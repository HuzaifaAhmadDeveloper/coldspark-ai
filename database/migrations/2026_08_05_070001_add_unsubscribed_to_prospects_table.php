<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('prospects', function (Blueprint $table) {
            $table->boolean('unsubscribed')->default(false)->after('email');
            $table->timestamp('unsubscribed_at')->nullable()->after('unsubscribed');
        });
    }

    public function down(): void
    {
        Schema::table('prospects', function (Blueprint $table) {
            $table->dropColumn(['unsubscribed', 'unsubscribed_at']);
        });
    }
};
