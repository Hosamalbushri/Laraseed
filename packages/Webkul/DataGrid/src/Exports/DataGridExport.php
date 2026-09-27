<?php

namespace Webkul\DataGrid\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Webkul\DataGrid\DataGrid;

class DataGridExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    /**
     * Create a new instance.
     *
     * @return void
     */
    public function __construct(protected DataGrid $datagrid) {}

    /**
     * Query.
     */
    public function query(): mixed
    {
        return $this->datagrid->getQueryBuilder();
    }

    /**
     * Headings.
     */
    public function headings(): array
    {
        return collect($this->datagrid->getColumns())
            ->filter(fn ($column) => $column->getExportable())
            ->map(fn ($column) => $this->sanitizeCell($column->getLabel()))
            ->toArray();
    }

    /**
     * Map each row for export.
     */
    public function map(mixed $record): array
    {
        return collect($this->datagrid->getColumns())
            ->filter(fn ($column) => $column->getExportable())
            ->map(function ($column) use ($record) {
                $index = $column->getIndex();
                $value = $record->{$index};

                if (
                    in_array($index, ['emails', 'contact_numbers'])
                    && is_string($value)
                ) {
                    $value = $this->extractValuesFromJson($value);
                }

                return $this->sanitizeCell($value);
            })
            ->toArray();
    }

    /**
     * Extract 'value' fields from a JSON string.
     */
    protected function extractValuesFromJson(string $json): string
    {
        $items = json_decode($json, true);

        if (
            json_last_error() === JSON_ERROR_NONE
            && is_array($items)
        ) {
            return collect($items)->map(fn ($item) => "{$item['value']} ({$item['label']})")->implode(', ');
        }

        return $json;
    }

    /**
     * Sanitize cell value to prevent spreadsheet formula injection.
     */
    protected function sanitizeCell(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map(fn ($item) => $this->sanitizeCell($item), $value);
        }

        if ($value === null || is_bool($value) || is_int($value) || is_float($value)) {
            return $value;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value;
        }

        if (is_object($value) && method_exists($value, '__toString')) {
            $value = (string) $value;
        }

        if (! is_string($value)) {
            return $value;
        }

        /**
         * Preserve pure numeric strings such as "-15", "-0.5", "12345".
         * Formula triggers starting with '=' or '+' must be escaped even if numeric (e.g. "=15", "+15").
         */
        if (is_numeric($value) && isset($value[0]) && $value[0] !== '=' && $value[0] !== '+') {
            return $value;
        }

        /**
         * Neutralize string if trimmed value begins with formula trigger character.
         */
        $trimmed = ltrim($value, " \t\r\n");

        if ($trimmed !== '') {
            $firstChar = $trimmed[0];

            if (in_array($firstChar, ['=', '+', '-', '@', "\t", "\r", "\n"], true)) {
                return "'".$value;
            }
        }

        return $value;
    }
}
