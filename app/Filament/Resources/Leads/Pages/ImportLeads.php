<?php

namespace App\Filament\Resources\Leads\Pages;

use App\Filament\Resources\Leads\LeadResource;
use App\Models\Lead;
use App\Models\LeadImportProfile;
use App\Models\LeadListing;
use App\Models\LeadStatus;
use App\Services\FollowUpGenerator;
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
    public string $defaultWhatsappConsent = '';
    public string $defaultEmailConsent = '';
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

    public array $listingMapping = [
        'listing_external_id' => '',
        'listing_title'       => '',
        'listing_type'        => '',
        'listing_operation'   => '',
        'listing_price'       => '',
        'listing_zone_raw'    => '',
        'listing_url'         => '',
        'listing_attributes'  => '',
    ];

    // ─── Field definitions ────────────────────────────────────────────────────

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

    public static function getListingFields(): array
    {
        return [
            'listing_external_id' => 'ID externo (MLU…)',
            'listing_title'       => 'Título de la publicación',
            'listing_type'        => 'Tipo de propiedad',
            'listing_operation'   => 'Operación (Venta/Alquiler)',
            'listing_price'       => 'Precio',
            'listing_zone_raw'    => 'Zona completa',
            'listing_url'         => 'Link de publicación',
            'listing_attributes'  => 'Atributos (dorm. | baños | m²)',
        ];
    }

    // ─── File upload ──────────────────────────────────────────────────────────

    public function updatedCsvFile(): void
    {
        if (! $this->csvFile) return;

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

    // ─── ML Preset ───────────────────────────────────────────────────────────

    public function applyMlPreset(): void
    {
        $mlLeadMap = [
            'phone'         => ['Teléfono', 'telefono', 'phone'],
            'property_type' => ['Tipo', 'tipo'],
        ];
        $mlListingMap = [
            'listing_external_id' => ['Item ID', 'item_id', 'item id'],
            'listing_title'       => ['Título', 'titulo', 'title'],
            'listing_type'        => ['Tipo', 'tipo'],
            'listing_operation'   => ['Operación', 'operacion', 'operation'],
            'listing_price'       => ['Precio', 'precio', 'price'],
            'listing_zone_raw'    => ['Zona', 'zona', 'zone'],
            'listing_url'         => ['Link Publicación', 'link publicacion', 'link', 'url'],
            'listing_attributes'  => ['Atributos', 'atributos', 'attributes'],
        ];

        foreach ($mlLeadMap as $field => $candidates) {
            foreach ($this->headers as $header) {
                if (in_array(trim($header), $candidates, true)) {
                    $this->mapping[$field] = $header;
                    break;
                }
            }
        }

        foreach ($mlListingMap as $field => $candidates) {
            foreach ($this->headers as $header) {
                if (in_array(trim($header), $candidates, true)) {
                    $this->listingMapping[$field] = $header;
                    break;
                }
            }
        }

        $this->defaultSource = 'ml';

        Notification::make()
            ->title('Preset MercadoLibre aplicado')
            ->body('Revisá el mapeo y ajustá si alguna columna no coincide.')
            ->success()
            ->send();
    }

    // ─── Profiles ─────────────────────────────────────────────────────────────

    public function availableProfiles(): array
    {
        return LeadImportProfile::where('company_id', config('inmofollow.default_company_id', 1))
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    public function updatedSelectedProfileId(): void
    {
        if (! $this->selectedProfileId) return;

        $profile = LeadImportProfile::find($this->selectedProfileId);
        if (! $profile) return;

        foreach ($this->mapping as $field => $_) {
            $savedColumn = $profile->mapping[$field] ?? '';
            $this->mapping[$field] = in_array($savedColumn, $this->headers, true) ? $savedColumn : '';
        }

        foreach ($this->listingMapping as $field => $_) {
            $savedColumn = $profile->mapping[$field] ?? '';
            $this->listingMapping[$field] = in_array($savedColumn, $this->headers, true) ? $savedColumn : '';
        }

        $this->defaultSource           = $profile->default_source;
        $this->defaultWhatsappConsent  = $this->boolToDefaultString($profile->default_whatsapp_consent);
        $this->defaultEmailConsent     = $this->boolToDefaultString($profile->default_email_consent);

        Notification::make()
            ->title('Perfil aplicado: ' . $profile->name)
            ->body('Revisá el mapeo antes de importar.')
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
            ['company_id' => config('inmofollow.default_company_id', 1), 'name' => trim($this->newProfileName)],
            [
                'mapping'                  => array_merge($this->mapping, $this->listingMapping),
                'default_source'           => $this->defaultSource,
                'default_whatsapp_consent' => $this->defaultStringToBool($this->defaultWhatsappConsent),
                'default_email_consent'    => $this->defaultStringToBool($this->defaultEmailConsent),
            ],
        );

        Notification::make()->title('Perfil guardado')->success()->send();
        $this->newProfileName = '';
    }

    private function defaultStringToBool(string $value): ?bool
    {
        return $value === '' ? null : $value === '1';
    }

    private function boolToDefaultString(?bool $value): string
    {
        return $value === null ? '' : ($value ? '1' : '0');
    }

    // ─── Auto-map ─────────────────────────────────────────────────────────────

    private function autoMap(): void
    {
        $leadAliases = [
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

        $listingAliases = [
            'listing_external_id' => ['item_id', 'item id', 'id_externo', 'external_id', 'mlu'],
            'listing_title'       => ['título', 'titulo', 'title', 'nombre_publicacion', 'publicacion'],
            'listing_operation'   => ['operación', 'operacion', 'operation', 'modalidad'],
            'listing_price'       => ['precio', 'price', 'valor', 'monto'],
            'listing_zone_raw'    => ['zona', 'zone', 'localidad', 'ubicacion', 'dirección', 'direccion'],
            'listing_url'         => ['link_publicación', 'link publicación', 'link publicacion', 'link', 'url', 'enlace'],
            'listing_attributes'  => ['atributos', 'attributes', 'características', 'caracteristicas'],
        ];

        foreach ($this->mapping as $field => $_) {
            $this->mapping[$field] = '';
        }
        foreach ($this->listingMapping as $field => $_) {
            $this->listingMapping[$field] = '';
        }

        foreach ($leadAliases as $field => $aliases) {
            foreach ($this->headers as $header) {
                $normalized = strtolower(trim(str_replace([' ', '-', '.'], '_', $header)));
                if (in_array($normalized, $aliases)) {
                    $this->mapping[$field] = $header;
                    break;
                }
            }
        }

        foreach ($listingAliases as $field => $aliases) {
            foreach ($this->headers as $header) {
                $normalized = strtolower(trim(str_replace([' ', '-', '.'], '_', $header)));
                if (in_array($normalized, $aliases)) {
                    $this->listingMapping[$field] = $header;
                    break;
                }
            }
        }
    }

    // ─── Import ───────────────────────────────────────────────────────────────

    public function import(): void
    {
        if (! $this->csvFile) {
            Notification::make()->title('No hay archivo para importar')->danger()->send();
            return;
        }

        $hasListingFields = collect($this->listingMapping)->filter()->isNotEmpty();

        // Si no hay nombre mapeado y hay datos de propiedad (import ML), lo permitimos
        if (empty($this->mapping['name']) && ! $hasListingFields) {
            Notification::make()->title('Debés mapear el campo Nombre antes de importar')->danger()->send();
            return;
        }

        $allRows        = $this->readAllRows($this->csvFile->getRealPath());
        $headerRowIndex = $this->detectHeaderRowIndex($allRows);
        $rawHeaders     = $allRows[$headerRowIndex];
        $rawHeaders[0]  = ltrim($rawHeaders[0] ?? '', "\xEF\xBB\xBF");
        $rawHeaders     = array_map(fn ($h) => trim((string) $h), $rawHeaders);
        $dataRows       = array_slice($allRows, $headerRowIndex + 1);

        // Dar tiempo suficiente para importaciones grandes (500+ filas)
        set_time_limit(300);

        $imported     = 0;
        $updated      = 0;
        $skipped      = 0;
        $duplicated   = 0;
        $errors       = [];
        $newLeadIds   = [];   // leads nuevos → generar follow-ups al final
        $companyId    = config('inmofollow.default_company_id', 1);
        $userId       = auth()->id();
        $boolTrue     = ['1', 'si', 'sí', 'yes', 'true', 'x', 'v'];
        $lineNumber   = 1;

        $statusesByName = LeadStatus::where('company_id', $companyId)
            ->get()
            ->keyBy(fn ($s) => mb_strtolower(trim($s->name)));
        $unmatchedStatuses = [];

        $existingPhones = Lead::where('company_id', $companyId)
            ->whereNotNull('phone')
            ->pluck('id', 'phone')
            ->mapWithKeys(fn ($id, $phone) => [Lead::normalizePhone($phone) ?? $phone => $id])
            ->toArray();

        foreach ($dataRows as $row) {
            $lineNumber++;
            $row     = array_pad($row, count($rawHeaders), '');
            $rowData = array_combine($rawHeaders, array_slice($row, 0, count($rawHeaders)));

            try {
                $data = [
                    'company_id' => $companyId,
                    'user_id'    => $userId,
                    'source'     => 'csv',
                ];

                foreach ($this->mapping as $field => $csvColumn) {
                    if (! $csvColumn) continue;
                    $value = trim($rowData[$csvColumn] ?? '');

                    if (in_array($field, ['whatsapp_consent', 'email_consent', 'do_not_contact'])) {
                        $data[$field] = in_array(strtolower($value), $boolTrue);
                    } elseif ($field === 'lead_status') {
                        if ($value === '') continue;
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

                if ($this->defaultWhatsappConsent !== '') {
                    $data['whatsapp_consent'] = $this->defaultWhatsappConsent === '1';
                }

                if ($this->defaultEmailConsent !== '') {
                    $data['email_consent'] = $this->defaultEmailConsent === '1';
                }

                $normalizedPhone = Lead::normalizePhone($data['phone'] ?? null);

                // Para imports ML sin nombre: usar teléfono como nombre
                if (empty($data['name'])) {
                    if ($normalizedPhone) {
                        $data['name'] = $data['phone'];
                    } else {
                        $skipped++;
                        continue;
                    }
                }

                // Dedup por teléfono
                $existingLeadId = $normalizedPhone ? ($existingPhones[$normalizedPhone] ?? null) : null;

                if ($existingLeadId) {
                    $lead = Lead::find($existingLeadId);
                    $duplicated++;
                } else {
                    // Suprimimos el observer para que no dispare FollowUpGenerator
                    // por cada lead individualmente — lo hacemos en lote al final
                    $lead = Lead::withoutObservers(fn () => Lead::create($data));
                    $newLeadIds[] = $lead->id;
                    $imported++;

                    if ($normalizedPhone) {
                        $existingPhones[$normalizedPhone] = $lead->id;
                    }
                }

                // Crear listing si hay campos mapeados
                if ($hasListingFields) {
                    $this->createListing($lead, $rowData, $data['source'] ?? 'csv', $companyId);
                }
            } catch (\Throwable $e) {
                $errors[] = "Fila {$lineNumber}: " . $e->getMessage();
            }
        }

        // Generar follow-ups en lote para los leads nuevos.
        // Al hacerlo después de que todos existen en DB, evitamos 500 queries
        // intercaladas con los inserts y reducimos el riesgo de timeout.
        if (! empty($newLeadIds)) {
            $generator  = app(FollowUpGenerator::class);
            $freshLeads = Lead::whereIn('id', $newLeadIds)->get();
            foreach ($freshLeads as $freshLead) {
                try {
                    $generator->generateForLead($freshLead, 'lead_created');
                } catch (\Throwable) {
                    // No interrumpir el resultado por un follow-up fallido
                }
            }
        }

        $this->results = [
            'imported'          => $imported,
            'updated'           => $updated,
            'skipped'           => $skipped,
            'duplicated'        => $duplicated,
            'errors'            => $errors,
            'unmatchedStatuses' => array_keys($unmatchedStatuses),
        ];
        $this->step = 3;
    }

    private function createListing(Lead $lead, array $rowData, string $source, int $companyId): void
    {
        $listingData = [
            'lead_id'    => $lead->id,
            'company_id' => $companyId,
            'source'     => $source,
        ];

        foreach ($this->listingMapping as $field => $csvColumn) {
            if (! $csvColumn) continue;
            $value = trim($rowData[$csvColumn] ?? '');
            if ($value === '') continue;

            $dbField = str_replace('listing_', '', $field);

            switch ($dbField) {
                case 'price':
                    $parsed = $this->parsePrice($value);
                    $listingData['asking_price']    = $parsed['amount'];
                    $listingData['price_currency']  = $parsed['currency'];
                    break;

                case 'attributes':
                    $parsed = $this->parseAttributes($value);
                    foreach ($parsed as $k => $v) {
                        $listingData[$k] = $v;
                    }
                    $listingData['attributes'] = ['raw' => $value];
                    break;

                default:
                    $listingData[$dbField] = $value;
            }
        }

        // Si hay type en listing pero no en lead, copiarlo al lead
        if (! empty($listingData['type']) && empty($lead->property_type)) {
            $lead->updateQuietly(['property_type' => $listingData['type']]);
        }

        $externalId = $listingData['external_id'] ?? null;

        if ($externalId) {
            $listing = LeadListing::updateOrCreate(
                ['external_id' => $externalId],
                $listingData
            );
        } else {
            $listing = LeadListing::create($listingData);
        }

        // Si el lead no tiene listing primario, asignar este
        if (! $lead->primary_listing_id) {
            $listing->update(['is_primary' => true]);
            $lead->updateQuietly(['primary_listing_id' => $listing->id]);
        }
    }

    // ─── Parsers ──────────────────────────────────────────────────────────────

    private function parsePrice(string $raw): array
    {
        $digits = preg_replace('/[^\d.]/', '', $raw);
        $amount = $digits !== '' ? (float) $digits : null;

        $currency = 'USD';
        $lower    = mb_strtolower($raw);
        if (str_contains($lower, 'peso') || str_contains($lower, 'uyu') || str_contains($lower, '$u')) {
            $currency = 'UYU';
        }

        return ['amount' => $amount, 'currency' => $currency];
    }

    private function parseAttributes(string $raw): array
    {
        $result = [];
        $parts  = preg_split('/\|/', $raw);

        foreach ($parts as $part) {
            $part = trim($part);

            if (preg_match('/^(\d+)\s+dorm/iu', $part, $m)) {
                $result['bedrooms'] = (int) $m[1];
            } elseif (preg_match('/^(\d+)\s+ba[ñn]/iu', $part, $m)) {
                $result['bathrooms'] = (int) $m[1];
            } elseif (preg_match('/([\d.,]+)\s*m[²2]\s*cubierto/iu', $part, $m)) {
                $result['m2_covered'] = (float) str_replace(',', '.', $m[1]);
            } elseif (preg_match('/([\d.,]+)\s*m[²2]\s*total/iu', $part, $m)) {
                $result['m2_total'] = (float) str_replace(',', '.', $m[1]);
            } elseif (preg_match('/([\d.,]+)\s*m[²2]/iu', $part, $m)) {
                $result['m2_covered'] = (float) str_replace(',', '.', $m[1]);
            } elseif (preg_match('/([\d.,]+)\s*ha\b/iu', $part, $m)) {
                $result['m2_total'] = round((float) str_replace(',', '.', $m[1]) * 10000, 2);
            }
        }

        return $result;
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    public function downloadTemplate(): mixed
    {
        $fields  = array_keys(self::getLeadFields());
        $example = ['Juan Pérez', '098123456', 'juan@example.com', 'Apartamento', 'Pocitos', 'web', 'Interesado en 2 dorm', 'Nuevo', '1', '0', '0'];

        return response()->streamDownload(function () use ($fields, $example) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $fields);
            fputcsv($handle, $example);
            fclose($handle);
        }, 'plantilla-leads.csv', ['Content-Type' => 'text/csv']);
    }

    public function startOver(): void
    {
        $this->csvFile          = null;
        $this->headers          = [];
        $this->preview          = [];
        $this->results          = null;
        $this->step             = 1;
        $this->delimiter        = ',';
        $this->mapping          = array_fill_keys(array_keys(self::getLeadFields()), '');
        $this->listingMapping   = array_fill_keys(array_keys(self::getListingFields()), '');
        $this->selectedProfileId      = null;
        $this->defaultSource          = null;
        $this->defaultWhatsappConsent = '';
        $this->defaultEmailConsent    = '';
        $this->newProfileName         = '';
    }

    private function isXlsx(): bool
    {
        $extension = strtolower($this->csvFile->getClientOriginalExtension() ?: pathinfo($this->csvFile->getClientOriginalName(), PATHINFO_EXTENSION));
        return in_array($extension, ['xlsx', 'xlsm'], true);
    }

    private function readAllRows(string $path): array
    {
        if ($this->isXlsx()) return $this->readXlsxRows($path);

        $handle = fopen($path, 'r');
        $rows   = [];
        while (($row = fgetcsv($handle, 0, $this->delimiter)) !== false) {
            $rows[] = $row;
        }
        fclose($handle);
        return $rows;
    }

    private function readXlsxRows(string $path): array
    {
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) throw new \RuntimeException('No se pudo abrir el archivo Excel.');

        $sharedStrings = [];
        $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedStringsXml !== false) {
            $xml = simplexml_load_string($sharedStringsXml);
            foreach ($xml->si as $si) {
                if (isset($si->t)) {
                    $sharedStrings[] = (string) $si->t;
                } else {
                    $text = '';
                    foreach ($si->r as $run) $text .= (string) $run->t;
                    $sharedStrings[] = $text;
                }
            }
        }

        $sheetContent = $zip->getFromName($this->firstSheetPath($zip));
        $zip->close();
        if ($sheetContent === false) throw new \RuntimeException('No se pudo leer la hoja del archivo Excel.');

        $sheet = simplexml_load_string($sheetContent);
        $rows  = [];
        foreach ($sheet->sheetData->row as $row) {
            $cells  = [];
            $maxCol = 0;
            foreach ($row->c as $c) {
                $colIdx = $this->colToIndex((string) $c['r']);
                $maxCol = max($maxCol, $colIdx);
                $type   = (string) $c['t'];
                $value  = isset($c->v) ? (string) $c->v : '';
                if ($type === 's' && $value !== '') $value = $sharedStrings[(int) $value] ?? '';
                elseif ($type === 'inlineStr' && isset($c->is->t)) $value = (string) $c->is->t;
                $cells[$colIdx] = $value;
            }
            $plainRow = [];
            for ($i = 0; $i <= $maxCol; $i++) $plainRow[] = $cells[$i] ?? '';
            $rows[] = $plainRow;
        }
        return $rows;
    }

    private function firstSheetPath(\ZipArchive $zip): string
    {
        try {
            $workbookXml = $zip->getFromName('xl/workbook.xml');
            $relsXml     = $zip->getFromName('xl/_rels/workbook.xml.rels');
            if ($workbookXml !== false && $relsXml !== false) {
                $workbook = simplexml_load_string($workbookXml);
                $rels     = simplexml_load_string($relsXml);
                $firstSheet = $workbook->sheets->sheet[0] ?? null;
                if ($firstSheet) {
                    $rId = (string) $firstSheet->attributes('r', true)->id;
                    foreach ($rels->Relationship as $rel) {
                        if ((string) $rel['Id'] === $rId) return 'xl/' . ltrim((string) $rel['Target'], '/');
                    }
                }
            }
        } catch (\Throwable) {}
        return 'xl/worksheets/sheet1.xml';
    }

    private function colToIndex(string $ref): int
    {
        $col   = preg_replace('/\d+/', '', $ref);
        $index = 0;
        foreach (str_split($col) as $char) $index = $index * 26 + (ord($char) - ord('A') + 1);
        return $index - 1;
    }

    private function detectHeaderRowIndex(array $rows): int
    {
        foreach ($rows as $index => $row) {
            if (count(array_filter($row, fn ($v) => trim((string) $v) !== '')) > 1) return $index;
        }
        return 0;
    }
}
