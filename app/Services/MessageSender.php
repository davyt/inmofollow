<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Lead;
use App\Models\MessageTemplate;
use App\Models\ScheduledMessage;
use App\Support\Activity;
use Illuminate\Database\Eloquent\Model;

class MessageSender
{
    public function __construct(private WhatsAppService $whatsApp) {}

    public function send(
        Lead $lead,
        MessageTemplate $template,
        Company $company,
        ?Model $triggeredBy = null,
    ): string {
        $useMetaTemplate = ! empty($template->meta_template_name);

        if ($useMetaTemplate) {
            $parameters = $this->resolveVariables($template->meta_template_variables ?? [], $lead, $triggeredBy);

            $waId = $this->whatsApp->sendTemplateMessage(
                $company,
                $lead->phone,
                $template->meta_template_name,
                $template->meta_template_language ?? 'es_UY',
                $parameters,
            );

            $body = $this->substituteVariables($template->body, $lead, $triggeredBy);
        } else {
            $body = $this->substituteVariables($template->body, $lead, $triggeredBy);
            $waId = $this->whatsApp->sendTextMessage($company, $lead->phone, $body);
        }

        return $waId;
    }

    public function substituteVariables(string $body, Lead $lead, ?Model $agent = null): string
    {
        return str_replace(
            ['{{nombre}}', '{{zona}}', '{{tipo_propiedad}}', '{{agente}}'],
            [
                $lead->name ?? '',
                $lead->zone ?? '',
                $lead->property_type ?? '',
                $agent?->name ?? auth()->user()?->name ?? '',
            ],
            $body,
        );
    }

    private function resolveVariables(array $variables, Lead $lead, ?Model $agent = null): array
    {
        $agentName = $agent?->name ?? auth()->user()?->name ?? '';

        return array_map(fn ($var) => match ($var) {
            'nombre'         => $lead->name ?? '',
            'zona'           => $lead->zone ?? '',
            'tipo_propiedad' => $lead->property_type ?? '',
            'agente'         => $agentName,
            default          => '',
        }, $variables);
    }
}
