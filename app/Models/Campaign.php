<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'name', 'status', 'daily_limit', 'gap_minutes',
        'start_date', 'working_hours_start', 'working_hours_end',
        'timezone', 'working_days', 'followup_delay_1', 'followup_delay_2',
        'stop_on_reply', 'total_prospects', 'emails_sent', 'emails_delivered',
        'emails_opened', 'replies_received', 'emails_bounced',
        'emails_clicked', 'cancelled_followups', 'notes',
    ];

    protected $casts = [
        'start_date'    => 'date',
        'stop_on_reply' => 'boolean',
        'working_days'  => 'array',
    ];

    // ── Relationships ──

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function emails()
    {
        return $this->hasMany(CampaignEmail::class);
    }

    public function prospects()
    {
        return $this->hasManyThrough(
            Prospect::class, CampaignEmail::class,
            'campaign_id', 'id', 'id', 'prospect_id'
        );
    }

    // ── Scoped Relationship Helpers ──

    public function scheduledEmails()
    {
        return $this->emails()->where('status', 'scheduled');
    }

    public function sentEmails()
    {
        return $this->emails()->where('status', 'sent');
    }

    // ── Rate Accessors ──
    //
    // Delivered is the correct denominator for open/click/reply/unsubscribe —
    // an email that was never delivered can't have been opened, so measuring
    // those against Sent understates the real rate whenever some sends bounce.
    // Delivery and Bounce are measured against Sent — that's what those two
    // are actually rates *of*. Delivered stays 0 (all these rates stay 0, not
    // fabricated) until Amazon SES's delivery webhook is actually connected —
    // see SES_CONFIGURATION_SET in the Campaign Dashboard / setup docs.

    public function getDeliveryRateAttribute(): float
    {
        if ($this->emails_sent === 0) return 0;
        return round(($this->emails_delivered / $this->emails_sent) * 100, 1);
    }

    public function getOpenRateAttribute(): float
    {
        if ($this->emails_delivered === 0) return 0;
        return round(($this->emails_opened / $this->emails_delivered) * 100, 1);
    }

    public function getReplyRateAttribute(): float
    {
        if ($this->emails_delivered === 0) return 0;
        return round(($this->replies_received / $this->emails_delivered) * 100, 1);
    }

    public function getBounceRateAttribute(): float
    {
        if ($this->emails_sent === 0) return 0;
        return round(($this->emails_bounced / $this->emails_sent) * 100, 1);
    }

    public function getClickRateAttribute(): float
    {
        if ($this->emails_delivered === 0) return 0;
        return round(($this->emails_clicked / $this->emails_delivered) * 100, 1);
    }

    public function getUnsubscribeRateAttribute(): float
    {
        if ($this->emails_delivered === 0) return 0;
        $unsubscribes = EmailEvent::whereIn('campaign_email_id', $this->emails()->pluck('id'))
            ->where('event_type', 'unsubscribed')
            ->count();
        return round($unsubscribes / $this->emails_delivered * 100, 1);
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'scheduled' => 'purple',
            'active'    => 'green',
            'paused'    => 'yellow',
            'completed' => 'blue',
            'cancelled' => 'red',
            'failed'    => 'red',
            default     => 'gray',
        };
    }

    // ── Helpers ──

    /**
     * Check if a given date is a configured working day.
     * Defaults to Monday–Friday if working_days is not set.
     */
    public function isWorkingDay(\Carbon\Carbon $date): bool
    {
        $days = $this->working_days ?? [1, 2, 3, 4, 5]; // Mon-Fri
        return in_array($date->dayOfWeekIso, $days);
    }

    /**
     * Advance a Carbon date to the next valid working day for this campaign.
     */
    public function nextWorkingDay(\Carbon\Carbon $date): \Carbon\Carbon
    {
        $next = $date->copy();
        $maxGuard = 0;
        while (!$this->isWorkingDay($next) && $maxGuard < 10) {
            $next->addDay();
            $maxGuard++;
        }
        return $next;
    }
}