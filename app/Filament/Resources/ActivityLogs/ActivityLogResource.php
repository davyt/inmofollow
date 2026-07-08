<?php

namespace App\Filament\Resources\ActivityLogs;

use App\Filament\Resources\ActivityLogs\Pages\CreateActivityLog;
use App\Filament\Resources\ActivityLogs\Pages\EditActivityLog;
use App\Filament\Resources\ActivityLogs\Pages\ListActivityLogs;
use App\Filament\Resources\ActivityLogs\Schemas\ActivityLogForm;
use App\Filament\Resources\ActivityLogs\Tables\ActivityLogsTable;
use App\Models\ActivityLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ActivityLogResource extends Resource
{
    protected static ?string $model = ActivityLog::class;

    protected static ?string $modelLabel = 'registro de actividad';

    protected static ?string $pluralModelLabel = 'registros de actividad';
    
    protected static ?string $navigationLabel = 'Registro de actividad';
    
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static ?int $navigationSort = 60;

    public static function getNavigationGroup(): ?string
    {
        return 'Configuración';
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->isAdmin();
    }
    
    public static function canCreate(): bool
    {
        return false;
    }

    protected static ?string $recordTitleAttribute = 'event';
    
    public static function canEdit(Model $record): bool
    {
        return false;
    }
    
    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->isAdmin();
    }

    public static function form(Schema $schema): Schema
    {
        return ActivityLogForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ActivityLogsTable::configure($table);
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
            'index' => ListActivityLogs::route('/'),
            'create' => CreateActivityLog::route('/create'),
            'edit' => EditActivityLog::route('/{record}/edit'),
        ];
    }
}
