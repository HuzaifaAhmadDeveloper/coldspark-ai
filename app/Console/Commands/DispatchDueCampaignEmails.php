<?php

namespace App\Console\Commands;

use App\Jobs\SendCampaignEmailJob;
use App\Models\CampaignEmail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DispatchDueCampaignEmails extends Command
{
    protected $signature = 'campaigns:dispatch-due {--limit= : Max emails to dispatch per run (defaults to services.email.batch_size)}';

    protected $description = 'Find campaign emails due for sending and queue them for delivery';

    public function handle(): int
    {
        $limit = (int) ($this->option('limit') ?: config('services.email.batch_size', 200));

        // Provider account-level daily cap (e.g. SES sandbox: 200/24h) — this is
        // enforced globally across every campaign/user sharing this SES account,
        // separate from any per-campaign daily_limit (which only paces one
        // campaign's own schedule). "sending" rows count too: they're in-flight
        // and about to consume quota, not yet reflected in sent_at.
        $dailyLimit = (int) config('services.email.daily_limit', 200);
        $sentToday = CampaignEmail::whereIn('status', ['sent', 'sending'])
            ->where('updated_at', '>=', now()->startOfDay())
            ->count();
        $remainingQuota = max(0, $dailyLimit - $sentToday);

        if ($remainingQuota <= 0) {
            Log::warning('campaigns:dispatch-due: daily provider send limit reached, deferring remaining sends', [
                'daily_limit' => $dailyLimit, 'sent_or_sending_today' => $sentToday,
            ]);
            $this->warn("Daily provider limit ({$dailyLimit}) reached — deferring remaining sends until tomorrow.");
            return self::SUCCESS;
        }

        $limit = min($limit, $remainingQuota);

        $ids = CampaignEmail::dueForSending()
            ->orderBy('scheduled_at')
            ->limit($limit)
            ->pluck('id');

        if ($ids->isEmpty()) {
            return self::SUCCESS;
        }

        // Flip to "sending" up front so an overlapping run (or a slow queue)
        // can't pick the same row up twice.
        CampaignEmail::whereIn('id', $ids)->update(['status' => 'sending']);

        foreach ($ids as $id) {
            SendCampaignEmailJob::dispatch($id);
        }

        Log::info('campaigns:dispatch-due queued ' . $ids->count() . ' email(s)', ['ids' => $ids->all()]);
        $this->info("Queued {$ids->count()} campaign email(s) for sending.");

        return self::SUCCESS;
    }
}
