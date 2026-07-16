<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('sequences', function (Blueprint $table) {
            $table->boolean('replied')->default(false)->after('credits_used');
            $table->timestamp('replied_at')->nullable()->after('replied');
            $table->text('reply_notes')->nullable()->after('replied_at');
            $table->enum('reply_status', ['none','positive','negative','neutral'])->default('none')->after('reply_notes');
        });
    }
    public function down(): void {
        Schema::table('sequences', function (Blueprint $table) {
            $table->dropColumn(['replied','replied_at','reply_notes','reply_status']);
        });
    }
};