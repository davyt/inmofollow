<?php

namespace App\Livewire;

use App\Models\Company;
use App\Models\Lead;
use App\Models\MessageTemplate;
use App\Models\ScheduledMessage;
use App\Models\WaInboundMessage;
use App\Services\MessageSender;
use App\Services\WhatsAppService;
use App\Support\Activity;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;
use Livewire\Component;

class LeadConversation extends Component implements HasSchemas
{
    use InteractsWithSchemas;

    public Lead $lead;

    public ?array $data = [];

    public function mount(Lead $lead): void
    {
        $this->lead = $lead;
        $this->form->fill(['mode' => 'template']);
    }

    public function isContactAllowed(): bool
    {
        return $this->lead->whatsapp_consent
            && ! $this->lead->do_not_contact
            && filled($this->lead->phone);
    }

    public function form(Schema $schema): Schema
    {
        if (! $this->isContactAllowed()) {
            return $schema
                ->components([
                    Placeholder::make('sin_permiso')
                        ->hiddenLabel()
                        ->content('No se le pueden enviar mensajes a este lead: falta el teléfono, no dio consentimiento de WhatsApp, o está marcado como "no contactar".'),
                ])
                ->statePath('data');
        }

        $hasSession = $this->lead->hasActiveWhatsAppSession();

        $templateOptions = MessageTemplate::query()
            ->where('channel', 'whatsapp')
            ->where('active', true)
            ->when(! $hasSession, fn ($q) => $q->whereNotNull('meta_template_name')->where('meta_template_name', '!=', ''))
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();

        if (empty($templateOptions) && ! $hasSession) {
            return $schema
                ->components([
                    Placeholder::make('sin_opciones')
                        ->hiddenLabel()
                        ->content('Este lead no te escribió en las últimas 24hs y no hay plantillas configuradas para primer contacto. Pedile al administrador que configure una plantilla de primer contacto.'),
                ])
                ->statePath('data');
        }

        $components = [];

        if ($hasSession) {
            $components[] = Radio::make('mode')
                ->hiddenLabel()
                ->options([
                    'template' => 'Usar plantilla',
                    'freetext' => 'Mensaje personalizado',
                ])
                ->helperText('El mensaje personalizado solo se puede enviar porque este lead te escribió hace menos de 24hs. Pasado ese tiempo vas a necesitar una plantilla aprobada.')
                ->inline()
                ->inlineLabel(false)
                ->live()
                ->default('template');
        }

        $components[] = Select::make('message_template_id')
            ->label('Plantilla')
            ->options($templateOptions)
            ->searchable()
            ->visible(fn (Get $get): bool => ! $hasSession || $get('mode') !== 'freetext')
            ->required(fn (Get $get): bool => ! $hasSession || $get('mode') !== 'freetext');

        if ($hasSession) {
            $components[] = Textarea::make('free_text')
                ->label('Mensaje')
                ->rows(3)
                ->visible(fn (Get $get): bool => $get('mode') === 'freetext')
                ->required(fn (Get $get): bool => $get('mode') === 'freetext');
        }

        return $schema->components($components)->statePath('data');
    }

