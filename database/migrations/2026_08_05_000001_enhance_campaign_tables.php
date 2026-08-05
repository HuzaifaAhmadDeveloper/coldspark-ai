<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->integer('emails_bounced')->default(0)->after('replies_received');
            $table->integer('emails_clicked')->default(0)->after('emails_bounced');
            $table->integer('cancelled_followups')->default(0)->after('emails_clicked');
            $table->json('working_days')->nullable()->after('timezone');
        });

        Schema::table('campaign_emails', function (Blueprint $table) {
            $table->boolean('clicked')->default(false)->after('opened_at');
            $table->timestamp('clicked_at')->nullable()->after('clicked');
            $table->boolean('bounced')->default(false)->after('clicked_at');
            $table->timestamp('bounced_at')->nullable()->after('bounced');
            $table->string('tracking_token', 64)->nullable()->after('message_id');

            $table->index(['status', 'scheduled_at'], 'ce_status_scheduled_idx');
            $table->index(['campaign_id', 'prospect_id', 'email_number'], 'ce_campaign_prospect_idx');
        });
    }

    public function down(): void
    {
        Schema::table('campaign_emails', function (Blueprint $table) {
            $table->dropIndex('ce_status_scheduled_idx');
            $table->dropIndex('ce_campaign_prospect_idx');
            $table->dropColumn(['clicked', 'clicked_at', 'bounced', 'bounced_at', 'tracking_token']);
        });

        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn(['emails_bounced', 'emails_clicked', 'cancelled_followups', 'working_days']);
        });
    }
};
