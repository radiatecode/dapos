<?php

namespace DA\Admin\Actions\Default;

use DA\Inventory\Models\Unit;

class CreateDefaultUnit
{
    private array $units = [
        [
            'name' => 'Piece',
            'code' => 'PC',
            'unit_type' => 'quantity',
            'precision' => 0,
        ],
        [
            'name' => 'Box',
            'code' => 'BOX',
            'unit_type' => 'quantity',
            'precision' => 0,
        ],
        [
            'name' => 'Kilogram',
            'code' => 'KG',
            'unit_type' => 'weight',
            'precision' => 3,
        ],
        [
            'name' => 'Meter',
            'code' => 'M',
            'unit_type' => 'length',
            'precision' => 2,
        ],
        [
            'name' => 'Liter',
            'code' => 'L',
            'unit_type' => 'volume',
            'precision' => 2,
        ],
    ];

    public function handle(int $tenantId)
    {
        foreach ($this->units as $unit) {
            Unit::create([
                'tenant_id' => $tenantId,
                'name' => $unit['name'],
                'code' => $unit['code'],
                'unit_type' => $unit['unit_type'],
                'precision' => $unit['precision'],
                'is_default' => true,
            ]);
        }
    }
}
