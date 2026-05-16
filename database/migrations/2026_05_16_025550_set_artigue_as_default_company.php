<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $companyId = config('inmofollow.default_company_id', 1);
        $companyName = config('inmofollow.default_company_name', 'Artigue Negocios Inmobiliarios');

        if (! DB::table('companies')->where('id', $companyId)->exists()) {
            DB::table('companies')->insert([
                'id' => $companyId,
                'name' => $companyName,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            DB::table('companies')
                ->where('id', $companyId)
                ->update([
                    'name' => $companyName,
                    'active' => true,
                    'updated_at' => now(),
                ]);
        }

        foreach ([
            'users',
            'lead_statuses',
            'leads',
            'message_templates',
            'sequences',
        ] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'company_id')) {
                DB::table($table)
                    ->whereNull('company_id')
                    ->update(['company_id' => $companyId]);
            }
        }
    }

    public function down(): void
    {
        //
    }
};