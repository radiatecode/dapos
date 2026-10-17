<?php

namespace DA\Inventory\Services;

use DA\Inventory\DTO\Product\ParsedProductBatch;
use DA\Inventory\DTO\Product\ProductBatchRowDTO;
use DA\Inventory\Supports\Product\ProductBatchColumns;
use DA\Inventory\Supports\Product\ProductBatchSheetImport;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ProductBatchReader
{
    public function read(UploadedFile $file): ParsedProductBatch
    {
        $this->useWritableTemporaryPath();

        try {
            $sheets = Excel::toCollection(new ProductBatchSheetImport, $file);
        } catch (Throwable $exception) {
            report($exception);

            return new ParsedProductBatch('The spreadsheet could not be read.');
        }

        $sheet = ($sheets->first() ?? collect())->values();

        if (! $this->headingsMatch($sheet->get(ProductBatchColumns::HEADER_ROW - 1))) {
            return new ParsedProductBatch('The spreadsheet headings do not match the sample file.');
        }

        $rows = $this->dataRows($sheet);

        if ($rows === []) {
            return new ParsedProductBatch('The spreadsheet does not contain any products.');
        }

        if (count($rows) > ProductBatchColumns::MAX_ROWS) {
            return new ParsedProductBatch('The spreadsheet may not contain more than 500 products.');
        }

        return new ParsedProductBatch(null, $rows);
    }

    private function headingsMatch(mixed $headingRow): bool
    {
        $cells = $this->cells($headingRow);
        $headings = [];

        foreach (ProductBatchColumns::HEADINGS as $index => $expected) {
            $headings[] = $this->cellString($cells[$index] ?? null);

            if ($headings[$index] !== $expected) {
                return false;
            }
        }

        foreach (array_slice($cells, count(ProductBatchColumns::HEADINGS)) as $extra) {
            if ($this->cellString($extra) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  Collection<int, mixed>  $sheet
     * @return list<ProductBatchRowDTO>
     */
    private function dataRows(Collection $sheet): array
    {
        $rows = [];
        $keys = array_values(ProductBatchColumns::KEYS);

        foreach ($sheet as $index => $row) {
            $excelRow = $index + 1;

            if ($excelRow <= ProductBatchColumns::HEADER_ROW) {
                continue;
            }

            $cells = $this->cells($row);

            if ($this->isBlank($cells)) {
                continue;
            }

            $mapped = [];

            foreach ($keys as $position => $key) {
                $mapped[$key] = $this->cellString($cells[$position] ?? null);
            }

            $rows[] = new ProductBatchRowDTO(
                excelRow: $excelRow,
                name: $mapped['name'],
                description: $mapped['description'],
                category: $mapped['category'],
                brand: $mapped['brand'],
                unit: $mapped['unit'],
                sku: $mapped['sku'],
                barcode: $mapped['barcode'],
                costPrice: $mapped['cost_price'],
                sellingPrice: $mapped['selling_price'],
                quantity: $mapped['quantity'],
                minStockLevel: $mapped['min_stock_level'],
            );
        }

        return $rows;
    }

    /**
     * @return list<mixed>
     */
    private function cells(mixed $row): array
    {
        if ($row instanceof Collection) {
            return $row->values()->all();
        }

        if (is_array($row)) {
            return array_values($row);
        }

        return [];
    }

    /**
     * @param  list<mixed>  $cells
     */
    private function isBlank(array $cells): bool
    {
        foreach ($cells as $cell) {
            if ($this->cellString($cell) !== '') {
                return false;
            }
        }

        return true;
    }

    private function cellString(mixed $value): string
    {
        if ($value === null || is_bool($value)) {
            return '';
        }

        if (is_int($value) || (is_float($value) && abs($value - round($value)) < 0.0000001 && abs($value) < 1e15)) {
            return sprintf('%.0f', round((float) $value));
        }

        if (is_float($value)) {
            return rtrim(rtrim(number_format($value, 4, '.', ''), '0'), '.');
        }

        if (! is_scalar($value)) {
            return '';
        }

        return trim((string) $value);
    }

    private function useWritableTemporaryPath(): void
    {
        $configured = config('excel.temporary_files.local_path');

        if (is_string($configured) && is_dir($configured) && is_writable($configured)) {
            return;
        }

        config()->set('excel.temporary_files.local_path', sys_get_temp_dir());
    }
}
