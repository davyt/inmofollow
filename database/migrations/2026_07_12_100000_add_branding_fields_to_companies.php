<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('brand_logo_path')->nullable()->after('logo');
            $table->string('brand_favicon_path')->nullable()->after('brand_logo_path');
            $table->string('brand_primary_color', 7)->nullable()->after('brand_favicon_path');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['brand_logo_path', 'brand_favicon_path', 'brand_primary_color']);
        });
    }
};
