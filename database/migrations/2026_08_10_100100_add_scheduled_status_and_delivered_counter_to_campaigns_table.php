<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Adds SCHEDULED/FAILED campaign statuses and a delivered-count counter
     * (needed for Phase 5's delivery-rate/open-rate-by-delivered formulas).
     * Converts status to a plain string for the same reason as the earlier
     * campaign_emails migration: application code is the only writer of this
     * column, so a DB-level ENUM buys no safety and costs an ALTER every time
     * a new status is needed — already had to do that once.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE campaigns MODIFY status VARCHAR(20) NOT NULL DEFAULT 'draft'");
        } elseif (DB::connection()->getDriverName() === 'sqlite') {
            // SQLite enforces enum() as a CHECK constraint (learned this the hard
            // way on campaign_emails.status) — no ALTER for that, rebuild the column.
            Schema::table('campaigns', function (Blueprint $table) {
                $table->string('status_tmp', 20)->default('draft')->after('status');
            });
            DB::statement('UPDATE campaigns SET status_tmp = status');
            Schema::table('campaigns', function (Blueprint $table) {
                $table->dropColumn('status');
            });
            Schema::table('campaigns', function (Blueprint $table) {
                $table->renameColumn('status_tmp', 'status');
            });
        }

        Schema::table('campaigns', function (Blueprint $table) {
            $table->integer('emails_delivered')->default(0)->after('emails_sent');
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn('emails_delivered');
        });

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("UPDATE campaigns SET status = 'active' WHERE status IN ('scheduled','failed')");
            DB::statement("ALTER TABLE campaigns MODIFY status ENUM('draft','active','paused','completed','cancelled') NOT NULL DEFAULT 'draft'");
        }
    }
};
