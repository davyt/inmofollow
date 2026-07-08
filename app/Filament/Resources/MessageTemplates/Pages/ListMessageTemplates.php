<?php

namespace App\Filament\Resources\MessageTemplates\Pages;

use App\Filament\Resources\MessageTemplates\MessageTemplateResource;
use App\Models\Company;
use App\Models\MessageTemplate;
use App\Services\WhatsAppService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListMessageTemplates extends ListRecords
{
    protected static string $resource = MessageTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('syncFromMeta')
                ->label('Sync desde Meta')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Sincronizar plantillas desde Meta')
                ->modalDescription('Se importarán todas las plantillas de tu WABA de WhatsApp. Las que ya existen localmente solo se actualizará su estado. ¿Continuás?')
                ->modalSubmitActionLabel('Sincronizar')
                ->visible(fn () => auth()->user()?->isAdmin() || auth()->user()?->isSupervisor())
                ->action(function (): void {
                    $user    = Auth::user();
                    $company = Company::find($user->company_id);

                    if (! $company) {
                        Notification::make()->title('No se encontró la empresa.')->danger()->send();
                        return;
                    }

                    try {
                        $raw = app(WhatsAppService::class)->getApprovedTemplates($company);
                    } catch (\Throwable $e) {
                        Notification::make()->title('Error al conectar con Meta')->body($e->getMessage())->danger()->send();
                        return;
                    }

                    $synced  = 0;
                    $created = 0;

                    foreach ($raw as $t) {
                        $body   = $this->extractComponent($t['components'] ?? [], 'BODY', 'text');
                        $footer = $this->extractComponent($t['components'] ?? [], 'FOOTER', 'text');
                        $header = collect($t['components'] ?? [])->firstWhere('type', 'HEADER');

                        $metaId   = $t['id']       ?? null;
                        $name     = $t['name']      ?? '';
                        $lang     = $t['language']  ?? 'es_UY';
                        $status   = $t['status']    ?? null;

                        // Match by meta_template_id first, then by name
                        $existing = MessageTemplate::where('company_id', $company->id)
                            ->where(function ($q) use ($name, $metaId) {
                                $q->where('meta_template_name', $name)
                                  ->orWhere('meta_template_id', $metaId);
                            })
                            ->first();

                        if ($existing) {
                            $existing->update([
                                'meta_status'      => $status,
                                'meta_template_id' => $metaId,
                            ]);
                            $synced++;
                        } else {
                            MessageTemplate::create([
                                'company_id'              => $company->id,
                                'scope'                   => 'global',
                                'name'                    => $name,
                                'channel'                 => 'whatsapp',
                                'body'                    => $body ?: '',
                                'meta_template_name'      => $name,
                                'meta_template_language'  => $lang,
                                'meta_template_id'        => $metaId,
                                'meta_status'             => $status,
                                'meta_header_type'        => $header ? strtolower($header['format'] ?? '') : null,
                                'active'                  => true,
                            ]);
                            $created++;
                        }
                    }

                    Notification::make()
                        ->title("Sync completado")
                        ->body("{$created} nuevas · {$synced} actualizadas")
                        ->success()
                        ->send();
                }),

            CreateAction::make(),
        ];
    }

    private function extractComponent(array $components, string $type, string $field): ?string
    {
        return collect($components)->firstWhere('type', $type)[$field] ?? null;
    }
}
