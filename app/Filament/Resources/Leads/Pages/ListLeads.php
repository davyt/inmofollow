<?php

namespace App\Filament\Resources\Leads\Pages;

use App\Filament\Resources\Leads\LeadResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLeads extends ListRecords
{
    protected static string $resource = LeadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('import')
                ->label('Importar CSV')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('gray')
                ->url(LeadResource::getUrl('import')),
            CreateAction::make(),
        ];
    }
}
