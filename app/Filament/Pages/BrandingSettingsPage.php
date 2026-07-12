<?php

namespace App\Filament\Pages;

use App\Models\Company;
use Filament\Pages\Page;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;

class BrandingSettingsPage extends Page
{
    use WithFileUploads;

    protected static ?string                 $navigationLabel = 'Estilos';
    protected static \BackedEnum|string|null $navigationIcon  = 'heroicon-o-swatch';
    protected static ?string                 $title           = 'Estilos de marca';
    protected static ?int                    $navigationSort  = 30;
    protected static \UnitEnum|string|null   $navigationGroup = 'Configuración';
    protected string                         $view            = 'filament.pages.branding-settings';

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public ?UploadedFile $logoUpload    = null;
    public ?UploadedFile $faviconUpload = null;

    public string $primaryColor = '';
    public string $currentLogoUrl    = '';
    public string $currentFaviconUrl = '';
    public string $saveMessage       = '';

    public function mount(): void
    {
        $company = Company::find(Auth::user()->company_id);
        if (! $company) return;

        $this->primaryColor     = $company->brand_primary_color ?? '';
        $this->currentLogoUrl    = $this->urlFor($company->brand_logo_path);
        $this->currentFaviconUrl = $this->urlFor($company->brand_favicon_path);
    }

    private function urlFor(?string $path): string
    {
        return $path ? Storage::disk('branding')->url($path) : '';
    }

    public function save(): void
    {
        $company = Company::find(Auth::user()->company_id);
        if (! $company) return;

        $data = [
            'brand_primary_color' => $this->primaryColor ?: null,
        ];

        if ($this->logoUpload) {
            $ext  = $this->logoUpload->getClientOriginalExtension() ?: 'png';
            $path = "{$company->id}/logo.{$ext}";
            Storage::disk('branding')->putFileAs("{$company->id}", $this->logoUpload, "logo.{$ext}");
            $data['brand_logo_path'] = $path;
            $this->currentLogoUrl    = $this->urlFor($path);
            $this->logoUpload        = null;
        }

        if ($this->faviconUpload) {
            $ext  = $this->faviconUpload->getClientOriginalExtension() ?: 'png';
            $path = "{$company->id}/favicon.{$ext}";
            Storage::disk('branding')->putFileAs("{$company->id}", $this->faviconUpload, "favicon.{$ext}");
            $data['brand_favicon_path'] = $path;
            $this->currentFaviconUrl    = $this->urlFor($path);
            $this->faviconUpload        = null;
        }

        $company->update($data);

        $this->saveMessage = '✓ Estilos guardados. Recargá la página para ver los cambios reflejados en el panel.';
    }
}
