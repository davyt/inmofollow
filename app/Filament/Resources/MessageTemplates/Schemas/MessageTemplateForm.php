<?php

namespace App\Filament\Resources\MessageTemplates\Schemas;

use App\Services\AiService;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use App\Models\User;

class MessageTemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('company_id')
                ->default(fn () => config('inmofollow.default_company_id', 1))
                ->dehydrated(true),

                Select::make('scope')
                    ->label('Tipo')
                    ->options([
                        'global' => 'Global',
                        'personal' => 'Personal',
                    ])
                    ->default(fn () => auth()->user()?->isAgent() ? 'personal' : 'global')
                    ->disabled(fn () => auth()->user()?->isAgent())
                    ->dehydrated(true)
                    ->required(),

                Select::make('user_id')
                    ->label('Agente dueño')
                    ->helperText('Solo aplica si la plantilla es personal.')
                    ->options(fn () => User::query()
                        ->where('active', true)
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->toArray()
                    )
                    ->searchable()
                    ->nullable()
                    ->visible(fn () => auth()->user()?->isAdmin() || auth()->user()?->isSupervisor()),

                Hidden::make('user_id')
                    ->default(fn () => auth()->id())
                    ->dehydrated(true)
                    ->visible(fn () => auth()->user()?->isAgent()),

                TextInput::make('name')
                    ->label('Nombre de la plantilla')
                    ->required(),

                Select::make('channel')
                    ->label('Canal')
                    ->options([
                        'whatsapp' => 'WhatsApp',
                        'email' => 'Email',
                    ])
                    ->default('whatsapp')
                    ->required()
                    ->live(),

                TextInput::make('subject')
                    ->label('Asunto')
                    ->helperText('Solo aplica para email.')
                    ->nullable(),

                Textarea::make('body')
                    ->label('Mensaje')
                    ->helperText('Variables disponibles: {{nombre}}, {{zona}}, {{tipo_propiedad}}, {{agente}}')
                    ->required()
                    ->rows(8)
                    ->columnSpanFull()
                    ->hintActions([
                        Action::make('generateWithAi')
                            ->label('Generar con IA')
                            ->icon('heroicon-o-sparkles')
                            ->color('warning')
                            ->modalHeading('Generar mensaje con IA')
                            ->modalDescription('Describí el tipo de mensaje que querés generar. La IA tendrá en cuenta el canal seleccionado.')
                            ->form([
                                Textarea::make('description')
                                    ->label('¿Qué tipo de mensaje querés?')
                                    ->placeholder('Ej: Primer contacto con un lead que consultó por un apartamento en Pocitos. Mencionar que tenemos varias opciones disponibles y proponer una visita.')
                                    ->required()
                                    ->rows(4),
                            ])
                            ->action(function (array $data, Set $set, Get $get): void {
                                try {
                                    $body = app(AiService::class)->generateTemplateBody(
                                        channel: $get('channel') ?? 'whatsapp',
                                        description: $data['description'],
                                    );
                                    $set('body', $body);
                                    Notification::make()
                                        ->title('Mensaje generado con IA')
                                        ->success()
                                        ->send();
                                } catch (\Throwable $e) {
                                    Notification::make()
                                        ->title('Error al generar el mensaje')
                                        ->body($e->getMessage())
                                        ->danger()
                                        ->send();
                                }
                            }),
                    ]),

                Toggle::make('active')
                    ->label('Activa')
                    ->default(true)
                    ->required(),
            ]);
    }
}
