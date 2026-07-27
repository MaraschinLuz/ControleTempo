<?php

namespace App\Services;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Support\Str;
use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

class ProjectScheduleSpreadsheetImporter
{
    private const MAIN_NAMESPACE = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';

    private const OFFICE_RELATIONSHIPS_NAMESPACE = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';

    private const PACKAGE_RELATIONSHIPS_NAMESPACE = 'http://schemas.openxmlformats.org/package/2006/relationships';

    private const REQUIRED_HEADERS = [
        1 => 'column 1',
        2 => 'column 2',
        3 => 'demandas',
        4 => 'sugestao ia',
        5 => 'foi feito',
        6 => 'data execucao',
        7 => 'responsavel',
        8 => 'responsavel cliente',
        9 => 'contato cliente',
        10 => 'escopo',
        11 => 'demandas realizadas',
        12 => 'o que falta',
        13 => 'quando finaliza',
        14 => 'quantidade de horas',
    ];

    private const FIELD_MAP = [
        1 => 'column_1',
        2 => 'column_2',
        3 => 'demand',
        4 => 'ai_suggestion',
        5 => 'completion_status',
        6 => 'execution_date',
        7 => 'responsible',
        8 => 'client_responsible',
        9 => 'client_contact',
        10 => 'scope',
        11 => 'completed_demands',
        12 => 'remaining_work',
        13 => 'completion_date',
        14 => 'hours',
    ];

    public function import(string $path): array
    {
        $zip = new ZipArchive;

        if ($zip->open($path) !== true) {
            throw new RuntimeException('Não foi possível abrir a planilha. Verifique se o arquivo XLSX não está corrompido.');
        }

        try {
            $this->guardArchiveSize($zip);

            $workbook = $this->readXml($zip, 'xl/workbook.xml');
            $relationships = $this->readXml($zip, 'xl/_rels/workbook.xml.rels');
            $sharedStrings = $this->readSharedStrings($zip);
            $date1904 = $this->uses1904DateSystem($workbook);

            foreach ($this->worksheetCandidates($workbook, $relationships) as $worksheet) {
                $cells = $this->readCells($zip, $worksheet['path'], $sharedStrings);
                $headerRow = $this->findHeaderRow($cells);

                if ($headerRow === null) {
                    continue;
                }

                $rows = $this->readScheduleRows($cells, $headerRow, $date1904);

                if ($rows === []) {
                    throw new RuntimeException("A aba \"{$worksheet['name']}\" possui os cabeçalhos, mas não contém linhas para importar.");
                }

                return [
                    'worksheet' => $worksheet['name'],
                    'rows' => $rows,
                ];
            }
        } finally {
            $zip->close();
        }

        throw new RuntimeException('A planilha não segue o modelo esperado. Mantenha os 14 cabeçalhos, de Column 1 até Quantidade de horas.');
    }

