<?php

namespace App\Filament\Pages;

use App\Models\Company;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class CompanySettingsPage extends Page
{
    protected static ?string                 $navigationLabel = 'Mi empresa';
    protected static \BackedEnum|string|null $navigationIcon  = 'heroicon-o-building-office';
    protected static ?string                 $title           = 'Mi empresa';
    protected static ?int                    $navigationSort  = 10;
    protected static \UnitEnum|string|null   $navigationGroup = 'Configuración';
    protected string                         $view            = 'filament.pages.company-settings';

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    // Form fields
    public string  $name                  = '';
    public string  $email                 = '';
    public string  $phone                 = '';
    public string  $logo                  = '';
    public bool    $active                = true;
    public string  $zoneOptions           = '';
    public string  $propertyTypeOptions   = '';
    public string  $leadSourceOptions     = '';
    public string  $saveMessage           = '';

    public function mount(): void
    {
        $company = Company::find(Auth::user()->company_id);

        if (! $company) return;

        $this->name                = $company->name ?? '';
        $this->email               = $company->email ?? '';
        $this->phone               = $company->phone ?? '';
        $this->logo                = $company->logo ?? '';
        $this->active              = (bool) $company->active;
        $this->zoneOptions         = implode("\n", $company->zone_options ?? []);
        $this->propertyTypeOptions = implode("\n", $company->property_type_options ?? []);
        $this->leadSourceOptions   = implode("\n", $company->lead_source_options ?? []);
    }

    public function save(): void
    {
        $company = Company::find(Auth::user()->company_id);
        if (! $company) return;

        $company->update([
            'name'                  => $this->name,
            'email'                 => $this->email ?: null,
            'phone'                 => $this->phone ?: null,
            'logo'                  => $this->logo ?: null,
            'active'                => $this->active,
            'zone_options'          => $this->parseOptions($this->zoneOptions),
            'property_type_options' => $this->parseOptions($this->propertyTypeOptions),
            'lead_source_options'   => $this->parseOptions($this->leadSourceOptions),
        ]);

        $this->saveMessage = '✓ Datos guardados correctamente.';
    }

    private function parseOptions(string $raw): array
    {
        return array_values(array_filter(array_map('trim', explode("\n", $raw))));
    }
}
