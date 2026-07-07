<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_knowledge_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ai_agent_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->enum('type', ['text', 'url', 'pdf'])->default('text');
            $table->string('source_url', 2048)->nullable();
            $table->string('file_path')->nullable();
            $table->longText('content');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_knowledge_entries');
    }
};
