<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('wa_phone_number_id')->nullable()->after('active');
            $table->text('wa_access_token')->nullable()->after('wa_phone_number_id');
            $table->boolean('wa_active')->default(false)->after('wa_access_token');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['wa_phone_number_id', 'wa_access_token', 'wa_active']);
        });
    }
};
