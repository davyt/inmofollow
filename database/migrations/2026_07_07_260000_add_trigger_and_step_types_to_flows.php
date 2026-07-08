<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sequences', function (Blueprint $table) {
            $table->string('trigger_type')->default('status_change')->after('active');
        });

        Schema::table('sequence_steps', function (Blueprint $table) {
            $table->string('step_type')->default('send_template')->after('active');
            $table->json('step_data')->nullable()->after('step_type');
        });

        Schema::table('scheduled_messages', function (Blueprint $table) {
            $table->string('step_type')->default('send_template')->after('ai_draft_discarded');
            $table->json('step_data')->nullable()->after('step_type');
        });
    }

    public function down(): void
    {
        Schema::table('sequences', fn ($t) => $t->dropColumn('trigger_type'));
        Schema::table('sequence_steps', fn ($t) => $t->dropColumn(['step_type', 'step_data']));
        Schema::table('scheduled_messages', fn ($t) => $t->dropColumn(['step_type', 'step_data']));
    }
};
