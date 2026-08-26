<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Meta reintenta la entrega del webhook si no recibe un 200 a tiempo, así que el
 * mismo mensaje entrante puede guardarse más de una vez. El corte en el
 * controlador cubre el caso normal (los reintentos llegan separados en el
 * tiempo); este índice cubre la carrera, cuando dos entregas del mismo mensaje
 * se procesan en paralelo y ninguna ve a la otra todavía.
 *
 * OJO: esta migración BORRA filas. Son duplicados exactos por `wa_message_id`
 * —el mismo mensaje de WhatsApp guardado dos o más veces— y sin eliminarlos el
 * índice único no entra. Conviene tener respaldo de la base antes de correrla.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->limpiarDuplicados();

        Schema::table('wa_inbound_messages', function (Blueprint $table) {
            $table->unique('wa_message_id');
        });
    }

    public function down(): void
    {
        Schema::table('wa_inbound_messages', function (Blueprint $table) {
            $table->dropUnique(['wa_message_id']);
        });
    }

    /**
     * Se conserva la fila más vieja de cada grupo: es la que ya tiene colgado el
     * historial (el borrador de IA que haya generado el agente sobre ella, y
     * cualquier referencia por id).
     */
    private function limpiarDuplicados(): void
    {
        $grupos = DB::table('wa_inbound_messages')
            ->select('wa_message_id', DB::raw('MIN(id) as conservar'))
            ->whereNotNull('wa_message_id')
            ->groupBy('wa_message_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($grupos->isEmpty()) {
            return;
        }

        $borradas = 0;

        foreach ($grupos as $grupo) {
            $borradas += DB::table('wa_inbound_messages')
                ->where('wa_message_id', $grupo->wa_message_id)
                ->where('id', '!=', $grupo->conservar)
                ->delete();
        }

        Log::warning('Migración: se eliminaron entrantes de WhatsApp duplicados por reintentos del webhook.', [
            'grupos' => $grupos->count(),
            'filas'  => $borradas,
        ]);
    }
};
