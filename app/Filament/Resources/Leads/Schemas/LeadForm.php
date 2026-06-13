<?php

namespace App\Filament\Resources\Leads\Schemas;

use App\Models\Company;
use App\Models\LeadStatus;
use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class LeadForm
{
    private static function companyOptions(string $field, array $defaults = []): array
    {
        $company = Company::find(config('inmofollow.default_company_id', 1));
        $options = $company?->{$field} ?? $defaults;
        return collect($options)->mapWithKeys(fn ($v) => [$v => $v])->toArray();
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('company_id')
                ->default(fn () => config('inmofollow.default_company_id', 1))
                ->dehydrated(true),

                Select::make('user_id')
                    ->label('Agente responsable')
                    ->options(fn () => User::query()
                        ->where('active', true)
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->toArray()
                    )
                    ->searchable()
                    ->default(auth()->id())
                    ->nullable()
                    ->visible(fn () => auth()->user()?->isAdmin() || auth()->user()?->isSupervisor()),
                
                Hidden::make('user_id')
                    ->default(fn () => auth()->id())
                    ->dehydrated(true)
                    ->visible(fn () => auth()->user()?->isAgent()),

                Select::make('lead_status_id')
                    ->label('Estado')
                    ->options(fn () => LeadStatus::query()->orderBy('sort_order')->orderBy('name')->pluck('name', 'id')->toArray())
                    ->searchable()
                    ->nullable(),

                TextInput::make('name')
                    ->label('Nombre')
                    ->required(),

                TextInput::make('phone')
                    ->label('Teléfono')
                    ->tel()
                    ->default(null),

                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->default(null),

                Select::make('property_type')
                    ->label('Tipo de propiedad')
                    ->options(fn () => self::companyOptions('property_type_options'))
                    ->searchable()
                    ->nullable(),

                Select::make('zone')
                    ->label('Zona')
                    ->options(fn () => self::companyOptions('zone_options'))
                    ->searchable()
                    ->nullable(),

                Select::make('source')
                    ->label('Origen')
                    ->options(fn () => self::companyOptions('lead_source_options', ['Manual']))
                    ->searchable()
                    ->default('Manual')
                    ->nullable(),

                Textarea::make('notes')
                    ->label('Observaciones')
                    ->default(null)
                    ->columnSpanFull(),

                Toggle::make('whatsapp_consent')
                    ->label('Acepta WhatsApp')
                    ->default(false)
                    ->required(),

                Toggle::make('email_consent')
                    ->label('Acepta Email')
                    ->default(false)
                    ->required(),

                Toggle::make('do_not_contact')
                    ->label('No contactar')
                    ->default(false)
                    ->required(),

                DateTimePicker::make('last_contacted_at')
                    ->label('Último contacto'),

                DateTimePicker::make('next_follow_up_at')
                    ->label('Próximo seguimiento'),
            ]);
    }
}