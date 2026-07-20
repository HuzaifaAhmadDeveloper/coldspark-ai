<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('crm_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('crm_type'); // hubspot, salesforce, pipedrive, csv
            $table->string('filename');
            $table->integer('total_contacts')->default(0);
            $table->integer('imported')->default(0);
            $table->integer('skipped')->default(0);
            $table->enum('status', ['pending','processing','completed','failed'])->default('pending');
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('crm_imports');
    }
};