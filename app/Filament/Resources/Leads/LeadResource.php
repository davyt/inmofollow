<?php

namespace App\Filament\Resources\Leads;

use App\Filament\Resources\Leads\Pages\CreateLead;
use App\Filament\Resources\Leads\Pages\EditLead;
use App\Filament\Resources\Leads\Pages\ImportLeads;
use App\Filament\Resources\Leads\Pages\ListLeads;
use App\Filament\Resources\Leads\Schemas\LeadForm;
use App\Filament\Resources\Leads\Tables\LeadsTable;
use App\Models\Lead;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LeadResource extends Resource
{
    protected static ?string $model = Lead::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    
    protected static ?string $modelLabel = 'propietario / lead';
    
    protected static ?string $pluralModelLabel = 'propietarios / leads';
    
    protected static ?string $navigationLabel = 'Propietarios / Leads';
    
    protected static ?int $navigationSort = 10;
    
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
    
        $user = auth()->user();
    
        if (! $user) {
            return $query->whereRaw('1 = 0');
        }
    
        if ($user->isAdmin() || $user->isSupervisor()) {
            return $query;
        }
    
        return $query->where('user_id', $user->id);
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Seguimiento comercial';
    }

    public static function form(Schema $schema): Schema
    {
        return LeadForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LeadsTable::configure($table);
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
            'index'  => ListLeads::route('/'),
            'create' => CreateLead::route('/create'),
            'edit'   => EditLead::route('/{record}/edit'),
            'import' => ImportLeads::route('/import'),
        ];
    }
}
