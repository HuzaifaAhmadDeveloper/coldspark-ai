<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\CampaignEmail;
use App\Models\EmailEvent;
use App\Models\Prospect;
use App\Models\Suppression;
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
    public function markReply(CampaignEmail $email, ?string $providerEventId = null): void
    {
        if ($email->replied) return;

        $email->update([
            'replied'    => true,
            'replied_at' => now(),
        ]);

        $campaign = $email->campaign;
        $campaign->increment('replies_received');

        $this->recordEvent($email, 'replied', [], $providerEventId);

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
     * Mark an email as bounced. $permanent distinguishes a hard bounce (bad
     * address, domain doesn't exist — will never succeed) from a soft/transient
     * one (mailbox full, greylisting — may well succeed later). Only a
     * permanent bounce globally suppresses the address; a soft bounce just
     * marks this one send attempt as failed and lets the retry policy handle it.
     */
    public function markBounced(CampaignEmail $email, bool $permanent = true, ?string $providerEventId = null): void
    {
        if ($email->bounced) return;

        $email->update([
            'bounced'    => true,
            'bounced_at' => now(),
            'status'     => 'failed',
        ]);

        $email->campaign->increment('emails_bounced');
        $this->recordEvent($email, 'bounced', ['permanent' => $permanent], $providerEventId);

        if (!$permanent) return;

        $this->suppress($email->prospect->email, $email->campaign->user_id, 'hard_bounce', $email->id);

        // Cancel follow-ups for this campaign too (separate from the
        // cross-campaign suppression above, which blocks all future campaigns).
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
     * Mark an email as successfully sent. $provider records which mailer
     * actually delivered it (ses/smtp/...) — useful once multiple providers
     * are in play to see per-provider deliverability.
     */
    public function markSent(CampaignEmail $email, ?string $messageId = null, ?string $provider = null): void
    {
        $email->update([
            'status'     => 'sent',
            'sent_at'    => now(),
            'message_id' => $messageId,
            'provider'   => $provider,
        ]);

        $email->campaign->increment('emails_sent');
        $this->recordEvent($email, 'sent', $provider ? ['provider' => $provider] : []);
    }

    /**
     * Mark an email as confirmed delivered by the provider (SES/SNS delivery
     * notification, or another ESP's delivery webhook).
     */
    public function markDelivered(CampaignEmail $email, ?string $providerEventId = null): void
    {
        if ($email->delivered) return;

        $email->update([
            'delivered'    => true,
            'delivered_at' => now(),
        ]);

        $email->campaign->increment('emails_delivered');
        $this->recordEvent($email, 'delivered', [], $providerEventId);
    }

    /**
     * Skip a single email with a reason (e.g. recipient unsubscribed between
     * scheduling and send time) without touching sent/opened/reply counters.
     */
    public function markSkipped(CampaignEmail $email, string $reason): void
    {
        if ($email->status === 'skipped') return;

        $email->update(['status' => 'skipped']);
        $this->recordEvent($email, 'skipped', ['reason' => $reason]);
    }

    /**
     * Suppress a prospect from all future campaign sends (unsubscribe / spam
     * complaint). Cancels every not-yet-sent email across every campaign for
     * this prospect — once someone opts out, that applies to all outreach
     * from this account, not just the one campaign they replied from.
     */
    public function markUnsubscribed(Prospect $prospect, string $reason = 'unsubscribed', ?string $providerEventId = null): int
    {
        if (!$prospect->unsubscribed) {
            $prospect->update(['unsubscribed' => true, 'unsubscribed_at' => now()]);
        }

        $this->suppress($prospect->email, $prospect->user_id, $reason);

        $pending = CampaignEmail::where('prospect_id', $prospect->id)
            ->whereIn('status', ['scheduled', 'pending'])
            ->get();

        foreach ($pending as $email) {
            $email->update(['status' => 'skipped']);
            $email->campaign->increment('cancelled_followups');
            $this->recordEvent($email, $reason === 'complaint' ? 'complaint' : 'unsubscribed', [], $providerEventId);
        }

        return $pending->count();
    }

    /**
     * Add an email to the centralized, cross-campaign suppression list.
     * Idempotent — re-suppressing an already-suppressed address is a no-op.
     */
    public function suppress(string $email, int $userId, string $reason, ?int $sourceCampaignEmailId = null): void
    {
        Suppression::firstOrCreate(
            ['user_id' => $userId, 'email' => strtolower(trim($email))],
            ['reason' => $reason, 'source_campaign_email_id' => $sourceCampaignEmailId],
        );
    }

    /**
     * The authoritative "may we email this address?" check. Every send path
     * (queued job, CSV import, manual add) must consult this — it's the one
     * source of truth across unsubscribes, hard bounces, complaints, and
     * manual suppressions, spanning every campaign this user has ever run.
     */
    public function isSuppressed(string $email, int $userId): bool
    {
        return Suppression::where('user_id', $userId)
            ->where('email', strtolower(trim($email)))
            ->exists();
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
        if (!in_array($campaign->status, ['active', 'scheduled'])) return false;

        // "sending" rows are mid-flight, possibly on a different queue worker
        // right now — must not be counted as complete just because this
        // particular job's own row already resolved.
        $remaining = $campaign->emails()
            ->whereIn('status', ['scheduled', 'pending', 'sending'])
            ->count();

        if ($remaining > 0) return false;

        // FAILED is reserved for a genuine campaign-level failure — every
        // single send attempt failing (SES misconfigured, sender unverified,
        // etc) — not for the normal case where some individual recipients
        // bounce/fail while most sends succeed. That's still COMPLETED.
        $attempted = $campaign->emails()->whereIn('status', ['sent', 'failed'])->count();
        $succeeded = $campaign->emails()->where('status', 'sent')->count();

        if ($attempted > 0 && $succeeded === 0) {
            $campaign->update(['status' => 'failed']);
            $this->recordLog($campaign, 'failed', 'Every campaign email failed to send');
        } else {
            $campaign->update(['status' => 'completed']);
            $this->recordLog($campaign, 'completed', 'All emails processed');
        }

        return true;
    }

    /**
     * A SCHEDULED campaign (future start_date, nothing sent yet) becomes
     * ACTIVE the instant its first email actually starts sending — not at
     * creation time and not merely because start_date arrived, since a
     * worker outage could mean the date passed with nothing actually sent yet.
     */
    public function activateIfScheduled(Campaign $campaign): void
    {
        if ($campaign->status !== 'scheduled') return;

        $campaign->update(['status' => 'active']);
        $this->recordLog($campaign, 'activated', 'First email started sending');
    }

    /**
     * Resume a paused campaign back to the status it should logically be in:
     * SCHEDULED if its start date is still in the future and nothing has
     * sent yet (matches how it started), otherwise ACTIVE.
     */
    public function resumeCampaign(Campaign $campaign): void
    {
        $tz = $campaign->timezone ?: 'UTC';
        // Same pattern as scheduleEmails(): build the comparison date from a
        // plain string, not from the already-cast Carbon, so it's tagged with
        // the campaign's own timezone instead of silently inheriting app.timezone.
        $startDateInTz = $campaign->start_date
            ? Carbon::parse($campaign->start_date->format('Y-m-d') . ' 00:00:00', $tz)
            : now($tz)->startOfDay();

        $startInFuture = $startDateInTz->gt(now($tz)->startOfDay());
        $hasSentAny    = $campaign->emails_sent > 0;

        $campaign->update(['status' => ($startInFuture && !$hasSentAny) ? 'scheduled' : 'active']);
        $this->recordLog($campaign, 'resumed', 'Campaign resumed');
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
    /**
     * $providerEventId, when given (SES/SNS's own notification MessageId, or
     * an equivalent ID from another ESP), makes this call idempotent at the
     * DB level via a unique index on (campaign_email_id, provider_event_id) —
     * a redelivered SNS notification for the same event can't create a
     * second EmailEvent row. Pixel/click events have no such ID and pass null,
     * which never collides (MySQL allows unlimited NULLs in a unique index).
     */
    public function recordEvent(CampaignEmail $email, string $type, array $metadata = [], ?string $providerEventId = null): void
    {
        if ($providerEventId) {
            $existing = EmailEvent::where('campaign_email_id', $email->id)
                ->where('provider_event_id', $providerEventId)
                ->exists();
            if ($existing) {
                Log::channel('stack')->info("CampaignEmail #{$email->id}: duplicate provider event ignored ({$type}, {$providerEventId})");
                return;
            }
        }

        try {
            EmailEvent::create([
                'campaign_email_id'  => $email->id,
                'event_type'         => $type,
                'metadata'           => !empty($metadata) ? $metadata : null,
                'provider_event_id'  => $providerEventId,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            // Two workers raced past the EXISTS check above for the same
            // redelivered notification — the DB's unique index is the real
            // guarantee; losing this race just means the event is already
            // recorded, which is the correct outcome, not a failure.
            if ($providerEventId && str_contains($e->getMessage(), 'Duplicate entry')) {
                Log::channel('stack')->info("CampaignEmail #{$email->id}: duplicate provider event lost race, ignored ({$type}, {$providerEventId})");
                return;
            }
            throw $e;
        }

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
