<?php

namespace DA\Inventory\DTO\Product;

final class ProductBatchVerification
{
    /**
     * @param  list<array{row: int, status: string, errors: list<string>, data: array<string, string>}>  $rows
     * @param  list<ProductDTO>  $products
     */
    public function __construct(
        public bool $valid,
        public int $storeId,
        public array $rows,
        public array $products,
    ) {}

    /**
     * @return array{
     *     message: string,
     *     valid: bool,
     *     store_id: int,
     *     summary: array{total: int, valid: int, invalid: int},
     *     rows: list<array{row: int, status: string, errors: list<string>, data: array<string, string>}>
     * }
     */
    public function preview(): array
    {
        $invalid = 0;

        foreach ($this->rows as $row) {
            if ($row['status'] === 'error') {
                $invalid++;
            }
        }

        $total = count($this->rows);

        return [
            'message' => $this->valid
                ? 'The spreadsheet is ready to upload.'
                : 'The spreadsheet has invalid rows.',
            'valid' => $this->valid,
            'store_id' => $this->storeId,
            'summary' => [
                'total' => $total,
                'valid' => $total - $invalid,
                'invalid' => $invalid,
            ],
            'rows' => $this->rows,
        ];
    }
}
