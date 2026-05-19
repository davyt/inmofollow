<?php

use Illuminate\Support\Facades\Route;
use App\Models\ScheduledMessage;
use App\Support\Activity;

Route::get('/', function () {
    return view('welcome');
});

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