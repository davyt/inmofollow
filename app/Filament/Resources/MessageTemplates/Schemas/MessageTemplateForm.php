<?php

namespace App\Filament\Resources\MessageTemplates\Schemas;

use App\Models\User;
use App\Services\AiService;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

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
                    ->helperText('Requerido para email. No aplica para WhatsApp.')
                    ->required(fn (Get $get): bool => $get('channel') === 'email')
                    ->nullable(),

                Textarea::make('body')
                    ->label('Mensaje')
                    ->helperText('Variables disponibles: {{nombre}}, {{zona}}, {{tipo_propiedad}}, {{agente}}')
                    ->required()
                    ->rows(8)
                    ->columnSpanFull()
                    ->hintActions([
                        Action::make('previewTemplate')
                            ->label('Previsualizar')
                            ->icon('heroicon-o-eye')
                            ->color('gray')
                            ->modalHeading('Vista previa del mensaje')
                            ->modalDescription('Variables sustituidas con datos de ejemplo.')
                            ->modalSubmitAction(false)
                            ->modalCancelActionLabel('Cerrar')
                            ->form(function (Get $get): array {
                                $body = $get('body') ?: '';
                                $preview = str_replace(
                                    ['{{nombre}}', '{{zona}}', '{{tipo_propiedad}}', '{{agente}}'],
                                    ['María García', 'Pocitos', 'Apartamento', 'Carlos Rodríguez'],
                                    $body,
                                );
                                return [
                                    Placeholder::make('preview_content')
                                        ->label('Mensaje con datos de ejemplo')
                                        ->content($preview ?: 'Escribí el cuerpo del mensaje primero.'),
                                ];
                            })
                            ->action(fn () => null),

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

                Section::make('Plantilla aprobada de Meta (WhatsApp)')
                    ->description('Necesario para enviar el primer mensaje a un lead o cuando pasaron más de 24hs sin respuesta.')
                    ->collapsible()
                    ->collapsed(fn (Get $get): bool => empty($get('meta_template_name')))
                    ->visible(fn (Get $get): bool => $get('channel') === 'whatsapp')
                    ->schema([
                        Placeholder::make('meta_info')
                            ->label('')
                            ->content(
                                "Para contactar leads que nunca te escribieron (o que no respondieron en 24hs), WhatsApp exige usar una plantilla pre-aprobada por Meta.\n\n" .
                                "Pasos:\n" .
                                "1. Creá la plantilla en Meta Business Manager → WhatsApp → Plantillas de mensajes\n" .
                                "2. Usá {{1}}, {{2}}, etc. para las variables (ej: \"Hola {{1}}, te contactamos por {{2}}\")\n" .
                                "3. Esperá la aprobación de Meta (24-48hs normalmente)\n" .
                                "4. Completá los campos de abajo con el nombre exacto de la plantilla y el orden de las variables"
                            ),

                        TextInput::make('meta_template_name')
                            ->label('Nombre de la plantilla en Meta')
                            ->helperText('Exactamente como aparece en Meta Business Manager (minúsculas, sin espacios). Ej: primer_contacto')
                            ->placeholder('primer_contacto')
                            ->nullable(),

                        Select::make('meta_template_language')
                            ->label('Idioma de la plantilla')
                            ->options([
                                'es_UY' => 'Español (Uruguay)',
                                'es_AR' => 'Español (Argentina)',
                                'es'    => 'Español (genérico)',
                                'en_US' => 'English (US)',
                            ])
                            ->default('es_UY')
                            ->required(),

                        TagsInput::make('meta_template_variables')
                            ->label('Variables en orden ({{1}}, {{2}}, ...)')
                            ->helperText('Agregá las variables de InmoFollow en el mismo orden que las pusiste en Meta. La 1ra = {{1}}, la 2da = {{2}}, etc.')
                            ->suggestions(['nombre', 'zona', 'tipo_propiedad', 'agente'])
                            ->placeholder('Ej: nombre')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
