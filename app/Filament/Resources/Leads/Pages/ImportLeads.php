<?php

namespace App\Filament\Resources\Leads\Pages;

use App\Filament\Resources\Leads\LeadResource;
use App\Models\Lead;
use App\Models\LeadStatus;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Livewire\WithFileUploads;

class ImportLeads extends Page
{
    use WithFileUploads;

    protected static string $resource = LeadResource::class;
    protected string $view = 'filament.resources.leads.pages.import-leads';
    protected static ?string $title = 'Importar Leads desde CSV';

    public $csvFile = null;
    public array $headers = [];
    public array $preview = [];
    public int $step = 1;
    public string $delimiter = ',';
    public ?array $results = null;

    public array $mapping = [
        'name'             => '',
        'phone'            => '',
        'email'            => '',
        'property_type'    => '',
        'zone'             => '',
        'source'           => '',
        'notes'            => '',
        'lead_status'      => '',
        'whatsapp_consent' => '',
        'email_consent'    => '',
        'do_not_contact'   => '',
    ];

    public static function getLeadFields(): array
    {
        return [
            'name'             => 'Nombre *',
            'phone'            => 'Teléfono',
            'email'            => 'Email',
            'property_type'    => 'Tipo de propiedad',
            'zone'             => 'Zona',
            'source'           => 'Origen',
            'notes'            => 'Observaciones',
            'lead_status'      => 'Estado',
            'whatsapp_consent' => 'Acepta WhatsApp (1/0)',
            'email_consent'    => 'Acepta Email (1/0)',
            'do_not_contact'   => 'No contactar (1/0)',
        ];
    }

    public function updatedCsvFile(): void
    {
        if (! $this->csvFile) {
            return;
        }

        try {
            $path = $this->csvFile->getRealPath();

            $firstLine = fgets(fopen($path, 'r'));
            $this->delimiter = str_contains($firstLine, ';') ? ';' : ',';

            $handle = fopen($path, 'r');
            $rawHeaders = fgetcsv($handle, 0, $this->delimiter);

            if (! $rawHeaders) {
                Notification::make()->title('El archivo no tiene cabeceras válidas')->danger()->send();
                fclose($handle);
                return;
            }

            $rawHeaders[0] = ltrim($rawHeaders[0], "\xEF\xBB\xBF");
            $this->headers = array_map('trim', $rawHeaders);

            $this->preview = [];
            for ($i = 0; $i < 3; $i++) {
                $row = fgetcsv($handle, 0, $this->delimiter);
                if ($row === false) {
                    break;
                }
                $row = array_pad($row, count($this->headers), '');
                $this->preview[] = array_combine($this->headers, $row);
            }
            fclose($handle);

            $this->autoMap();
            $this->step = 2;
        } catch (\Throwable $e) {
            Notification::make()->title('Error al leer el archivo: ' . $e->getMessage())->danger()->send();
        }
    }

    private function autoMap(): void
    {
        $aliases = [
            'name'             => ['nombre', 'name', 'cliente', 'propietario', 'contacto', 'nombres', 'razon_social'],
            'phone'            => ['telefono', 'teléfono', 'phone', 'tel', 'celular', 'movil', 'móvil', 'fono', 'whatsapp', 'numero'],
            'email'            => ['email', 'correo', 'mail', 'e_mail', 'correo_electronico'],
            'property_type'    => ['tipo', 'type', 'propiedad', 'property_type', 'tipo_propiedad', 'tipo propiedad'],
            'zone'             => ['zona', 'zone', 'barrio', 'localidad', 'ubicacion', 'ubicación', 'sector', 'ciudad', 'departamento'],
            'source'           => ['origen', 'source', 'fuente', 'canal', 'procedencia', 'medio'],
            'notes'            => ['notas', 'notes', 'observaciones', 'comentarios', 'descripcion', 'descripción', 'obs', 'detalle'],
            'lead_status'      => ['estado', 'status', 'lead_status', 'situacion', 'situación'],
            'whatsapp_consent' => ['whatsapp_consent', 'acepta_whatsapp', 'acepta_ws', 'ws_ok'],
            'email_consent'    => ['email_consent', 'acepta_email', 'email_ok'],
            'do_not_contact'   => ['no_contactar', 'do_not_contact', 'bloqueado', 'dnc'],
        ];

        foreach ($this->mapping as $field => $_) {
            $this->mapping[$field] = '';
        }

        foreach ($aliases as $field => $fieldAliases) {
            foreach ($this->headers as $header) {
                $normalized = strtolower(trim(str_replace([' ', '-', '.'], '_', $header)));
                if (in_array($normalized, $fieldAliases)) {
                    $this->mapping[$field] = $header;
                    break;
                }
            }
        }
    }

