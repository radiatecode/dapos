<?php

namespace DA\Inventory\Actions\Dashboard;

use Carbon\CarbonImmutable;
use DA\Inventory\Models\QuickSale;
use DA\Inventory\Services\Decimal;

class PrepareDashboardQuickSalesToday
{
    /**
     * @return array{
     *     today: string,
     *     quick_sales_count: int,
     *     total_amount: string,
     *     total_items: int,
     *     cash_amount: string,
     *     average_amount: string
     * }
     */
    public function handle(): array
    {
        $today = CarbonImmutable::today();
        $totals = QuickSale::queries()->totalsForDay($today);

        return [
            'today' => $today->toDateString(),
            ...$totals,
            'average_amount' => Decimal::div($totals['total_amount'], $totals['quick_sales_count']),
        ];
    }
}
