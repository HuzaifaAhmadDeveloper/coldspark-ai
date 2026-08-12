<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * DB-level duplicate-send protection. Verified before writing this
     * migration: zero duplicate (campaign_id, prospect_id, email_number)
     * groups exist in the current data (checked via a GROUP BY ... HAVING
     * COUNT(*) > 1 query), so this is safe to apply directly. Replaces the
     * existing non-unique index (same columns, same query benefit) with a
     * unique one rather than keeping both.
     */
    public function up(): void
    {
        // MySQL keeps the old index alive to back the campaign_id/prospect_id
        // foreign keys — it won't let it be dropped until a replacement index
        // exists, so the new unique index has to be added first.
        Schema::table('campaign_emails', function (Blueprint $table) {
            $table->unique(['campaign_id', 'prospect_id', 'email_number'], 'ce_campaign_prospect_unique');
        });

        Schema::table('campaign_emails', function (Blueprint $table) {
            $table->dropIndex('ce_campaign_prospect_idx');
        });
    }

    public function down(): void
    {
        Schema::table('campaign_emails', function (Blueprint $table) {
            $table->index(['campaign_id', 'prospect_id', 'email_number'], 'ce_campaign_prospect_idx');
        });

        Schema::table('campaign_emails', function (Blueprint $table) {
            $table->dropUnique('ce_campaign_prospect_unique');
        });
    }
};
