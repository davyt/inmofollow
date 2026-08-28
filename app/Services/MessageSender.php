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
    /**
     * Valor que se manda cuando una variable de plantilla no tiene dato.
     * Meta rechaza el envío si un parámetro llega vacío, así que nunca
     * puede salir un string en blanco de resolveVariables().
     */
    private const EMPTY_PLACEHOLDER = '—';

    public function __construct(private WhatsAppService $whatsApp) {}

    /**
     * @param  string|null  $toPhone  Teléfono destino alternativo. Por defecto el del lead;
     *                                los pasos "plantilla a agente" lo usan para mandarle la
     *                                ficha al agente en vez de al contacto.
     */
    public function send(
        Lead $lead,
        MessageTemplate $template,
        Company $company,
        ?Model $triggeredBy = null,
        ?string $toPhone = null,
    ): string {
        $destino         = $toPhone ?: $lead->phone;
        $useMetaTemplate = ! empty($template->meta_template_name);

        if ($useMetaTemplate) {
            $parameters = $this->resolveVariables($template->meta_template_variables ?? [], $lead, $triggeredBy);

            $header = $template->meta_header_type
                ? ['type' => $template->meta_header_type, 'link' => $template->meta_header_media_url]
                : null;

            $buttonValue = $template->meta_button_variable
                ? ($this->resolveVariables([$template->meta_button_variable], $lead, $triggeredBy)[0] ?? null)
                : null;

            $waId = $this->whatsApp->sendTemplateMessage(
                $company,
                $destino,
                $template->meta_template_name,
                $template->meta_template_language ?? 'es_UY',
                $parameters,
                $header,
                $buttonValue,
            );

            $body = $this->substituteVariables($template->body, $lead, $triggeredBy);
        } else {
            $body = $this->substituteVariables($template->body, $lead, $triggeredBy);
            $waId = $this->whatsApp->sendTextMessage($company, $destino, $body);
        }

        return $waId;
    }

    public function substituteVariables(string $body, Lead $lead, ?Model $agent = null): string
    {
        $valores = $this->variableValues($lead, $agent, placeholder: '');

        return str_replace(
            array_map(fn ($k) => '{{' . $k . '}}', array_keys($valores)),
            array_values($valores),
            $body,
        );
    }

    /**
     * Traduce los nombres de variable configurados en la plantilla a los valores
     * del lead, en el mismo orden en que se cargaron ({{1}}, {{2}}, ...).
     */
    public function resolveVariables(array $variables, Lead $lead, ?Model $agent = null): array
    {
        $valores = $this->variableValues($lead, $agent, placeholder: self::EMPTY_PLACEHOLDER);

        return array_map(
            fn ($var) => $valores[$var] ?? self::EMPTY_PLACEHOLDER,
            $variables,
        );
    }

    /**
     * Vocabulario único de variables. Lo comparten el texto libre y las plantillas
     * de Meta para que una plantilla se comporte igual por los dos caminos.
     *
     * Los saltos de línea y tabulaciones se aplastan porque Meta rechaza los
     * parámetros que los contienen.
     */
    private function variableValues(Lead $lead, ?Model $agent, string $placeholder): array
    {
        $limpiar = fn (?string $v) => $placeholder === ''
            ? (string) preg_replace('/\s+/u', ' ', trim((string) $v))
            : (trim((string) $v) === '' ? $placeholder : preg_replace('/\s+/u', ' ', trim((string) $v)));

        return [
            'id'             => (string) $lead->id,
            'nombre'         => $limpiar($lead->name),
            'telefono'       => $limpiar($lead->phone),
            'email'          => $limpiar($lead->email),
            'zona'           => $limpiar($lead->zone),
            'tipo_propiedad' => $limpiar($lead->property_type),
            'estado'         => $limpiar($lead->leadStatus?->name),
            'clasificacion'  => $limpiar($lead->ai_classification),
            'origen'         => $limpiar($lead->source),
            'agente'         => $limpiar($agent?->name ?? $lead->user?->name ?? auth()->user()?->name),
        ];
    }
}
