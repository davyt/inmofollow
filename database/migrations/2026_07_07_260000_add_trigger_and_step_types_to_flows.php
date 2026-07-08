<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sequences', function (Blueprint $table) {
            if (! Schema::hasColumn('sequences', 'trigger_type')) {
                $table->string('trigger_type')->default('status_change')->after('active');
            }
        });

        Schema::table('sequence_steps', function (Blueprint $table) {
            if (! Schema::hasColumn('sequence_steps', 'step_type')) {
                $table->string('step_type')->default('send_template')->after('active');
            }
            if (! Schema::hasColumn('sequence_steps', 'step_data')) {
                $table->json('step_data')->nullable()->after('step_type');
            }
        });

        Schema::table('scheduled_messages', function (Blueprint $table) {
            if (! Schema::hasColumn('scheduled_messages', 'step_type')) {
                $table->string('step_type')->default('send_template')->after('ai_draft_discarded');
            }
            if (! Schema::hasColumn('scheduled_messages', 'step_data')) {
                $table->json('step_data')->nullable()->after('step_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sequences', fn ($t) => $t->dropColumn('trigger_type'));
        Schema::table('sequence_steps', fn ($t) => $t->dropColumn(['step_type', 'step_data']));
        Schema::table('scheduled_messages', fn ($t) => $t->dropColumn(['step_type', 'step_data']));
    }
};
