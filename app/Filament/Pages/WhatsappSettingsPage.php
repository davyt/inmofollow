<?php

namespace App\Filament\Pages;

use App\Models\Company;
use App\Services\WhatsAppService;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class WhatsappSettingsPage extends Page
{
    protected static ?string                 $navigationLabel = 'WhatsApp / Meta';
    protected static \BackedEnum|string|null $navigationIcon  = 'heroicon-o-chat-bubble-oval-left-ellipsis';
    protected static ?string                 $title           = 'WhatsApp / Meta';
    protected static ?int                    $navigationSort  = 20;
    protected static \UnitEnum|string|null   $navigationGroup = 'Configuración';
    protected string                         $view            = 'filament.pages.whatsapp-settings';

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    // Form fields
    public bool   $waActive           = false;
    public string $waBusinessAccountId = '';
    public string $waPhoneNumberId    = '';
    public string $waAccessToken      = '';
    public string $anthropicApiKey    = '';
    public string $saveMessage        = '';
    public string $connectionStatus   = '';

    public function mount(): void
    {
        $company = Company::find(Auth::user()->company_id);
        if (! $company) return;

        $this->waActive            = (bool) $company->wa_active;
        $this->waBusinessAccountId = $company->wa_business_account_id ?? '';
        $this->waPhoneNumberId     = $company->wa_phone_number_id ?? '';
    }

    public function save(): void
    {
        $company = Company::find(Auth::user()->company_id);
        if (! $company) return;

        $data = [
            'wa_active'              => $this->waActive,
            'wa_business_account_id' => $this->waBusinessAccountId ?: null,
            'wa_phone_number_id'     => $this->waPhoneNumberId ?: null,
        ];

        if ($this->waAccessToken !== '') {
            $data['wa_access_token'] = $this->waAccessToken;
        }

        if ($this->anthropicApiKey !== '') {
            $data['anthropic_api_key'] = $this->anthropicApiKey;
        }

        $company->update($data);

        $this->waAccessToken   = '';
        $this->anthropicApiKey = '';
        $this->saveMessage     = '✓ Configuración guardada.';
    }

    public function testConnection(): void
    {
        $company = Company::find(Auth::user()->company_id);

        if (! $company?->hasWhatsApp()) {
            $this->connectionStatus = 'error:No hay credenciales configuradas o WhatsApp está desactivado.';
            return;
        }

        try {
            app(WhatsAppService::class)->testConnection($company);
            $this->connectionStatus = 'ok';
        } catch (\Throwable $e) {
            $this->connectionStatus = 'error:' . $e->getMessage();
        }
    }
}
