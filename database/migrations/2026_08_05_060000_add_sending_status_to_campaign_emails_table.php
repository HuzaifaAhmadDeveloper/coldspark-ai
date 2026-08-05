<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("ALTER TABLE campaign_emails MODIFY status ENUM('pending','scheduled','sending','sent','failed','skipped') NOT NULL DEFAULT 'pending'");

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
        DB::statement("ALTER TABLE campaign_emails MODIFY status ENUM('pending','scheduled','sent','failed','skipped') NOT NULL DEFAULT 'pending'");
    }
};
