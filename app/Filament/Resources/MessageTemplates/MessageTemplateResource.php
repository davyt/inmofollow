<?php

namespace App\Filament\Resources\MessageTemplates;

use App\Filament\Resources\MessageTemplates\Pages\CreateMessageTemplate;
use App\Filament\Resources\MessageTemplates\Pages\EditMessageTemplate;
use App\Filament\Resources\MessageTemplates\Pages\ListMessageTemplates;
use App\Filament\Resources\MessageTemplates\Schemas\MessageTemplateForm;
use App\Filament\Resources\MessageTemplates\Tables\MessageTemplatesTable;
use App\Models\MessageTemplate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class MessageTemplateResource extends Resource
{
    protected static ?string $model = MessageTemplate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    
    protected static ?string $modelLabel = 'plantilla de mensaje';
    
    protected static ?string $pluralModelLabel = 'plantillas de mensajes';
    
    protected static ?string $navigationLabel = 'Plantillas de mensajes';
    
    protected static ?int $navigationSort = 10;
    
    public static function getNavigationGroup(): ?string
    {
        return 'Automatizaciones';
    }

    public static function form(Schema $schema): Schema
    {
        return MessageTemplateForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MessageTemplatesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }
    
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
    
        return $query->where(function (Builder $query) use ($user) {
            $query
                ->where('scope', 'global')
                ->orWhere('user_id', $user->id);
        });
    }
    
    public static function canEdit(Model $record): bool
    {
        $user = auth()->user();
    
        if (! $user) {
            return false;
        }
    
        if ($user->isAdmin() || $user->isSupervisor()) {
            return true;
        }
    
        return $record->scope === 'personal'
            && (int) $record->user_id === (int) $user->id;
    }
    
    public static function canDelete(Model $record): bool
    {
        return static::canEdit($record);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMessageTemplates::route('/'),
            'create' => CreateMessageTemplate::route('/create'),
            'edit' => EditMessageTemplate::route('/{record}/edit'),
        ];
    }
}
