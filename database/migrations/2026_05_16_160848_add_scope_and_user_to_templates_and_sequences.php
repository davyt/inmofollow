<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('message_templates')) {
            if (! Schema::hasColumn('message_templates', 'user_id')) {
                Schema::table('message_templates', function (Blueprint $table) {
                    $table->foreignId('user_id')
                        ->nullable()
                        ->after('company_id')
                        ->constrained()
                        ->nullOnDelete();
                });
            }

            if (! Schema::hasColumn('message_templates', 'scope')) {
                Schema::table('message_templates', function (Blueprint $table) {
                    $table->string('scope')->default('global')->after('user_id');
                });
            }

            DB::table('message_templates')
                ->whereNull('scope')
                ->update(['scope' => 'global']);
        }

        if (Schema::hasTable('sequences')) {
            if (! Schema::hasColumn('sequences', 'user_id')) {
                Schema::table('sequences', function (Blueprint $table) {
                    $table->foreignId('user_id')
                        ->nullable()
                        ->after('company_id')
                        ->constrained()
                        ->nullOnDelete();
                });
            }

            if (! Schema::hasColumn('sequences', 'scope')) {
                Schema::table('sequences', function (Blueprint $table) {
                    $table->string('scope')->default('global')->after('user_id');
                });
            }

            DB::table('sequences')
                ->whereNull('scope')
                ->update(['scope' => 'global']);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('message_templates')) {
            Schema::table('message_templates', function (Blueprint $table) {
                if (Schema::hasColumn('message_templates', 'user_id')) {
                    $table->dropForeign(['user_id']);
                    $table->dropColumn('user_id');
                }

                if (Schema::hasColumn('message_templates', 'scope')) {
                    $table->dropColumn('scope');
                }
            });
        }

        if (Schema::hasTable('sequences')) {
            Schema::table('sequences', function (Blueprint $table) {
                if (Schema::hasColumn('sequences', 'user_id')) {
                    $table->dropForeign(['user_id']);
                    $table->dropColumn('user_id');
                }

                if (Schema::hasColumn('sequences', 'scope')) {
                    $table->dropColumn('scope');
                }
            });
        }
    }
};