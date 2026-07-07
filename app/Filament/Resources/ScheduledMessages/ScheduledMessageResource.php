<?php

namespace App\Filament\Resources\ScheduledMessages;

use App\Filament\Resources\ScheduledMessages\Pages\CreateScheduledMessage;
use App\Filament\Resources\ScheduledMessages\Pages\EditScheduledMessage;
use App\Filament\Resources\ScheduledMessages\Pages\ListScheduledMessages;
use App\Filament\Resources\ScheduledMessages\Schemas\ScheduledMessageForm;
use App\Filament\Resources\ScheduledMessages\Tables\ScheduledMessagesTable;
use App\Models\ScheduledMessage;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ScheduledMessageResource extends Resource
{
    protected static ?string $model = ScheduledMessage::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected static ?string $modelLabel = 'envío';

    protected static ?string $pluralModelLabel = 'historial de envíos';

    protected static ?string $navigationLabel = 'Historial de envíos';

    protected static ?int $navigationSort = 30;
    
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
        return 'Automatización';
    }

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return ScheduledMessageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ScheduledMessagesTable::configure($table);
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
            'index' => ListScheduledMessages::route('/'),
        ];
    }
}
