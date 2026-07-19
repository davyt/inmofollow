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
        return ($path && Storage::disk('branding')->exists($path))
            ? Storage::disk('branding')->url($path)
            : '';
    }

    public function save(): void
    {
        $company = Company::find(Auth::user()->company_id);
        if (! $company) return;

        $data = [
            'brand_primary_color' => $this->primaryColor ?: null,
        ];

        if ($this->logoUpload) {
            $data['brand_logo_path'] = $this->storeVersioned($company, $this->logoUpload, 'logo', $company->brand_logo_path);
            $this->currentLogoUrl    = $this->urlFor($data['brand_logo_path']);
            $this->logoUpload        = null;
        }

        if ($this->faviconUpload) {
            $data['brand_favicon_path'] = $this->storeVersioned($company, $this->faviconUpload, 'favicon', $company->brand_favicon_path);
            $this->currentFaviconUrl    = $this->urlFor($data['brand_favicon_path']);
            $this->faviconUpload        = null;
        }

        $company->update($data);

        $this->saveMessage = '✓ Estilos guardados.';
    }

    /**
     * Guarda con un nombre de archivo único por subida (en vez de pisar
     * siempre logo.png/favicon.png) y borra el archivo anterior. El hosting
     * sirve /branding/* detrás de una CDN que cachea por URL hasta 7 días e
     * ignora el query string al cachear estáticos, así que la única forma
     * confiable de que la imagen nueva se vea es que la URL (el nombre de
     * archivo) cambie de verdad.
     */
    private function storeVersioned(Company $company, UploadedFile $file, string $baseName, ?string $previousPath): string
    {
        $ext      = $file->getClientOriginalExtension() ?: 'png';
        $filename = "{$baseName}-" . now()->timestamp . ".{$ext}";
        $path     = "{$company->id}/{$filename}";

        Storage::disk('branding')->putFileAs((string) $company->id, $file, $filename);

        if ($previousPath && $previousPath !== $path) {
            Storage::disk('branding')->delete($previousPath);
        }

        return $path;
    }
}
