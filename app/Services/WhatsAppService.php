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

    public function testConnection(Company $company): bool
    {
        $response = Http::withToken($company->wa_access_token)
            ->get(self::API_BASE . "/{$company->wa_phone_number_id}", [
                'fields' => 'display_phone_number,verified_name',
            ]);

        return $response->successful();
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