    private function guardArchiveSize(ZipArchive $zip): void
    {
        $uncompressedBytes = 0;

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $stat = $zip->statIndex($index);
            $uncompressedBytes += (int) ($stat['size'] ?? 0);

            if ($uncompressedBytes > 50 * 1024 * 1024) {
                throw new RuntimeException('A planilha é muito grande para ser importada.');
            }
        }
    }

    private function readXml(ZipArchive $zip, string $path): SimpleXMLElement
    {
        $contents = $zip->getFromName($path);

        if ($contents === false) {
            throw new RuntimeException('O arquivo não possui uma estrutura XLSX válida.');
        }

        $xml = simplexml_load_string($contents, SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOCDATA);

        if ($xml === false) {
            throw new RuntimeException('Não foi possível ler a estrutura XML da planilha.');
        }

        return $xml;
    }

    private function readSharedStrings(ZipArchive $zip): array
    {
        if ($zip->locateName('xl/sharedStrings.xml') === false) {
            return [];
        }

        $xml = $this->readXml($zip, 'xl/sharedStrings.xml');
        $strings = [];

        foreach ($xml->children(self::MAIN_NAMESPACE)->si as $item) {
            $strings[] = trim((string) dom_import_simplexml($item)->textContent);
        }

        return $strings;
    }

    private function uses1904DateSystem(SimpleXMLElement $workbook): bool
    {
        $main = $workbook->children(self::MAIN_NAMESPACE);
        $attributes = $main->workbookPr->attributes();

        return filter_var((string) ($attributes['date1904'] ?? '0'), FILTER_VALIDATE_BOOL);
    }

    private function worksheetCandidates(SimpleXMLElement $workbook, SimpleXMLElement $relationships): array
    {
        $relationshipTargets = [];

        foreach ($relationships->children(self::PACKAGE_RELATIONSHIPS_NAMESPACE)->Relationship as $relationship) {
            $attributes = $relationship->attributes();
            $relationshipTargets[(string) $attributes['Id']] = (string) $attributes['Target'];
        }

        $main = $workbook->children(self::MAIN_NAMESPACE);
        $activeIndex = isset($main->bookViews->workbookView)
            ? (int) ($main->bookViews->workbookView->attributes()['activeTab'] ?? 0)
            : 0;
        $worksheets = [];

        $sheetIndex = 0;

        foreach ($main->sheets->sheet as $sheet) {
            $relationshipId = (string) $sheet->attributes(self::OFFICE_RELATIONSHIPS_NAMESPACE)['id'];
            $attributes = $sheet->attributes();
            $target = $relationshipTargets[$relationshipId] ?? null;

            if ($target === null) {
                $sheetIndex++;

                continue;
            }

            $path = str_starts_with($target, '/')
                ? ltrim($target, '/')
                : (str_starts_with($target, 'xl/') ? $target : 'xl/'.ltrim($target, '/'));

            if (str_contains($path, '..')) {
                continue;
            }

            $worksheets[] = [
                'name' => (string) $attributes['name'],
                'path' => $path,
                'active' => $sheetIndex === $activeIndex,
            ];

            $sheetIndex++;
        }

        usort($worksheets, fn (array $left, array $right) => $right['active'] <=> $left['active']);

        return $worksheets;
    }

    private function readCells(ZipArchive $zip, string $path, array $sharedStrings): array
    {
        $worksheet = $this->readXml($zip, $path);
        $main = $worksheet->children(self::MAIN_NAMESPACE);
        $cells = [];

        foreach ($main->sheetData->row as $row) {
            $rowNumber = (int) $row->attributes()['r'];

            foreach ($row->c as $cell) {
                $attributes = $cell->attributes();

                if (! preg_match('/^([A-Z]+)(\d+)$/', (string) $attributes['r'], $matches)) {
                    continue;
                }

                $column = $this->columnNumber($matches[1]);

                if ($column < 1 || $column > 14) {
                    continue;
                }

                $type = (string) $attributes['t'];
                $cellMain = $cell->children(self::MAIN_NAMESPACE);

                $value = match ($type) {
                    's' => $sharedStrings[(int) $cellMain->v] ?? '',
                    'inlineStr' => trim((string) dom_import_simplexml($cellMain->is)->textContent),
                    default => (string) $cellMain->v,
                };

                $cells[$rowNumber][$column] = [
                    'value' => trim($value),
                    'numeric' => $type === '' || $type === 'n',
                ];
            }
        }

        ksort($cells);

        return $cells;
    }

    private function columnNumber(string $letters): int
    {
        $number = 0;

        foreach (str_split($letters) as $letter) {
            $number = ($number * 26) + ord($letter) - 64;
        }

        return $number;
    }

    private function findHeaderRow(array $cells): ?int
    {
        foreach (array_slice($cells, 0, 20, true) as $rowNumber => $row) {
            $matches = 0;

            foreach (self::REQUIRED_HEADERS as $column => $expected) {
                if ($this->normalize((string) ($row[$column]['value'] ?? '')) === $expected) {
                    $matches++;
                }
            }

            if ($matches === count(self::REQUIRED_HEADERS)) {
                return $rowNumber;
            }
        }

        return null;
    }

    private function readScheduleRows(array $cells, int $headerRow, bool $date1904): array
    {
        $lastRow = max(array_keys($cells));
        $rows = [];
        $emptyRows = 0;

        for ($rowNumber = $headerRow + 1; $rowNumber <= $lastRow; $rowNumber++) {
            $source = $cells[$rowNumber] ?? [];
            $hasContent = collect(self::FIELD_MAP)
                ->keys()
                ->contains(fn (int $column) => trim((string) ($source[$column]['value'] ?? '')) !== '');

            if (! $hasContent) {
                $emptyRows++;

                if ($emptyRows >= 3) {
                    break;
                }

                continue;
            }

            $emptyRows = 0;
            $row = [];

            foreach (self::FIELD_MAP as $column => $field) {
                $cell = $source[$column] ?? ['value' => '', 'numeric' => false];
                $row[$field] = match ($field) {
                    'completion_status' => $this->status($cell['value'], $rowNumber),
                    'execution_date', 'completion_date' => $this->date($cell, $date1904, $rowNumber),
                    'hours' => $this->hours($cell['value'], $rowNumber),
                    default => $this->text($cell['value']),
                };
            }

            $rows[] = $row;

            if (count($rows) > 300) {
                throw new RuntimeException('A planilha possui mais de 300 linhas de cronograma.');
            }
        }

        return $rows;
    }

    private function text(string $value): ?string
    {
        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function status(string $value, int $rowNumber): ?string
    {
        $normalized = $this->normalize($value);

        if ($normalized === '' || $normalized === 'sim nao agendado') {
            return null;
        }

        return match ($normalized) {
            'sim' => 'Sim',
            'nao' => 'Não',
            'em andamento' => 'Em andamento',
            'agendado' => 'Agendado',
            default => throw new RuntimeException("Status inválido na linha {$rowNumber}: {$value}."),
        };
    }

    private function date(array $cell, bool $date1904, int $rowNumber): ?string
    {
        $value = trim((string) $cell['value']);
        $normalized = $this->normalize($value);

        if ($value === '' || in_array($normalized, ['', 'n', 'na', 'n a', 'data topicos'], true)) {
            return null;
        }

        if ($cell['numeric'] && is_numeric($value)) {
            $base = new DateTimeImmutable($date1904 ? '1904-01-01' : '1899-12-30', new DateTimeZone('UTC'));

            return $base->modify('+'.(int) floor((float) $value).' days')->format('Y-m-d');
        }

        foreach (['!d/m/Y', '!d/m/y', '!Y-m-d', '!d-m-Y'] as $format) {
            $date = DateTimeImmutable::createFromFormat($format, $value, new DateTimeZone('UTC'));
            $errors = DateTimeImmutable::getLastErrors();

            if ($date !== false && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))) {
                return $date->format('Y-m-d');
            }
        }

        throw new RuntimeException("Data inválida na linha {$rowNumber}: {$value}.");
    }

    private function hours(string $value, int $rowNumber): ?float
    {
        $value = trim($value);

        if ($value === '' || in_array($this->normalize($value), ['', 'soma das horas empenhadas'], true)) {
            return null;
        }

        $normalized = str_replace(' ', '', $value);

        if (str_contains($normalized, ',') && str_contains($normalized, '.')) {
            $normalized = str_replace('.', '', $normalized);
        }

        $normalized = str_replace(',', '.', $normalized);

        if (! is_numeric($normalized) || (float) $normalized < 0) {
            throw new RuntimeException("Quantidade de horas inválida na linha {$rowNumber}: {$value}.");
        }

        return round((float) $normalized, 2);
    }

    private function normalize(string $value): string
    {
        $value = Str::lower(Str::ascii(trim($value)));
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value);

        return trim(preg_replace('/\s+/', ' ', $value));
    }
}
