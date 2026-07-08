<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sequences', function (Blueprint $table) {
            $table->string('trigger_source')->nullable()->after('trigger_type');
        });
    }

    public function down(): void
    {
        Schema::table('sequences', function (Blueprint $table) {
            $table->dropColumn('trigger_source');
        });
    }
};
