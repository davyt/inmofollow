<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_agents', function (Blueprint $table) {
            $table->string('provider')->default('anthropic')->after('name');
            $table->string('model')->nullable()->after('provider');
            $table->text('api_key')->nullable()->after('model');
        });
    }

    public function down(): void
    {
        Schema::table('ai_agents', function (Blueprint $table) {
            $table->dropColumn(['provider', 'model', 'api_key']);
        });
    }
};
