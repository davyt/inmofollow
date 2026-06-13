<?php

namespace App\Filament\Resources\Companies\Schemas;

use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CompanyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nombre')
                    ->required(),
                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->default(null),
                TextInput::make('phone')
                    ->label('Teléfono')
                    ->tel()
                    ->default(null),
                TextInput::make('logo')
                    ->label('Logo (URL)')
                    ->default(null),
                Toggle::make('active')
                    ->label('Activa')
                    ->required(),

                // ── WhatsApp Business API ──────────────────────────────────

                Toggle::make('wa_active')
                    ->label('WhatsApp: Activar envío automático')
                    ->helperText('Activá solo cuando tengas configuradas las credenciales de abajo.')
                    ->default(false),

                TextInput::make('wa_phone_number_id')
                    ->label('WhatsApp: Phone Number ID')
                    ->helperText('Meta for Developers → Tu App → WhatsApp → Configuración de la API → Phone Number ID')
                    ->nullable(),

                TextInput::make('wa_access_token')
                    ->label('WhatsApp: Access Token')
                    ->helperText('Token de acceso permanente. Meta for Developers → Tu App → WhatsApp → Configuración de la API')
                    ->password()
                    ->nullable()
                    ->dehydrated(fn ($state) => filled($state)),

                Placeholder::make('wa_webhook_url')
                    ->label('WhatsApp: Webhook URL (copiar en Meta)')
                    ->content(fn () => url('/webhooks/whatsapp')),

                Placeholder::make('wa_verify_token')
                    ->label('WhatsApp: Webhook Verify Token (copiar en Meta)')
                    ->content(fn () => config('services.whatsapp.verify_token')),

                // ── Listas de opciones para leads ─────────────────────────

                Section::make('Opciones de leads')
                    ->description('Configurá las listas que aparecen al cargar o editar un lead.')
                    ->collapsible()
                    ->schema([
                        TagsInput::make('zone_options')
                            ->label('Zonas disponibles')
                            ->helperText('Escribí cada zona y presioná Enter para agregarla.')
                            ->placeholder('Ej: Pocitos, Carrasco, Centro...')
                            ->columnSpanFull(),

                        TagsInput::make('property_type_options')
                            ->label('Tipos de propiedad')
                            ->helperText('Escribí cada tipo y presioná Enter para agregarlo.')
                            ->placeholder('Ej: Apartamento, Casa, Local comercial...')
                            ->columnSpanFull(),

                        TagsInput::make('lead_source_options')
                            ->label('Orígenes de lead')
                            ->helperText('Escribí cada origen y presioná Enter para agregarlo.')
                            ->placeholder('Ej: Manual, 2clics, MercadoLibre, Referido...')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
