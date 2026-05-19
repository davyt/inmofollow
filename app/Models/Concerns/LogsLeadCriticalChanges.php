<?php

namespace App\Models\Concerns;

use App\Models\LeadStatus;
use App\Models\User;
use App\Support\Activity;

trait LogsLeadCriticalChanges
{
    protected static function bootLogsLeadCriticalChanges(): void
    {
        static::updated(function ($lead) {
            if ($lead->wasChanged('lead_status_id')) {
                $old = self::leadStatusLabel($lead->getOriginal('lead_status_id'));
                $new = self::leadStatusLabel($lead->lead_status_id);

                Activity::log(
                    event: 'lead_status_changed',
                    description: "Se cambió el estado comercial del lead de '{$old}' a '{$new}'.",
                    subject: $lead,
                    properties: [
                        'old_status_id' => $lead->getOriginal('lead_status_id'),
                        'new_status_id' => $lead->lead_status_id,
                        'old_status' => $old,
                        'new_status' => $new,
                    ]
                );
            }

            if ($lead->wasChanged('user_id')) {
                $old = self::userLabel($lead->getOriginal('user_id'));
                $new = self::userLabel($lead->user_id);

                Activity::log(
                    event: 'lead_agent_changed',
                    description: "Se cambió el agente responsable del lead de '{$old}' a '{$new}'.",
                    subject: $lead,
                    properties: [
                        'old_user_id' => $lead->getOriginal('user_id'),
                        'new_user_id' => $lead->user_id,
                        'old_user' => $old,
                        'new_user' => $new,
                    ]
                );
            }

            if ($lead->wasChanged('whatsapp_consent')) {
                $old = self::boolLabel($lead->getOriginal('whatsapp_consent'));
                $new = self::boolLabel($lead->whatsapp_consent);

                Activity::log(
                    event: 'lead_whatsapp_consent_changed',
                    description: "Se cambió el consentimiento de WhatsApp de '{$old}' a '{$new}'.",
                    subject: $lead,
                    properties: [
                        'old' => $old,
                        'new' => $new,
                    ]
                );
            }

            if ($lead->wasChanged('email_consent')) {
                $old = self::boolLabel($lead->getOriginal('email_consent'));
                $new = self::boolLabel($lead->email_consent);

                Activity::log(
                    event: 'lead_email_consent_changed',
                    description: "Se cambió el consentimiento de Email de '{$old}' a '{$new}'.",
                    subject: $lead,
                    properties: [
                        'old' => $old,
                        'new' => $new,
                    ]
                );
            }

            if ($lead->wasChanged('do_not_contact')) {
                $old = self::boolLabel($lead->getOriginal('do_not_contact'));
                $new = self::boolLabel($lead->do_not_contact);

                Activity::log(
                    event: 'lead_do_not_contact_changed',
                    description: "Se cambió la marca 'No contactar' de '{$old}' a '{$new}'.",
                    subject: $lead,
                    properties: [
                        'old' => $old,
                        'new' => $new,
                    ]
                );
            }
        });
    }

    private static function leadStatusLabel($id): string
    {
        if (! $id) {
            return 'Sin estado';
        }

        return LeadStatus::find($id)?->name ?? "Estado #{$id}";
    }

    private static function userLabel($id): string
    {
        if (! $id) {
            return 'Sin agente';
        }

        return User::find($id)?->name ?? "Usuario #{$id}";
    }

    private static function boolLabel($value): string
    {
        return (bool) $value ? 'Sí' : 'No';
    }
}
