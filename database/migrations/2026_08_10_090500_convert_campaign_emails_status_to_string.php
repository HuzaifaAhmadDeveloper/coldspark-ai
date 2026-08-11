<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Every status value ever needed here (pending/scheduled/sending/sent/
     * failed/skipped) is written exclusively by application code (CampaignService,
     * SendCampaignEmailJob) — nothing user-supplied ever reaches this column.
     * A strict DB-level ENUM/CHECK buys no real safety over that, and costs
     * real pain: MySQL needs an ALTER for every new status (as the previous
     * migration had to do), and SQLite enforces its enum() as a CHECK
     * constraint that's effectively impossible to alter without rebuilding
     * the table. Plain string sidesteps both permanently.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE campaign_emails MODIFY status VARCHAR(20) NOT NULL DEFAULT 'pending'");
            return;
        }

        if (DB::connection()->getDriverName() === 'sqlite') {
            // SQLite has no ALTER for CHECK constraints — rebuild the column.
            // The composite index over (status, scheduled_at) has to go first;
            // SQLite refuses to drop a column that's part of an index.
            Schema::table('campaign_emails', function ($table) {
                $table->dropIndex('ce_status_scheduled_idx');
            });

            Schema::table('campaign_emails', function ($table) {
                $table->string('status_tmp', 20)->default('pending')->after('status');
            });
            DB::statement('UPDATE campaign_emails SET status_tmp = status');
            Schema::table('campaign_emails', function ($table) {
                $table->dropColumn('status');
            });
            Schema::table('campaign_emails', function ($table) {
                $table->renameColumn('status_tmp', 'status');
            });

            Schema::table('campaign_emails', function ($table) {
                $table->index(['status', 'scheduled_at'], 'ce_status_scheduled_idx');
            });
        }
    }

    public function down(): void
    {
        // Deliberately not reversible back to a strict enum/check — the whole
        // point is that constraint was never load-bearing. Down is a no-op.
    }
};
