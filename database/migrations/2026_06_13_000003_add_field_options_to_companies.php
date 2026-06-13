<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->json('zone_options')->nullable()->after('wa_active');
            $table->json('property_type_options')->nullable()->after('zone_options');
            $table->json('lead_source_options')->nullable()->after('property_type_options');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['zone_options', 'property_type_options', 'lead_source_options']);
        });
    }
};
