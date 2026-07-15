<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('prospect_id')->constrained()->onDelete('cascade');
            $table->string('style');
            $table->string('offer');
            $table->string('value_prop');
            $table->string('cta');
            $table->string('subject1')->nullable();
            $table->string('subject2')->nullable();
            $table->text('email1');
            $table->text('email2');
            $table->text('email3');
            $table->integer('credits_used')->default(1);
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('sequences');
    }
};