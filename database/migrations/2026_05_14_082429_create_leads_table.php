<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
        
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('lead_status_id')->nullable()->constrained()->nullOnDelete();
        
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
        
            $table->string('property_type')->nullable();
            $table->string('zone')->nullable();
            $table->string('source')->nullable();
        
            $table->text('notes')->nullable();
        
            $table->boolean('whatsapp_consent')->default(false);
            $table->boolean('email_consent')->default(false);
            $table->boolean('do_not_contact')->default(false);
        
            $table->timestamp('last_contacted_at')->nullable();
            $table->timestamp('next_follow_up_at')->nullable();
        
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
