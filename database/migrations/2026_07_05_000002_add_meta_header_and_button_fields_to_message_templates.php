<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('message_templates', function (Blueprint $table) {
            $table->string('meta_header_type')->nullable()->after('meta_template_variables');
            $table->string('meta_header_media_url')->nullable()->after('meta_header_type');
            $table->string('meta_button_variable')->nullable()->after('meta_header_media_url');
        });
    }

    public function down(): void
    {
        Schema::table('message_templates', function (Blueprint $table) {
            $table->dropColumn(['meta_header_type', 'meta_header_media_url', 'meta_button_variable']);
        });
    }
};
