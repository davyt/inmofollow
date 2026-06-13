<?php

namespace App\Http\Controllers;

use App\Models\ScheduledMessage;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class WhatsAppWebhookController extends Controller
{
    public function verify(Request $request): Response
    {
        $mode      = $request->query('hub_mode');
        $token     = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        if ($mode === 'subscribe' && $token === config('services.whatsapp.verify_token')) {
            return response($challenge, 200);
        }

        return response('Forbidden', 403);
    }

    public function receive(Request $request): Response
    {
        if (! $this->signatureIsValid($request)) {
            return response('Unauthorized', 401);
        }

        foreach (data_get($request->all(), 'entry', []) as $entry) {
            foreach (data_get($entry, 'changes', []) as $change) {
                foreach (data_get($change, 'value.statuses', []) as $status) {
                    $this->handleStatusUpdate($status);
                }
            }
        }

        return response('OK', 200);
    }

    private function signatureIsValid(Request $request): bool
    {
        $secret = config('services.whatsapp.app_secret');

        // Si no hay app_secret configurado, se omite la validación (útil en desarrollo)
        if (empty($secret)) {
            return true;
        }

        $signature = $request->header('X-Hub-Signature-256', '');
        $expected  = 'sha256=' . hash_hmac('sha256', $request->getContent(), $secret);

        return hash_equals($expected, $signature);
    }

    private function handleStatusUpdate(array $status): void
    {
        $waMessageId = $status['id'] ?? null;
        $statusValue = $status['status'] ?? null;

        if (! $waMessageId || ! $statusValue) {
            return;
        }

        $message = ScheduledMessage::where('wa_message_id', $waMessageId)->first();

        if (! $message) {
            return;
        }

        if ($statusValue === 'failed') {
            $errorBody = $status['errors'][0]['title'] ?? 'Error desconocido';
            $message->update([
                'status'        => 'failed',
                'error_message' => $errorBody,
            ]);
        }
    }
}
