<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lead_import_profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('lead_import_profiles', 'default_lead_status_id')) {
                $table->foreignId('default_lead_status_id')->nullable()->after('default_email_consent')
                    ->constrained('lead_statuses')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('lead_import_profiles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('default_lead_status_id');
        });
    }
};
