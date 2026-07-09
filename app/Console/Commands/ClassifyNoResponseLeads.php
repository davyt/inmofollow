<?php

namespace App\Console\Commands;

use App\Models\Lead;
use Illuminate\Console\Command;

class ClassifyNoResponseLeads extends Command
{
    protected $signature   = 'leads:classify-no-response {--hours=48 : Horas sin respuesta para clasificar}';
    protected $description = 'Clasifica como sin_respuesta los leads que recibieron un broadcast hace N horas y no contestaron';

    public function handle(): int
    {
        $hours  = (int) $this->option('hours');
        $cutoff = now()->subHours($hours);

        // Leads que:
        // 1. Recibieron un broadcast (ScheduledMessage con broadcast_id) hace al menos N horas
        // 2. No respondieron desde entonces (last_wa_inbound_at es null o anterior al corte)
        // 3. Aún no tienen clasificación IA
        $query = Lead::query()
            ->whereNull('ai_classification')
            ->whereHas('scheduledMessages', function ($q) use ($cutoff) {
                $q->whereNotNull('broadcast_id')
                  ->where('channel', 'whatsapp')
                  ->where('status', 'sent')
                  ->where('sent_at', '<=', $cutoff);
            })
            ->where(function ($q) use ($cutoff) {
                $q->whereNull('last_wa_inbound_at')
                  ->orWhere('last_wa_inbound_at', '<', $cutoff);
            });

        $count = $query->count();

        if ($count === 0) {
            $this->line('Sin leads para clasificar.');
            return self::SUCCESS;
        }

        $this->info("Clasificando {$count} lead(s) como sin_respuesta...");

        $query->chunkById(100, function ($leads) {
            foreach ($leads as $lead) {
                $lead->updateQuietly([
                    'ai_classification' => 'sin_respuesta',
                    'ai_classified_at'  => now(),
                ]);
            }
        });

        $this->info("Listo. {$count} lead(s) clasificados.");

        return self::SUCCESS;
    }
}
