<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('sequences', function (Blueprint $table) {
            $table->text('edited_email1')->nullable()->after('email3');
            $table->text('edited_email2')->nullable()->after('edited_email1');
            $table->text('edited_email3')->nullable()->after('edited_email2');
            $table->boolean('is_edited')->default(false)->after('edited_email3');
        });
    }
    public function down(): void {
        Schema::table('sequences', function (Blueprint $table) {
            $table->dropColumn(['edited_email1', 'edited_email2', 'edited_email3', 'is_edited']);
        });
    }
};