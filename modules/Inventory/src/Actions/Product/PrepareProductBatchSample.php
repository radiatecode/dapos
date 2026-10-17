<?php

namespace DA\Inventory\Actions\Product;

use DA\Admin\Models\Tenant;
use DA\Inventory\DTO\Product\ProductBatchSampleDTO;

class PrepareProductBatchSample
{
    public function handle(): ProductBatchSampleDTO
    {
        /** @var Tenant $tenant */
        $tenant = Tenant::queries()->findById((int) auth()->user()->tenant_id);

        return new ProductBatchSampleDTO(
            companyName: (string) $tenant->name,
            address: $this->address($tenant),
        );
    }

    private function address(Tenant $tenant): string
    {
        $parts = [
            $tenant->address_line_1,
            $tenant->address_line_2,
            $tenant->city,
            $tenant->state,
            $tenant->postal_code,
            $tenant->country,
        ];

        $filled = [];

        foreach ($parts as $part) {
            if (is_string($part) && trim($part) !== '') {
                $filled[] = trim($part);
            }
        }

        return implode(', ', $filled);
    }
}
