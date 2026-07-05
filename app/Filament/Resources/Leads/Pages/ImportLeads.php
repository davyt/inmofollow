<?php

namespace App\Filament\Resources\Leads\Pages;

use App\Filament\Resources\Leads\LeadResource;
use App\Models\Lead;
use App\Models\LeadImportProfile;
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

    public ?int $selectedProfileId = null;
    public ?string $defaultSource = null;
    public string $newProfileName = '';

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

            if (! $this->isXlsx()) {
                $firstLine = fgets(fopen($path, 'r'));
                $this->delimiter = str_contains($firstLine, ';') ? ';' : ',';
            }

            $allRows = $this->readAllRows($path);

            if (empty($allRows)) {
                Notification::make()->title('El archivo no tiene datos')->danger()->send();
                return;
            }

            $headerRowIndex = $this->detectHeaderRowIndex($allRows);
            $rawHeaders = $allRows[$headerRowIndex];
            $rawHeaders[0] = ltrim($rawHeaders[0] ?? '', "\xEF\xBB\xBF");
            $this->headers = array_map(fn ($h) => trim((string) $h), $rawHeaders);

            $dataRows = array_slice($allRows, $headerRowIndex + 1);

            $this->preview = [];
            foreach (array_slice($dataRows, 0, 3) as $row) {
                $row = array_pad($row, count($this->headers), '');
                $this->preview[] = array_combine($this->headers, array_slice($row, 0, count($this->headers)));
            }

            $this->autoMap();
            $this->step = 2;
        } catch (\Throwable $e) {
            Notification::make()->title('Error al leer el archivo: ' . $e->getMessage())->danger()->send();
        }
    }

    private function isXlsx(): bool
    {
        $extension = strtolower($this->csvFile->getClientOriginalExtension() ?: pathinfo($this->csvFile->getClientOriginalName(), PATHINFO_EXTENSION));

        return in_array($extension, ['xlsx', 'xlsm'], true);
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function readAllRows(string $path): array
    {
        if ($this->isXlsx()) {
            return $this->readXlsxRows($path);
        }

        $handle = fopen($path, 'r');
        $rows = [];
        while (($row = fgetcsv($handle, 0, $this->delimiter)) !== false) {
            $rows[] = $row;
        }
        fclose($handle);

        return $rows;
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function readXlsxRows(string $path): array
    {
        $zip = new \ZipArchive();

        if ($zip->open($path) !== true) {
            throw new \RuntimeException('No se pudo abrir el archivo Excel.');
        }

        $sharedStrings = [];
        $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedStringsXml !== false) {
            $xml = simplexml_load_string($sharedStringsXml);
            foreach ($xml->si as $si) {
                if (isset($si->t)) {
                    $sharedStrings[] = (string) $si->t;
                } else {
                    $text = '';
                    foreach ($si->r as $run) {
                        $text .= (string) $run->t;
                    }
                    $sharedStrings[] = $text;
                }
            }
        }

        $sheetContent = $zip->getFromName($this->firstSheetPath($zip));
        $zip->close();

        if ($sheetContent === false) {
            throw new \RuntimeException('No se pudo leer la primera hoja del archivo Excel.');
        }

        $sheet = simplexml_load_string($sheetContent);
        $rows = [];

        foreach ($sheet->sheetData->row as $row) {
            $cells = [];
            $maxCol = 0;

            foreach ($row->c as $c) {
                $colIdx = $this->colToIndex((string) $c['r']);
                $maxCol = max($maxCol, $colIdx);
                $type = (string) $c['t'];
                $value = isset($c->v) ? (string) $c->v : '';

                if ($type === 's' && $value !== '') {
                    $value = $sharedStrings[(int) $value] ?? '';
                } elseif ($type === 'inlineStr' && isset($c->is->t)) {
                    $value = (string) $c->is->t;
                }

                $cells[$colIdx] = $value;
            }

            $plainRow = [];
            for ($i = 0; $i <= $maxCol; $i++) {
                $plainRow[] = $cells[$i] ?? '';
            }

            $rows[] = $plainRow;
        }

        return $rows;
    }

    private function firstSheetPath(\ZipArchive $zip): string
    {
        try {
            $workbookXml = $zip->getFromName('xl/workbook.xml');
            $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');

            if ($workbookXml !== false && $relsXml !== false) {
                $workbook = simplexml_load_string($workbookXml);
                $rels = simplexml_load_string($relsXml);

                $firstSheet = $workbook->sheets->sheet[0] ?? null;

                if ($firstSheet) {
                    $rId = (string) $firstSheet->attributes('r', true)->id;

                    foreach ($rels->Relationship as $rel) {
                        if ((string) $rel['Id'] === $rId) {
                            return 'xl/' . ltrim((string) $rel['Target'], '/');
                        }
                    }
                }
            }
        } catch (\Throwable) {
            // Fallback abajo
        }

        return 'xl/worksheets/sheet1.xml';
    }

    private function colToIndex(string $ref): int
    {
        $col = preg_replace('/\d+/', '', $ref);
        $index = 0;
        foreach (str_split($col) as $char) {
            $index = $index * 26 + (ord($char) - ord('A') + 1);
        }

        return $index - 1;
    }

    /**
     * Algunos exports (ej. 2clics) tienen una fila de título fusionada antes de la
     * cabecera real. Saltea filas con una sola celda con contenido hasta encontrar
     * la primera fila con varias columnas completas.
     *
     * @param  array<int, array<int, string>>  $rows
     */
    private function detectHeaderRowIndex(array $rows): int
    {
        foreach ($rows as $index => $row) {
            $filled = count(array_filter($row, fn ($value) => trim((string) $value) !== ''));

            if ($filled > 1) {
                return $index;
            }
        }

        return 0;
    }

    public function availableProfiles(): array
    {
        return LeadImportProfile::where('company_id', config('inmofollow.default_company_id', 1))
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    public function updatedSelectedProfileId(): void
    {
        if (! $this->selectedProfileId) {
            return;
        }

        $profile = LeadImportProfile::find($this->selectedProfileId);

        if (! $profile) {
            return;
        }

        foreach ($this->mapping as $field => $_) {
            $savedColumn = $profile->mapping[$field] ?? '';
            $this->mapping[$field] = in_array($savedColumn, $this->headers, true) ? $savedColumn : '';
        }

        $this->defaultSource = $profile->default_source;

        Notification::make()
            ->title('Perfil aplicado: ' . $profile->name)
            ->body('Revisá el mapeo antes de importar — alguna columna puede no existir en este archivo.')
            ->success()
            ->send();
    }

    public function saveProfile(): void
    {
        if (trim($this->newProfileName) === '') {
            Notification::make()->title('Ponele un nombre al perfil antes de guardarlo')->warning()->send();
            return;
        }

        LeadImportProfile::updateOrCreate(
            [
                'company_id' => config('inmofollow.default_company_id', 1),
                'name'       => trim($this->newProfileName),
            ],
            [
                'mapping'        => $this->mapping,
                'default_source' => $this->defaultSource,
            ],
        );

        Notification::make()->title('Perfil guardado')->success()->send();
        $this->newProfileName = '';
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

        $allRows = $this->readAllRows($this->csvFile->getRealPath());
        $headerRowIndex = $this->detectHeaderRowIndex($allRows);
        $rawHeaders = $allRows[$headerRowIndex];
        $rawHeaders[0] = ltrim($rawHeaders[0] ?? '', "\xEF\xBB\xBF");
        $rawHeaders = array_map(fn ($h) => trim((string) $h), $rawHeaders);
        $dataRows = array_slice($allRows, $headerRowIndex + 1);

        $imported   = 0;
        $skipped    = 0;
        $duplicated = 0;
        $errors     = [];
        $companyId = config('inmofollow.default_company_id', 1);
        $userId    = auth()->id();
        $boolTrue  = ['1', 'si', 'sí', 'yes', 'true', 'x', 'v'];
        $lineNumber = 1;

        $statusesByName = LeadStatus::where('company_id', $companyId)
            ->get()
            ->keyBy(fn ($status) => mb_strtolower(trim($status->name)));
        $unmatchedStatuses = [];

        $existingPhones = Lead::where('company_id', $companyId)
            ->whereNotNull('phone')
            ->pluck('phone')
            ->map(fn ($phone) => Lead::normalizePhone($phone))
            ->filter()
            ->flip()
            ->toArray();

        foreach ($dataRows as $row) {
            $lineNumber++;
            $row = array_pad($row, count($rawHeaders), '');
            $rowData = array_combine($rawHeaders, array_slice($row, 0, count($rawHeaders)));

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

                if (! empty($this->defaultSource)) {
                    $data['source'] = $this->defaultSource;
                }

                if (empty($data['name'])) {
                    $skipped++;
                    continue;
                }

                $normalizedPhone = Lead::normalizePhone($data['phone'] ?? null);

                if ($normalizedPhone !== null && array_key_exists($normalizedPhone, $existingPhones)) {
                    $duplicated++;
                    continue;
                }

                Lead::create($data);
                $imported++;

                if ($normalizedPhone !== null) {
                    $existingPhones[$normalizedPhone] = true;
                }
            } catch (\Throwable $e) {
                $errors[] = "Fila {$lineNumber}: " . $e->getMessage();
            }
        }

        $this->results = [
            'imported'          => $imported,
            'skipped'           => $skipped,
            'duplicated'        => $duplicated,
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
        $this->selectedProfileId = null;
        $this->defaultSource = null;
        $this->newProfileName = '';
    }
}
