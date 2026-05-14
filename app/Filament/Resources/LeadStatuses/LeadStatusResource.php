<?php

namespace App\Filament\Resources\LeadStatuses;

use App\Filament\Resources\LeadStatuses\Pages\CreateLeadStatus;
use App\Filament\Resources\LeadStatuses\Pages\EditLeadStatus;
use App\Filament\Resources\LeadStatuses\Pages\ListLeadStatuses;
use App\Filament\Resources\LeadStatuses\Schemas\LeadStatusForm;
use App\Filament\Resources\LeadStatuses\Tables\LeadStatusesTable;
use App\Models\LeadStatus;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class LeadStatusResource extends Resource
{
    protected static ?string $model = LeadStatus::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return LeadStatusForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LeadStatusesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLeadStatuses::route('/'),
            'create' => CreateLeadStatus::route('/create'),
            'edit' => EditLeadStatus::route('/{record}/edit'),
        ];
    }
}
