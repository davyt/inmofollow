<?php

use App\Http\Controllers\WhatsAppWebhookController;
use App\Models\ScheduledMessage;
use App\Models\WaInboundMessage;
use App\Support\Activity;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
    return view('welcome');
});

// WhatsApp Business API webhook (sin auth — Meta necesita acceso libre)
Route::get('/webhooks/whatsapp', [WhatsAppWebhookController::class, 'verify']);
Route::post('/webhooks/whatsapp', [WhatsAppWebhookController::class, 'receive']);

Route::middleware(['auth'])->get('/scheduled-messages/{scheduledMessage}/open-whatsapp', function (ScheduledMessage $scheduledMessage) {
    $user = auth()->user();

    if (! $user) {
        abort(403);
    }

    if ($user->isAgent() && (int) $scheduledMessage->user_id !== (int) $user->id) {
        abort(403);
    }

    $lead = $scheduledMessage->lead;

    if (! $lead || ! $lead->phone) {
        abort(404);
    }

    $phone = preg_replace('/\D+/', '', $lead->phone);

    if (str_starts_with($phone, '09')) {
        $phone = '598' . substr($phone, 1);
    }

    Activity::log(
        event: 'whatsapp_opened',
        description: 'Se abrió WhatsApp para un mensaje programado.',
        subject: $scheduledMessage,
        properties: [
            'lead_id' => $scheduledMessage->lead_id,
            'lead_name' => $lead->name,
            'phone' => $phone,
        ]
    );

    $message = rawurlencode($scheduledMessage->message_body ?? '');

    return redirect()->away("https://wa.me/{$phone}?text={$message}");
})->name('scheduled-messages.open-whatsapp');

Route::middleware(['auth'])->get('/wa-audio/{waInboundMessage}', function (WaInboundMessage $waInboundMessage) {
    $user = auth()->user();
    $lead = $waInboundMessage->lead;

    if (! $user || ! $lead || (int) $waInboundMessage->company_id !== (int) $user->company_id) {
        abort(403);
    }

    if ($user->isAgent() && (int) $lead->user_id !== (int) $user->id) {
        abort(403);
    }

    if (! $waInboundMessage->media_path || ! Storage::disk('local')->exists($waInboundMessage->media_path)) {
        abort(404);
    }

    return Storage::disk('local')->response(
        $waInboundMessage->media_path,
        headers: ['Content-Type' => $waInboundMessage->media_mime_type ?? 'audio/ogg']
    );
})->name('wa-inbound.audio');