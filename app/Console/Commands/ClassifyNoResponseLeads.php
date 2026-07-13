<?php

namespace App\Console\Commands;

use App\Models\Lead;
use App\Models\LeadStatus;
use Illuminate\Console\Command;

class ClassifyNoResponseLeads extends Command
{
    protected $signature   = 'leads:classify-no-response {--hours=48 : Horas sin respuesta para clasificar}';
    protected $description = 'Marca como "No responde" y clasifica como sin_respuesta los leads en estado Contactado que recibieron una plantilla hace N horas y no contestaron';

    public function handle(): int
    {
        $hours  = (int) $this->option('hours');
        $cutoff = now()->subHours($hours);

        // Leads que:
        // 1. Recibieron un mensaje de plantilla (broadcast o desde la conversación,
        //    ScheduledMessage con message_template_id) hace al menos N horas —
        //    usamos delivered_at si lo tenemos (confirmación real de Meta), y si no
        //    llegó ese webhook, caemos a sent_at para no quedar esperando para siempre.
        // 2. No respondieron desde entonces (last_wa_inbound_at es null o anterior al corte)
        // 3. Siguen en estado "Contactado" — si el agente ya los movió a otro estado
        //    a partir de la conversación (Interesado, No quiere inmobiliaria, etc.),
        //    no los tocamos.
        $query = Lead::query()
            ->whereHas('scheduledMessages', function ($q) use ($cutoff) {
                $q->whereNotNull('message_template_id')
                  ->where('channel', 'whatsapp')
                  ->where('status', 'sent')
                  ->where(function ($q2) use ($cutoff) {
                      $q2->where('delivered_at', '<=', $cutoff)
                         ->orWhere(function ($q3) use ($cutoff) {
                             $q3->whereNull('delivered_at')->where('sent_at', '<=', $cutoff);
                         });
                  });
            })
            ->where(function ($q) use ($cutoff) {
                $q->whereNull('last_wa_inbound_at')
                  ->orWhere('last_wa_inbound_at', '<', $cutoff);
            })
            ->whereHas('leadStatus', function ($q) {
                $q->whereRaw('LOWER(TRIM(name)) = ?', ['contactado']);
            });

        $count = $query->count();

        if ($count === 0) {
            $this->line('Sin leads para clasificar.');
            return self::SUCCESS;
        }

        $this->info("Clasificando {$count} lead(s) como sin_respuesta...");

        $statusCache = [];

        $query->chunkById(100, function ($leads) use (&$statusCache) {
            foreach ($leads as $lead) {
                $statusCache[$lead->company_id] ??= LeadStatus::where('company_id', $lead->company_id)
                    ->whereRaw('LOWER(TRIM(name)) = ?', ['no responde'])
                    ->first();

                $noResponde = $statusCache[$lead->company_id];

                // update() (no updateQuietly) cuando cambia el estado, para que
                // LeadObserver dispare los flows configurados para "No responde".
                if ($noResponde) {
                    $lead->update([
                        'ai_classification' => 'sin_respuesta',
                        'ai_classified_at'  => now(),
                        'lead_status_id'    => $noResponde->id,
                    ]);
                } else {
                    $lead->updateQuietly([
                        'ai_classification' => 'sin_respuesta',
                        'ai_classified_at'  => now(),
                    ]);
                }
            }
        });

        $this->info("Listo. {$count} lead(s) clasificados.");

        return self::SUCCESS;
    }
}
