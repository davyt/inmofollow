<?php

namespace App\Filament\Resources\Companies\Pages;

use App\Filament\Resources\Companies\CompanyResource;
use App\Services\WhatsAppService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditCompany extends EditRecord
{
    protected static string $resource = CompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('viewTemplates')
                ->label('Ver plantillas aprobadas')
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->modalHeading('Plantillas de Meta aprobadas')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Cerrar')
                ->modalWidth('3xl')
                ->action(fn () => null)
                ->modalContent(function (): \Illuminate\Contracts\View\View {
                    $company = $this->record;
                    $templates = [];
                    $error = null;

                    try {
                        $templates = app(WhatsAppService::class)->getApprovedTemplates($company);
                    } catch (\Throwable $e) {
                        $error = $e->getMessage();
                    }

                    return view('filament.modals.meta-templates', compact('templates', 'error'));
                }),

            Action::make('testWhatsApp')
                ->label('Probar conexión WhatsApp')
                ->icon('heroicon-o-signal')
                ->color('info')
                ->action(function (): void {
                    $company = $this->record;

                    $missing = [];
                    if (empty($company->wa_phone_number_id)) {
                        $missing[] = 'Phone Number ID';
                    }
                    if (empty($company->wa_access_token)) {
                        $missing[] = 'Access Token';
                    }
                    if (! empty($missing)) {
                        Notification::make()
                            ->title('Faltan datos de configuración')
                            ->body('Completá: ' . implode(', ', $missing) . '. Guardá los cambios y volvé a probar.')
                            ->warning()
                            ->send();
                        return;
                    }
                    if (! $company->wa_active) {
                        Notification::make()
                            ->title('WhatsApp desactivado')
                            ->body('Los datos están completos, pero el toggle "Activar envío automático" está apagado. Activalo, guardá y volvé a probar.')
                            ->warning()
                            ->send();
                        return;
                    }

                    try {
                        app(WhatsAppService::class)->testConnection($company);

                        Notification::make()
                            ->title('Conexión exitosa')
                            ->body('WhatsApp está conectado y listo para enviar mensajes.')
                            ->success()
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Conexión fallida')
                            ->body($e->getMessage())
                            ->danger()
                            ->persistent()
                            ->send();
                    }
                }),

            DeleteAction::make(),
        ];
    }
}
