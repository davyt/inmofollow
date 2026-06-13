<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scheduled_messages', function (Blueprint $table) {
            $table->string('wa_message_id')->nullable()->after('sent_at');
            $table->text('error_message')->nullable()->after('wa_message_id');
        });
    }

    public function down(): void
    {
        Schema::table('scheduled_messages', function (Blueprint $table) {
            $table->dropColumn(['wa_message_id', 'error_message']);
        });
    }
};
