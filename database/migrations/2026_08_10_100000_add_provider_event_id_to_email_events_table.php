<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('email_events', function (Blueprint $table) {
            $table->string('provider_event_id')->nullable()->after('event_type');

            // NULL values don't collide in a MySQL/SQLite unique index, so this
            // only actually constrains webhook-sourced events (which carry a
            // real provider ID) — pixel/click/manual events (provider_event_id
            // always null) are unaffected and can repeat freely.
            $table->unique(['campaign_email_id', 'provider_event_id'], 'ee_campaign_email_provider_event_unique');
        });
    }

    public function down(): void
    {
        Schema::table('email_events', function (Blueprint $table) {
            $table->dropUnique('ee_campaign_email_provider_event_unique');
            $table->dropColumn('provider_event_id');
        });
    }
};
