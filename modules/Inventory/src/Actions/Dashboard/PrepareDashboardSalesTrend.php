<?php

namespace DA\Inventory\Actions\Dashboard;

use Carbon\CarbonImmutable;
use DA\Inventory\Models\Sale;
use DA\Inventory\Services\DashboardService;

class PrepareDashboardSalesTrend
{
    private const DAYS = 7;

    public function __construct(private DashboardService $dashboard) {}

    /**
     * Every day of the window is listed oldest first, including days without sales,
     * so the rows can feed a chart axis or a table directly.
     *
     * @return array{
     *     date_from: string,
     *     date_to: string,
     *     days: list<array{date: string, invoice_count: int, sales_amount: string, paid_amount: string, due_amount: string}>,
     *     totals: array{invoice_count: int, sales_amount: string, paid_amount: string, due_amount: string}
     * }
     */
    public function handle(): array
    {
        $dateTo = CarbonImmutable::today();
        $dateFrom = $dateTo->subDays(self::DAYS - 1);

        $days = $this->dashboard->fillDailySales(
            Sale::queries()->dailyTotalsBetween($dateFrom, $dateTo),
            $dateFrom,
            $dateTo,
        );

        return [
            'date_from' => $dateFrom->toDateString(),
            'date_to' => $dateTo->toDateString(),
            'days' => $days,
            'totals' => $this->dashboard->totalDailySales($days),
        ];
    }
}
