<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('suppressions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('email');
            $table->enum('reason', ['unsubscribed', 'hard_bounce', 'complaint', 'manual', 'invalid_email']);
            $table->foreignId('source_campaign_email_id')->nullable()
                ->constrained('campaign_emails')->nullOnDelete();
            $table->timestamps();

            // One suppression row per user+email — isSuppressed() is a simple existence check.
            $table->unique(['user_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppressions');
    }
};
