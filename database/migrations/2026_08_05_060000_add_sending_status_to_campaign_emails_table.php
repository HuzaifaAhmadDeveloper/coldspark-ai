<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // MySQL's ENUM column needs an explicit ALTER to add a new allowed value.
        // SQLite (used by the test suite) has no real ENUM type — Laravel's schema
        // builder already stores it as a plain string there, so any value fits
        // and this statement would just error; skip it on non-MySQL connections.
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE campaign_emails MODIFY status ENUM('pending','scheduled','sending','sent','failed','skipped') NOT NULL DEFAULT 'pending'");
        }

        Schema::table('campaign_emails', function ($table) {
            $table->unique('tracking_token', 'ce_tracking_token_unique');
        });
    }

    public function down(): void
    {
        Schema::table('campaign_emails', function ($table) {
            $table->dropUnique('ce_tracking_token_unique');
        });

        DB::statement("UPDATE campaign_emails SET status = 'scheduled' WHERE status = 'sending'");

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE campaign_emails MODIFY status ENUM('pending','scheduled','sent','failed','skipped') NOT NULL DEFAULT 'pending'");
        }
    }
};
