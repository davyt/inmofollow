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

class ScheduledMessageResource extends Resource
{
    protected static ?string $model = ScheduledMessage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

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
            'create' => CreateScheduledMessage::route('/create'),
            'edit' => EditScheduledMessage::route('/{record}/edit'),
        ];
    }
}
