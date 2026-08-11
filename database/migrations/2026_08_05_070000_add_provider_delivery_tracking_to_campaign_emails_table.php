<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('campaign_emails', function (Blueprint $table) {
            $table->string('provider')->nullable()->after('message_id');
            $table->boolean('delivered')->default(false)->after('bounced_at');
            $table->timestamp('delivered_at')->nullable()->after('delivered');
        });
    }

    public function down(): void
    {
        Schema::table('campaign_emails', function (Blueprint $table) {
            $table->dropColumn(['provider', 'delivered', 'delivered_at']);
        });
    }
};
