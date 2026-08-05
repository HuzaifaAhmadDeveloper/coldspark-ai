<?php

namespace App\Console\Commands;

use App\Jobs\SendCampaignEmailJob;
use App\Models\CampaignEmail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DispatchDueCampaignEmails extends Command
{
    protected $signature = 'campaigns:dispatch-due {--limit=200 : Max emails to dispatch per run}';

    protected $description = 'Find campaign emails due for sending and queue them for delivery';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');

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