    public function send(): void
    {
        if (! $this->isContactAllowed()) {
            return;
        }

        $data = $this->form->getState();

        $company = Company::find($this->lead->company_id);

        if (! $company?->hasWhatsApp()) {
            Notification::make()
                ->title('WhatsApp no configurado')
                ->body('Pedile al administrador que configure las credenciales de WhatsApp.')
                ->danger()
                ->send();
            return;
        }

        $isFreeText = ($data['mode'] ?? 'template') === 'freetext' && $this->lead->hasActiveWhatsAppSession();

        try {
            if ($isFreeText) {
                $body = $data['free_text'];
                $waId = app(WhatsAppService::class)->sendTextMessage($company, $this->lead->phone, $body);

                ScheduledMessage::create([
                    'lead_id'       => $this->lead->id,
                    'user_id'       => auth()->id(),
                    'channel'       => 'whatsapp',
                    'message_body'  => $body,
                    'status'        => 'sent',
                    'scheduled_for' => now(),
                    'sent_at'       => now(),
                    'wa_message_id' => $waId ?: null,
                ]);
            } else {
                $template = MessageTemplate::findOrFail($data['message_template_id']);
                $sender   = app(MessageSender::class);
                $waId     = $sender->send($this->lead, $template, $company);
                $body     = $sender->substituteVariables($template->body, $this->lead);

                ScheduledMessage::create([
                    'lead_id'             => $this->lead->id,
                    'message_template_id' => $template->id,
                    'user_id'             => auth()->id(),
                    'channel'             => 'whatsapp',
                    'message_body'        => $body,
                    'status'              => 'sent',
                    'scheduled_for'       => now(),
                    'sent_at'             => now(),
                    'wa_message_id'       => $waId ?: null,
                ]);
            }

            $this->lead->update(['last_contacted_at' => now()]);

            Activity::log(
                event: 'whatsapp_sent_now',
                description: 'Mensaje enviado inmediatamente por WhatsApp.',
                subject: $this->lead,
            );

            $this->form->fill(['mode' => 'template']);

            Notification::make()->title('Mensaje enviado')->success()->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Error al enviar')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function getConversation(): Collection
    {
        $sent = $this->lead->scheduledMessages()
            ->where('channel', 'whatsapp')
            ->whereIn('status', ['sent', 'failed'])
            ->get()
            ->map(fn ($m) => [
                'type'      => 'message',
                'direction' => 'out',
                'date'      => $m->sent_at ?? $m->scheduled_for ?? $m->created_at,
                'status'    => $m->status,
                'text'      => $m->message_body,
            ]);

        $received = $this->lead->waInboundMessages()
            ->get()
            ->flatMap(fn ($m) => array_filter([
                [
                    'type'      => 'message',
                    'direction' => 'in',
                    'date'      => $m->received_at ?? $m->created_at,
                    'status'    => null,
                    'text'      => $m->body ?: '[' . ucfirst($m->message_type) . ']',
                ],
                $m->ai_draft_reply && ! $m->ai_draft_discarded ? [
                    'type'       => 'ai_draft',
                    'direction'  => 'out',
                    'date'       => ($m->received_at ?? $m->created_at)->addSecond(),
                    'status'     => null,
                    'text'       => $m->ai_draft_reply,
                    'message_id' => $m->id,
                ] : null,
            ]));

        return $sent->concat($received)->sortBy('date')->values();
    }

    public function sendAiDraft(int $messageId): void
    {
        $msg = WaInboundMessage::where('lead_id', $this->lead->id)->find($messageId);
        if (! $msg?->ai_draft_reply) return;

        $company = Company::find($this->lead->company_id);
        if (! $company?->hasWhatsApp()) return;

        try {
            $waId = app(WhatsAppService::class)->sendTextMessage($company, $this->lead->phone, $msg->ai_draft_reply);

            ScheduledMessage::create([
                'lead_id'       => $this->lead->id,
                'user_id'       => auth()->id(),
                'channel'       => 'whatsapp',
                'message_body'  => $msg->ai_draft_reply,
                'status'        => 'sent',
                'scheduled_for' => now(),
                'sent_at'       => now(),
                'wa_message_id' => $waId,
            ]);

            $msg->update(['ai_draft_reply' => null, 'ai_draft_discarded' => true]);
            $this->lead->update(['last_contacted_at' => now()]);

            Notification::make()->title('Respuesta enviada')->success()->send();
        } catch (\Throwable $e) {
            Notification::make()->title('Error al enviar')->body($e->getMessage())->danger()->send();
        }
    }

    public function discardAiDraft(int $messageId): void
    {
        WaInboundMessage::where('lead_id', $this->lead->id)
            ->find($messageId)
            ?->update(['ai_draft_discarded' => true]);
    }

    public function canSend(): bool
    {
        if (! $this->isContactAllowed()) {
            return false;
        }

        if ($this->lead->hasActiveWhatsAppSession()) {
            return true;
        }

        return MessageTemplate::query()
            ->where('channel', 'whatsapp')
            ->where('active', true)
            ->whereNotNull('meta_template_name')
            ->where('meta_template_name', '!=', '')
            ->exists();
    }

    public function render()
    {
        return view('livewire.lead-conversation', [
            'conversation' => $this->getConversation(),
            'canSend'      => $this->canSend(),
        ]);
    }
}
