<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('email_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sequence_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('campaign_email_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('event_type'); // sent, delivered, opened, clicked, replied, bounced, unsubscribed
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('email_events');
    }
};
