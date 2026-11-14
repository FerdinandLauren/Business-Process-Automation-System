<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('approval_tasks', function (Blueprint $table) {
            $table->timestamp('reminder_sent_at')->nullable();
            $table->timestamp('breached_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('approval_tasks', function (Blueprint $table) {
            $table->dropColumn(['reminder_sent_at', 'breached_at']);
        });
    }
};
