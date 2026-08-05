<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('campaign_emails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->onDelete('cascade');
            $table->foreignId('prospect_id')->constrained()->onDelete('cascade');
            $table->integer('email_number'); // 1, 2, 3
            $table->string('subject')->nullable();
            $table->text('body');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->enum('status', ['pending','scheduled','sent','failed','skipped'])->default('pending');
            $table->string('message_id')->nullable(); // for tracking
            $table->boolean('opened')->default(false);
            $table->timestamp('opened_at')->nullable();
            $table->boolean('replied')->default(false);
            $table->timestamp('replied_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('campaign_emails');
    }
};