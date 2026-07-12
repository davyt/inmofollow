<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('leads', 'last_message_at')) {
            Schema::table('leads', function (Blueprint $table) {
                $table->timestamp('last_message_at')->nullable()->after('last_wa_inbound_at');
                $table->string('last_message_preview', 160)->nullable()->after('last_message_at');
                $table->string('last_message_direction', 3)->nullable()->after('last_message_preview');
                $table->index(['company_id', 'last_message_at']);
            });
        }

        // Backfill: para leads con historial previo, calcular el último mensaje
        // (in/out) a partir de wa_inbound_messages / scheduled_messages, para
        // que no desaparezcan de Conversaciones al pasar a usar las columnas nuevas.
        $leads = DB::table('leads')->select('id')->get();

        foreach ($leads as $lead) {
            $lastIn = DB::table('wa_inbound_messages')
                ->where('lead_id', $lead->id)
                ->orderByDesc('received_at')
                ->first(['body', 'received_at']);

            $lastOut = DB::table('scheduled_messages')
                ->where('lead_id', $lead->id)
                ->where('status', 'sent')
                ->whereNotNull('sent_at')
                ->orderByDesc('sent_at')
                ->first(['message_body', 'sent_at']);

            $inAt  = $lastIn?->received_at;
            $outAt = $lastOut?->sent_at;

            if (! $inAt && ! $outAt) {
                continue;
            }

            $useIn = $inAt && (! $outAt || $inAt > $outAt);

            DB::table('leads')->where('id', $lead->id)->update([
                'last_message_at'        => $useIn ? $inAt : $outAt,
                'last_message_preview'   => Str::limit($useIn ? ($lastIn->body ?? '') : ($lastOut->message_body ?? ''), 150),
                'last_message_direction' => $useIn ? 'in' : 'out',
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'last_message_at']);
            $table->dropColumn(['last_message_at', 'last_message_preview', 'last_message_direction']);
        });
    }
};
