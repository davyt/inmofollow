<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wa_inbound_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->string('wa_message_id')->nullable();
            $table->string('message_type')->default('text');
            $table->text('body')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamps();

            $table->index(['lead_id', 'received_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_inbound_messages');
    }
};
