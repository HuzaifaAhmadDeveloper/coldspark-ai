<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->enum('status', ['draft','active','paused','completed','cancelled'])->default('draft');
            $table->integer('daily_limit')->default(30);
            $table->integer('gap_minutes')->default(10);
            $table->date('start_date')->nullable();
            $table->time('working_hours_start')->default('09:00:00');
            $table->time('working_hours_end')->default('17:00:00');
            $table->string('timezone')->default('Asia/Karachi');
            $table->integer('followup_delay_1')->default(3);
            $table->integer('followup_delay_2')->default(7);
            $table->boolean('stop_on_reply')->default(true);
            $table->integer('total_prospects')->default(0);
            $table->integer('emails_sent')->default(0);
            $table->integer('emails_opened')->default(0);
            $table->integer('replies_received')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('campaigns');
    }
};