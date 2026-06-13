<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->timestamp('last_wa_inbound_at')->nullable()->after('last_contacted_at');
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->string('wa_business_account_id')->nullable()->after('wa_active');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn('last_wa_inbound_at');
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('wa_business_account_id');
        });
    }
};
