<?php

namespace App\Filament\Resources\Companies\Pages;

use App\Filament\Resources\Companies\CompanyResource;
use App\Services\WhatsAppService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditCompany extends EditRecord
{
    protected static string $resource = CompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('testWhatsApp')
                ->label('Probar conexión WhatsApp')
                ->icon('heroicon-o-signal')
                ->color('info')
                ->action(function (): void {
                    $company = $this->record;

                    if (! $company->hasWhatsApp()) {
                        Notification::make()
                            ->title('WhatsApp no configurado')
                            ->body('Completá el Phone Number ID y el Access Token, guardá los cambios y volvé a probar.')
                            ->warning()
                            ->send();
                        return;
                    }

                    try {
                        $ok = app(WhatsAppService::class)->testConnection($company);

                        if ($ok) {
                            Notification::make()
                                ->title('Conexión exitosa')
                                ->body('WhatsApp está conectado y listo para enviar mensajes.')
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Conexión fallida')
                                ->body('La API no respondió correctamente. Verificá el Phone Number ID y el Access Token.')
                                ->danger()
                                ->send();
                        }
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Error al conectar')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            DeleteAction::make(),
        ];
    }
}
