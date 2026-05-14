<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->timestamp('sent_at')->nullable()->after('status');
            $table->timestamp('failed_at')->nullable()->after('sent_at');
            $table->integer('retry_count')->default(0)->after('failed_at');
            $table->integer('max_retries')->default(3)->after('retry_count');
            $table->text('error_message')->nullable()->after('max_retries');
            $table->string('error_code', 50)->nullable()->after('error_message');
            
            // Add index for performance
            $table->index(['status', 'retry_count']);
            $table->index('sent_at');
            $table->index('failed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn([
                'sent_at',
                'failed_at',
                'retry_count',
                'max_retries',
                'error_message',
                'error_code',
            ]);
            
            // Drop indexes
            $table->dropIndex(['status', 'retry_count']);
            $table->dropIndex(['sent_at']);
            $table->dropIndex(['failed_at']);
        });
    }
};
