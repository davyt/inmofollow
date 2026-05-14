<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('message_templates', 'name')) {
            Schema::table('message_templates', function (Blueprint $table) {
                $table->string('name')->after('company_id');
            });
        }

        if (! Schema::hasColumn('message_templates', 'channel')) {
            Schema::table('message_templates', function (Blueprint $table) {
                $table->string('channel')->default('whatsapp')->after('name');
            });
        }

        if (! Schema::hasColumn('message_templates', 'subject')) {
            Schema::table('message_templates', function (Blueprint $table) {
                $table->string('subject')->nullable()->after('channel');
            });
        }

        if (! Schema::hasColumn('message_templates', 'body')) {
            Schema::table('message_templates', function (Blueprint $table) {
                $table->text('body')->after('subject');
            });
        }

        if (! Schema::hasColumn('message_templates', 'active')) {
            Schema::table('message_templates', function (Blueprint $table) {
                $table->boolean('active')->default(true)->after('body');
            });
        }
    }

    public function down(): void
    {
        Schema::table('message_templates', function (Blueprint $table) {
            $table->dropColumn([
                'name',
                'channel',
                'subject',
                'body',
                'active',
            ]);
        });
    }
};