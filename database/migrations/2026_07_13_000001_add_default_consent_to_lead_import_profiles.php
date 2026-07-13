<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lead_import_profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('lead_import_profiles', 'default_whatsapp_consent')) {
                $table->boolean('default_whatsapp_consent')->nullable()->after('default_source');
            }
            if (! Schema::hasColumn('lead_import_profiles', 'default_email_consent')) {
                $table->boolean('default_email_consent')->nullable()->after('default_whatsapp_consent');
            }
        });
    }

    public function down(): void
    {
        Schema::table('lead_import_profiles', function (Blueprint $table) {
            $table->dropColumn(['default_whatsapp_consent', 'default_email_consent']);
        });
    }
};
