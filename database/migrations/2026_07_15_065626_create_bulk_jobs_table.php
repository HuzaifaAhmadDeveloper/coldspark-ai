<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('bulk_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('filename');
            $table->integer('total')->default(0);
            $table->integer('processed')->default(0);
            $table->integer('failed')->default(0);
            $table->enum('status', ['pending','processing','completed','failed'])->default('pending');
            $table->string('style')->default('direct');
            $table->string('offer')->nullable();
            $table->string('value_prop')->nullable();
            $table->string('cta')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('bulk_jobs');
    }
};