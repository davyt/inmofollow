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

                Placeholder::make('meta_status_agente')
                    ->label('Primer contacto por WhatsApp')
                    ->content(fn (Get $get): string => filled($get('meta_template_name'))
                        ? '✓ Aprobada por Meta — se puede enviar a cualquier lead, incluso si nunca te escribió.'
                        : 'No aprobada por Meta — solo se puede enviar si el lead te escribió en las últimas 24hs.')
                    ->visible(fn (Get $get): bool => $get('channel') === 'whatsapp' && auth()->user()?->isAgent()),

                Section::make('Primer contacto por WhatsApp (plantilla aprobada por Meta)')
                    ->description('Completá esta sección si querés poder enviar este mensaje a leads que nunca te escribieron, o que no respondieron hace más de 24hs.')
                    ->collapsible()
                    ->collapsed(fn (Get $get): bool => empty($get('meta_template_name')))
                    ->visible(fn (Get $get): bool => $get('channel') === 'whatsapp' && ! auth()->user()?->isAgent())
                    ->schema([
                        Placeholder::make('meta_info')
                            ->label('¿Cómo funciona?')
                            ->content(
                                "WhatsApp solo permite enviar mensajes libremente a personas que te escribieron en las últimas 24hs. " .
                                "Para contactar a alguien por primera vez (o después de 24hs sin respuesta), Meta exige usar una plantilla que ellos aprobaron previamente.\n\n" .
                                "Si completás esta sección, el sistema usará automáticamente esa plantilla aprobada cuando sea necesario. " .
                                "Si no la completás, el mensaje solo funcionará con leads que ya están en conversación activa con vos.\n\n" .
                                "Pasos para configurarlo:\n" .
                                "1. Entrá a Meta Business Manager → WhatsApp → Plantillas de mensajes\n" .
                                "2. Creá una plantilla con el mismo texto que tiene este mensaje. Usá {{1}}, {{2}}, etc. donde van los datos del lead (nombre, zona, etc.)\n" .
                                "3. Esperá que Meta la apruebe (normalmente 24-48hs)\n" .
                                "4. Volvé aquí y completá los campos de abajo con el nombre exacto de esa plantilla"
                            ),

                        TextInput::make('meta_template_name')
                            ->label('Nombre de la plantilla en Meta')
                            ->helperText('Copiá exactamente el nombre que aparece en Meta Business Manager. Ej: primer_contacto_pocitos')
                            ->placeholder('primer_contacto')
                            ->nullable(),

                        Select::make('meta_template_language')
                            ->label('Idioma')
                            ->options([
                                'es_UY' => 'Español (Uruguay)',
                                'es_AR' => 'Español (Argentina)',
                                'es'    => 'Español (genérico)',
                                'en_US' => 'English (US)',
                            ])
                            ->default('es_UY')
                            ->required(),

                        TagsInput::make('meta_template_variables')
                            ->label('Variables en orden')
                            ->helperText('Indicá en qué orden pusiste las variables en Meta. Si en Meta escribiste "Hola {{1}}, te contactamos por {{2}}", ponés: nombre → zona. Cada variable aquí corresponde a un {{número}} allá.')
                            ->suggestions(['nombre', 'zona', 'tipo_propiedad', 'agente'])
                            ->placeholder('Ej: nombre')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
