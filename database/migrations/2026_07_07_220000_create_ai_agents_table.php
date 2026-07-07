<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_agents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name')->default('Agente IA');
            $table->text('system_prompt');
            $table->boolean('active')->default(false);
            $table->boolean('auto_send')->default(false);
            $table->timestamps();
        });

        Schema::table('wa_inbound_messages', function (Blueprint $table) {
            $table->text('ai_draft_reply')->nullable()->after('body');
            $table->boolean('ai_draft_discarded')->default(false)->after('ai_draft_reply');
        });
    }

    public function down(): void
    {
        Schema::table('wa_inbound_messages', function (Blueprint $table) {
            $table->dropColumn(['ai_draft_reply', 'ai_draft_discarded']);
        });
        Schema::dropIfExists('ai_agents');
    }
};
