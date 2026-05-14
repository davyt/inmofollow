<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // sequences
        if (! Schema::hasColumn('sequences', 'company_id')) {
            Schema::table('sequences', function (Blueprint $table) {
                $table->foreignId('company_id')
                    ->nullable()
                    ->after('id')
                    ->constrained()
                    ->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('sequences', 'lead_status_id')) {
            Schema::table('sequences', function (Blueprint $table) {
                $table->foreignId('lead_status_id')
                    ->nullable()
                    ->after('company_id')
                    ->constrained()
                    ->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('sequences', 'name')) {
            Schema::table('sequences', function (Blueprint $table) {
                $table->string('name')->after('lead_status_id');
            });
        }

        if (! Schema::hasColumn('sequences', 'description')) {
            Schema::table('sequences', function (Blueprint $table) {
                $table->text('description')->nullable()->after('name');
            });
        }

        if (! Schema::hasColumn('sequences', 'active')) {
            Schema::table('sequences', function (Blueprint $table) {
                $table->boolean('active')->default(true)->after('description');
            });
        }

        // sequence_steps
        if (! Schema::hasColumn('sequence_steps', 'sequence_id')) {
            Schema::table('sequence_steps', function (Blueprint $table) {
                $table->foreignId('sequence_id')
                    ->after('id')
                    ->constrained()
                    ->cascadeOnDelete();
            });
        }

        if (! Schema::hasColumn('sequence_steps', 'message_template_id')) {
            Schema::table('sequence_steps', function (Blueprint $table) {
                $table->foreignId('message_template_id')
                    ->nullable()
                    ->after('sequence_id')
                    ->constrained()
                    ->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('sequence_steps', 'day_offset')) {
            Schema::table('sequence_steps', function (Blueprint $table) {
                $table->integer('day_offset')->default(0)->after('message_template_id');
            });
        }

        if (! Schema::hasColumn('sequence_steps', 'channel')) {
            Schema::table('sequence_steps', function (Blueprint $table) {
                $table->string('channel')->default('whatsapp')->after('day_offset');
            });
        }

        if (! Schema::hasColumn('sequence_steps', 'sort_order')) {
            Schema::table('sequence_steps', function (Blueprint $table) {
                $table->integer('sort_order')->default(0)->after('channel');
            });
        }

        if (! Schema::hasColumn('sequence_steps', 'active')) {
            Schema::table('sequence_steps', function (Blueprint $table) {
                $table->boolean('active')->default(true)->after('sort_order');
            });
        }

        // scheduled_messages
        if (! Schema::hasColumn('scheduled_messages', 'lead_id')) {
            Schema::table('scheduled_messages', function (Blueprint $table) {
                $table->foreignId('lead_id')
                    ->after('id')
                    ->constrained()
                    ->cascadeOnDelete();
            });
        }

        if (! Schema::hasColumn('scheduled_messages', 'sequence_id')) {
            Schema::table('scheduled_messages', function (Blueprint $table) {
                $table->foreignId('sequence_id')
                    ->nullable()
                    ->after('lead_id')
                    ->constrained()
                    ->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('scheduled_messages', 'sequence_step_id')) {
            Schema::table('scheduled_messages', function (Blueprint $table) {
                $table->foreignId('sequence_step_id')
                    ->nullable()
                    ->after('sequence_id')
                    ->constrained()
                    ->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('scheduled_messages', 'message_template_id')) {
            Schema::table('scheduled_messages', function (Blueprint $table) {
                $table->foreignId('message_template_id')
                    ->nullable()
                    ->after('sequence_step_id')
                    ->constrained()
                    ->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('scheduled_messages', 'user_id')) {
            Schema::table('scheduled_messages', function (Blueprint $table) {
                $table->foreignId('user_id')
                    ->nullable()
                    ->after('message_template_id')
                    ->constrained()
                    ->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('scheduled_messages', 'channel')) {
            Schema::table('scheduled_messages', function (Blueprint $table) {
                $table->string('channel')->default('whatsapp')->after('user_id');
            });
        }

        if (! Schema::hasColumn('scheduled_messages', 'message_body')) {
            Schema::table('scheduled_messages', function (Blueprint $table) {
                $table->text('message_body')->after('channel');
            });
        }

        if (! Schema::hasColumn('scheduled_messages', 'status')) {
            Schema::table('scheduled_messages', function (Blueprint $table) {
                $table->string('status')->default('pending')->after('message_body');
            });
        }

        if (! Schema::hasColumn('scheduled_messages', 'scheduled_for')) {
            Schema::table('scheduled_messages', function (Blueprint $table) {
                $table->timestamp('scheduled_for')->nullable()->after('status');
            });
        }

        if (! Schema::hasColumn('scheduled_messages', 'sent_at')) {
            Schema::table('scheduled_messages', function (Blueprint $table) {
                $table->timestamp('sent_at')->nullable()->after('scheduled_for');
            });
        }
    }

    public function down(): void
    {
        //
    }
};