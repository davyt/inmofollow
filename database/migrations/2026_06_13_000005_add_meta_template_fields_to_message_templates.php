<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('message_templates', function (Blueprint $table) {
            $table->string('meta_template_name')->nullable()->after('body');
            $table->string('meta_template_language')->default('es_UY')->after('meta_template_name');
            $table->json('meta_template_variables')->nullable()->after('meta_template_language');
        });
    }

    public function down(): void
    {
        Schema::table('message_templates', function (Blueprint $table) {
            $table->dropColumn(['meta_template_name', 'meta_template_language', 'meta_template_variables']);
        });
    }
};
