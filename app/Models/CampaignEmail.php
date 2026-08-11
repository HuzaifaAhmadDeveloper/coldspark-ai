<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CampaignEmail extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_id', 'prospect_id', 'email_number',
        'subject', 'body', 'scheduled_at', 'sent_at',
        'status', 'message_id', 'provider', 'tracking_token',
        'opened', 'opened_at', 'clicked', 'clicked_at',
        'replied', 'replied_at', 'bounced', 'bounced_at',
        'delivered', 'delivered_at', 'error_message',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'sent_at'      => 'datetime',
        'opened_at'    => 'datetime',
        'clicked_at'   => 'datetime',
        'replied_at'   => 'datetime',
        'bounced_at'   => 'datetime',
        'delivered_at' => 'datetime',
        'opened'       => 'boolean',
        'clicked'      => 'boolean',
        'replied'      => 'boolean',
        'bounced'      => 'boolean',
        'delivered'    => 'boolean',
    ];

    // ── Relationships ──

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function prospect()
    {
        return $this->belongsTo(Prospect::class);
    }

    public function events()
    {
        return $this->hasMany(EmailEvent::class);
    }

    // ── Scopes ──

    /**
     * Emails that are due for sending: status=scheduled, scheduled_at <= now,
     * and belonging to an active campaign.
     */
    public function scopeDueForSending(Builder $query): Builder
    {
        return $query
            ->where('status', 'scheduled')
            ->where('scheduled_at', '<=', now())
            ->whereHas('campaign', fn (Builder $q) => $q->where('status', 'active'));
    }

    /**
     * All follow-up emails for a given prospect in a campaign.
     */
    public function scopeFollowupsFor(Builder $query, int $campaignId, int $prospectId): Builder
    {
        return $query
            ->where('campaign_id', $campaignId)
            ->where('prospect_id', $prospectId)
            ->where('email_number', '>', 1)
            ->where('status', 'scheduled');
    }
}