    public function import(): void
    {
        if (empty($this->mapping['name'])) {
            Notification::make()->title('Debés mapear el campo Nombre antes de importar')->danger()->send();
            return;
        }

        if (! $this->csvFile) {
            Notification::make()->title('No hay archivo para importar')->danger()->send();
            return;
        }

        $handle = fopen($this->csvFile->getRealPath(), 'r');
        $rawHeaders = fgetcsv($handle, 0, $this->delimiter);
        $rawHeaders[0] = ltrim($rawHeaders[0], "\xEF\xBB\xBF");
        $rawHeaders = array_map('trim', $rawHeaders);

        $imported = 0;
        $skipped  = 0;
        $errors   = [];
        $companyId = config('inmofollow.default_company_id', 1);
        $userId    = auth()->id();
        $boolTrue  = ['1', 'si', 'sí', 'yes', 'true', 'x', 'v'];
        $lineNumber = 1;

        $statusesByName = LeadStatus::where('company_id', $companyId)
            ->get()
            ->keyBy(fn ($status) => mb_strtolower(trim($status->name)));
        $unmatchedStatuses = [];

        while (($row = fgetcsv($handle, 0, $this->delimiter)) !== false) {
            $lineNumber++;
            $row = array_pad($row, count($rawHeaders), '');
            $rowData = array_combine($rawHeaders, $row);

            try {
                $data = [
                    'company_id' => $companyId,
                    'user_id'    => $userId,
                    'source'     => 'CSV Import',
                ];

                foreach ($this->mapping as $field => $csvColumn) {
                    if (! $csvColumn) {
                        continue;
                    }
                    $value = trim($rowData[$csvColumn] ?? '');

                    if (in_array($field, ['whatsapp_consent', 'email_consent', 'do_not_contact'])) {
                        $data[$field] = in_array(strtolower($value), $boolTrue);
                    } elseif ($field === 'lead_status') {
                        if ($value === '') {
                            continue;
                        }
                        $status = $statusesByName->get(mb_strtolower($value));
                        if ($status) {
                            $data['lead_status_id'] = $status->id;
                        } else {
                            $unmatchedStatuses[$value] = true;
                        }
                    } else {
                        $data[$field] = $value !== '' ? $value : null;
                    }
                }

                if (empty($data['name'])) {
                    $skipped++;
                    continue;
                }

                Lead::create($data);
                $imported++;
            } catch (\Throwable $e) {
                $errors[] = "Fila {$lineNumber}: " . $e->getMessage();
            }
        }
        fclose($handle);

        $this->results = [
            'imported'          => $imported,
            'skipped'           => $skipped,
            'errors'            => $errors,
            'unmatchedStatuses' => array_keys($unmatchedStatuses),
        ];
        $this->step = 3;
    }

    public function downloadTemplate(): mixed
    {
        $fields = array_keys(self::getLeadFields());
        $example = ['Juan Pérez', '098123456', 'juan@example.com', 'Apartamento', 'Pocitos', 'Portal Inmuebles', 'Interesado en 2 dorm', 'Nuevo', '1', '0', '0'];

        return response()->streamDownload(function () use ($fields, $example) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $fields);
            fputcsv($handle, $example);
            fclose($handle);
        }, 'plantilla-leads.csv', ['Content-Type' => 'text/csv']);
    }

    public function startOver(): void
    {
        $this->csvFile = null;
        $this->headers = [];
        $this->preview = [];
        $this->results = null;
        $this->step = 1;
        $this->delimiter = ',';
        $this->mapping = array_fill_keys(array_keys(self::getLeadFields()), '');
    }
}
