<?php

namespace DA\Inventory\Services;

use DA\Inventory\Models\ProductVariant;

class VariantCodeGenerator
{
    public const BARCODE_PREFIX = 'DAPR';

    public const SKU_PREFIX = 'DASKU-';

    public const BARCODE_PAD = 7;

    /**
     * @param  list<string>  $reserved
     */
    public function nextSku(array $reserved = []): string
    {
        $reserved = array_map(fn (string $sku): string => strtolower($sku), $reserved);

        for ($attempt = 0; $attempt < 20; $attempt++) {
            $sku = self::SKU_PREFIX.((int) round(microtime(true) * 1000) + $attempt);

            if (! in_array(strtolower($sku), $reserved, true) && ! $this->skuExists($sku)) {
                return $sku;
            }
        }

        return self::SKU_PREFIX.((int) round(microtime(true) * 1000)).'-'.bin2hex(random_bytes(2));
    }

    /**
     * @param  list<string>  $reserved
     */
    public function nextBarcode(array $reserved = []): string
    {
        return $this->nextBarcodes(1, $reserved)[0];
    }

    /**
     * @param  list<string>  $reserved
     * @return list<string>
     */
    public function nextBarcodes(int $count, array $reserved = []): array
    {
        $next = $this->nextBarcodeNumber($reserved);
        $codes = [];

        for ($index = 0; $index < $count; $index++) {
            $codes[] = self::BARCODE_PREFIX.str_pad((string) ($next + $index), self::BARCODE_PAD, '0', STR_PAD_LEFT);
        }

        return $codes;
    }

    /**
     * @param  list<string>  $reserved
     */
    private function nextBarcodeNumber(array $reserved): int
    {
        $highest = ProductVariant::queries()->latestSequentialBarcodeNumber(self::BARCODE_PREFIX);

        foreach ($reserved as $barcode) {
            if (! is_string($barcode) || ! preg_match('/^'.preg_quote(self::BARCODE_PREFIX, '/').'(\d+)$/', $barcode, $matches)) {
                continue;
            }

            $highest = max($highest, (int) $matches[1]);
        }

        return $highest + 1;
    }

    private function skuExists(string $sku): bool
    {
        return ProductVariant::queries()->skuExists($sku);
    }
}
