<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Support\Facades\Http;

class WhatsAppService
{
    private const API_BASE = 'https://graph.facebook.com/v20.0';

    public function sendTextMessage(Company $company, string $phone, string $body): string
    {
        $to = $this->formatPhone($phone);

        $response = Http::withToken($company->wa_access_token)
            ->post(self::API_BASE . "/{$company->wa_phone_number_id}/messages", [
                'messaging_product' => 'whatsapp',
                'to'               => $to,
                'type'             => 'text',
                'text'             => ['body' => $body, 'preview_url' => false],
            ]);

        if (! $response->successful()) {
            $error = $response->json('error.message', $response->body());
            throw new \RuntimeException("WhatsApp API error: {$error}");
        }

        return $response->json('messages.0.id', '');
    }

    public function sendTemplateMessage(
        Company $company,
        string $phone,
        string $templateName,
        string $language,
        array $parameters,
        ?array $header = null,
        ?string $buttonParameterValue = null,
    ): string {
        $to = $this->formatPhone($phone);

        $components = [];

        if ($header && ! empty($header['type']) && ! empty($header['link'])) {
            $components[] = [
                'type'       => 'header',
                'parameters' => [[
                    'type'         => $header['type'],
                    $header['type'] => ['link' => $header['link']],
                ]],
            ];
        }

        if (! empty($parameters)) {
            $components[] = [
                'type'       => 'body',
                'parameters' => array_map(fn ($p) => ['type' => 'text', 'text' => (string) $p], $parameters),
            ];
        }

        if ($buttonParameterValue !== null && $buttonParameterValue !== '') {
            $components[] = [
                'type'       => 'button',
                'sub_type'   => 'url',
                'index'      => '0',
                'parameters' => [['type' => 'text', 'text' => $buttonParameterValue]],
            ];
        }

        $response = Http::withToken($company->wa_access_token)
            ->post(self::API_BASE . "/{$company->wa_phone_number_id}/messages", [
                'messaging_product' => 'whatsapp',
                'to'               => $to,
                'type'             => 'template',
                'template'         => [
                    'name'       => $templateName,
                    'language'   => ['code' => $language],
                    'components' => $components,
                ],
            ]);

        if (! $response->successful()) {
            $error = $response->json('error.message', $response->body());
            throw new \RuntimeException("WhatsApp API error: {$error}");
        }

        return $response->json('messages.0.id', '');
    }

    public function getApprovedTemplates(Company $company): array
    {
        if (empty($company->wa_business_account_id)) {
            throw new \RuntimeException('Falta el WhatsApp Business Account ID. Configuralo en Mi empresa.');
        }

        $response = Http::withToken($company->wa_access_token)
            ->get(self::API_BASE . "/{$company->wa_business_account_id}/message_templates", [
                'fields' => 'name,status,language,components',
                'limit'  => 100,
            ]);

        if (! $response->successful()) {
            $error = $response->json('error.message', $response->body());
            throw new \RuntimeException("Error al obtener plantillas: {$error}");
        }

        return $response->json('data', []);
    }

    public function testConnection(Company $company): bool
    {
        $response = Http::withToken($company->wa_access_token)
            ->get(self::API_BASE . "/{$company->wa_phone_number_id}", [
                'fields' => 'display_phone_number,verified_name',
            ]);

        if (! $response->successful()) {
            $error = $response->json('error.message', $response->body());
            throw new \RuntimeException("WhatsApp API error: {$error}");
        }

        return true;
    }

    private function formatPhone(string $phone): string
    {
        $cleaned = preg_replace('/\D/', '', $phone);

        // Uruguay: 09X XXX XXX (9 digits) → 598 + 8 digits
        if (str_starts_with($cleaned, '09') && strlen($cleaned) === 9) {
            return '598' . substr($cleaned, 1);
        }

        // Uruguay: 9X XXX XXX sin el 0 (8 digits)
        if (str_starts_with($cleaned, '9') && strlen($cleaned) === 8) {
            return '598' . $cleaned;
        }

        // Already includes country code (11+ digits)
        return $cleaned;
    }
}
