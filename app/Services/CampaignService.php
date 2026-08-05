<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\CampaignEmail;
use App\Models\EmailEvent;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CampaignService
{
    /**
     * Schedule all emails for a campaign respecting:
     * - Working hours
     * - Daily limit
     * - Gap between emails
     * - Timezone
     * - Working days (Mon-Fri default)
     * - Never schedule in the past
     */
    public function scheduleEmails(Campaign $campaign, array $generatedEmails, array $options = []): void
    {
        $tz  = $campaign->timezone ?: 'UTC';
        $now = now()->setTimezone($tz);

        // Campaign::start_date is cast to Carbon by the model. Carbon::parse() silently
        // ignores the $tz argument when given an already-cast DateTime instance, so passing
        // $campaign->start_date directly here would tag the date with the wrong timezone
        // (app.timezone instead of the campaign's) — go through a plain date string instead.
        $startDate = $campaign->start_date
            ? Carbon::parse($campaign->start_date->format('Y-m-d') . ' 00:00:00', $tz)
            : $now->copy()->startOfDay();
        $workStart = $campaign->working_hours_start ?? '09:00';
        $workEnd   = $campaign->working_hours_end ?? '17:00';
        $gap       = $campaign->gap_minutes ?? 10;
        $limit     = $campaign->daily_limit ?? 30;

        // Build initial scheduled time
        $scheduledTime = $startDate->copy()->setTimeFromTimeString($workStart);

        // If the scheduled time is in the past, adjust
        if ($scheduledTime->isPast()) {
            $todayEnd = $now->copy()->setTimeFromTimeString($workEnd);
            if ($now->lt($todayEnd)) {
                // Current time is within working hours — round up to next gap interval.
                // Uses raw Unix timestamps (not Carbon::diffInMinutes(), whose sign/absolute
                // behavior varies by version) so the sign is never ambiguous.
                $workStartToday   = $now->copy()->setTimeFromTimeString($workStart);
                $secondsPastStart = $now->getTimestamp() - $workStartToday->getTimestamp();
                $minutesPastStart = max(0, (int) ceil($secondsPastStart / 60));
                $nextSlot         = (int) ceil($minutesPastStart / $gap) * $gap;
                $scheduledTime    = $workStartToday->copy()->addMinutes($nextSlot);

                // Safety: if that's still in the past, push forward one gap
                if ($scheduledTime->lte($now)) {
                    $scheduledTime = $now->copy()->addMinutes($gap);
                }
            } else {
                // Past working hours — start next working day
                $scheduledTime = $campaign->nextWorkingDay(
                    $now->copy()->addDay()
                )->setTimeFromTimeString($workStart);
            }
        }

        // Ensure we start on a working day
        $scheduledTime = $campaign->nextWorkingDay($scheduledTime)->setTimeFromTimeString(
            $scheduledTime->format('H:i')
        );

        $emailsToday = 0;
        $followupDelay1 = $campaign->followup_delay_1 ?? 3;
        $followupDelay2 = $campaign->followup_delay_2 ?? 7;

        foreach ($generatedEmails as $gen) {
            if (($gen['status'] ?? '') === 'failed') continue;

            $prospectId = $gen['prospect_id'];

            // ── Daily limit check ──
            if ($emailsToday >= $limit) {
                $scheduledTime = $campaign->nextWorkingDay(
                    $scheduledTime->copy()->addDay()
                )->setTimeFromTimeString($workStart);
                $emailsToday = 0;
            }

            // ── End-of-day check ──
            $dayEnd = $scheduledTime->copy()->setTimeFromTimeString($workEnd);
            if ($scheduledTime->gte($dayEnd)) {
                $scheduledTime = $campaign->nextWorkingDay(
                    $scheduledTime->copy()->addDay()
                )->setTimeFromTimeString($workStart);
                $emailsToday = 0;
            }

            $email1Time = $scheduledTime->copy();
            $token1 = Str::random(32);

            // Email 1 — Opener
            CampaignEmail::create([
                'campaign_id'    => $campaign->id,
                'prospect_id'    => $prospectId,
                'email_number'   => 1,
                'subject'        => $gen['subject1'] ?? '',
                'body'           => $gen['email1'] ?? '',
                'scheduled_at'   => $this->toStorageTz($email1Time),
                'status'         => 'scheduled',
                'tracking_token' => $token1,
            ]);

            $emailsToday++;
            $scheduledTime->addMinutes($gap);

            // Follow-up 1 — anchored to Email 1 date + delay days
            $fu1Date = $campaign->nextWorkingDay(
                $email1Time->copy()->addDays($followupDelay1)
            )->setTimeFromTimeString($workStart);

            CampaignEmail::create([
                'campaign_id'    => $campaign->id,
                'prospect_id'    => $prospectId,
                'email_number'   => 2,
                'subject'        => 'Re: ' . ($gen['subject1'] ?? ''),
                'body'           => $gen['email2'] ?? '',
                'scheduled_at'   => $this->toStorageTz($fu1Date),
                'status'         => 'scheduled',
                'tracking_token' => Str::random(32),
            ]);

            // Follow-up 2 — anchored to Email 1 date + delay days
            $fu2Date = $campaign->nextWorkingDay(
                $email1Time->copy()->addDays($followupDelay2)
            )->setTimeFromTimeString($workStart);

            CampaignEmail::create([
                'campaign_id'    => $campaign->id,
                'prospect_id'    => $prospectId,
                'email_number'   => 3,
                'subject'        => 'Re: ' . ($gen['subject1'] ?? ''),
                'body'           => $gen['email3'] ?? '',
                'scheduled_at'   => $this->toStorageTz($fu2Date),
                'status'         => 'scheduled',
                'tracking_token' => Str::random(32),
            ]);
        }

        $this->recordLog($campaign, 'scheduled', 'All emails scheduled');
    }

    /**
     * MySQL DATETIME columns carry no timezone. If we save a Carbon instance
     * that's still set to the campaign's timezone (e.g. Asia/Karachi), the
     * wall-clock digits get stored as-is and later get misread as if they
     * were already in app.timezone (UTC) — silently shifting every send
     * time by the timezone offset. Always convert to the app timezone
     * immediately before persisting so "due for sending" comparisons
     * (which use now(), in app timezone) are correct.
     */
    private function toStorageTz(Carbon $time): Carbon
    {
        return $time->copy()->setTimezone(config('app.timezone', 'UTC'));
    }

    /**
     * Cancel remaining follow-ups for a prospect when a reply is detected.
     * Returns the number of cancelled follow-ups.
     */
    public function cancelFollowups(Campaign $campaign, int $prospectId): int
    {
        $cancelled = CampaignEmail::where('campaign_id', $campaign->id)
            ->where('prospect_id', $prospectId)
            ->where('email_number', '>', 1)
            ->where('status', 'scheduled')
            ->update(['status' => 'skipped']);

        if ($cancelled > 0) {
            $campaign->increment('cancelled_followups', $cancelled);
            $this->recordLog($campaign, 'followups_cancelled', "Cancelled {$cancelled} follow-ups for prospect #{$prospectId}");
        }

        return $cancelled;
    }

    /**
     * Mark a reply on a campaign email and cancel follow-ups if stop_on_reply is enabled.
     */
    public function markReply(CampaignEmail $email): void
    {
        if ($email->replied) return;

        $email->update([
            'replied'    => true,
            'replied_at' => now(),
        ]);

        $campaign = $email->campaign;
        $campaign->increment('replies_received');

        $this->recordEvent($email, 'replied');

        // Cancel follow-ups if configured
        if ($campaign->stop_on_reply) {
            $this->cancelFollowups($campaign, $email->prospect_id);
        }
    }

    /**
     * Mark an email as opened.
     */
    public function markOpened(CampaignEmail $email): void
    {
        if ($email->opened) return;

        $email->update([
            'opened'    => true,
            'opened_at' => now(),
        ]);

        $email->campaign->increment('emails_opened');
        $this->recordEvent($email, 'opened');
    }

    /**
     * Mark an email link as clicked.
     */
    public function markClicked(CampaignEmail $email): void
    {
        if (!$email->clicked) {
            $email->update([
                'clicked'    => true,
                'clicked_at' => now(),
            ]);
            $email->campaign->increment('emails_clicked');
        }

        $this->recordEvent($email, 'clicked');
    }

    /**
     * Mark an email as bounced.
     */
    public function markBounced(CampaignEmail $email): void
    {
        if ($email->bounced) return;

        $email->update([
            'bounced'    => true,
            'bounced_at' => now(),
            'status'     => 'failed',
        ]);

        $email->campaign->increment('emails_bounced');
        $this->recordEvent($email, 'bounced');

        // Cancel follow-ups for bounced addresses
        if ($email->campaign->stop_on_reply) {
            $this->cancelFollowups($email->campaign, $email->prospect_id);
        }
    }

    /**
     * Mark an email as currently being sent (transient state, set right before
     * the SMTP call so a crashed worker doesn't leave it stuck as "scheduled").
     */
    public function markSending(CampaignEmail $email): void
    {
        $email->update(['status' => 'sending']);
        $this->recordEvent($email, 'sending');
    }

    /**
     * Mark an email as successfully sent.
     */
    public function markSent(CampaignEmail $email, ?string $messageId = null): void
    {
        $email->update([
            'status'     => 'sent',
            'sent_at'    => now(),
            'message_id' => $messageId,
        ]);

        $email->campaign->increment('emails_sent');
        $this->recordEvent($email, 'sent');
    }

    /**
     * Mark an email as permanently failed (no more retries left).
     */
    public function markFailed(CampaignEmail $email, string $errorMessage): void
    {
        $email->update([
            'status'        => 'failed',
            'error_message' => $errorMessage,
        ]);

        $this->recordEvent($email, 'failed', ['error' => $errorMessage]);
    }

    /**
     * Look up a campaign email by its tracking token (used by the open pixel,
     * click redirect, and inbound webhook endpoints).
     */
    public function findByTrackingToken(string $token): ?CampaignEmail
    {
        return CampaignEmail::where('tracking_token', $token)->first();
    }

    /**
     * Check if a campaign is complete (all emails sent/skipped/failed).
     */
    public function checkCompletion(Campaign $campaign): bool
    {
        $remaining = $campaign->emails()
            ->whereIn('status', ['scheduled', 'pending'])
            ->count();

        if ($remaining === 0 && $campaign->status === 'active') {
            $campaign->update(['status' => 'completed']);
            $this->recordLog($campaign, 'completed', 'All emails processed');
            return true;
        }

        return false;
    }

    /**
     * Refresh campaign stats by counting actual email records.
     */
    public function refreshStats(Campaign $campaign): void
    {
        $emails = $campaign->emails;

        $campaign->update([
            'emails_sent'        => $emails->where('status', 'sent')->count(),
            'emails_opened'      => $emails->where('opened', true)->count(),
            'emails_clicked'     => $emails->where('clicked', true)->count(),
            'replies_received'   => $emails->where('replied', true)->count(),
            'emails_bounced'     => $emails->where('bounced', true)->count(),
            'cancelled_followups'=> $emails->where('status', 'skipped')->where('email_number', '>', 1)->count(),
        ]);
    }

    /**
     * Record an event for a campaign email.
     */
    public function recordEvent(CampaignEmail $email, string $type, array $metadata = []): void
    {
        EmailEvent::create([
            'campaign_email_id' => $email->id,
            'event_type'        => $type,
            'metadata'          => !empty($metadata) ? $metadata : null,
        ]);

        Log::channel('stack')->info("CampaignEmail #{$email->id}: {$type}", $metadata);
    }

    /**
     * Record a campaign-level log.
     */
    public function recordLog(Campaign $campaign, string $type, string $message = ''): void
    {
        Log::channel('stack')->info("Campaign #{$campaign->id} [{$campaign->name}]: {$type} — {$message}");
    }
}
