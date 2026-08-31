<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wa_inbound_messages', function (Blueprint $table) {
            $table->string('media_path')->nullable()->after('body');
            $table->string('media_mime_type')->nullable()->after('media_path');
        });
    }

    public function down(): void
    {
        Schema::table('wa_inbound_messages', function (Blueprint $table) {
            $table->dropColumn(['media_path', 'media_mime_type']);
        });
    }
};
