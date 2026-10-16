<?php

namespace DA\Inventory\Actions\Dashboard;

use Carbon\CarbonImmutable;
use DA\Inventory\DTO\Report\CurrentStockValueReportFilterDTO;
use DA\Inventory\Enums\CurrentStockValueReportSortColumn;
use DA\Inventory\Models\InventoryBalance;
use DA\Inventory\Models\Purchase;
use DA\Inventory\Models\Sale;
use DA\Inventory\Models\SaleItem;
use DA\Inventory\Services\DashboardService;

class PrepareDashboardSummary
{
    public function __construct(private DashboardService $dashboard) {}

    /**
     * Month comparisons are current month-to-date against the full previous calendar month.
     *
     * @return array{
     *     period: array{today: string, current_month_start: string, previous_month_start: string, previous_month_end: string, days_elapsed: int},
     *     sales: array{total: string, today: string, current_month: string, previous_month: string, current_month_daily_average: string, current_month_change: array{change_amount: string, change_percentage: string|null, trend: string}},
     *     purchases: array{total: string, today: string, current_month: string},
     *     profit: array{current_month: string, previous_month: string, current_month_change: array{change_amount: string, change_percentage: string|null, trend: string}},
     *     payments: array{total_paid: string, total_due: string},
     *     stock: array{current_value: string}
     * }
     */
    public function handle(): array
    {
        $today = CarbonImmutable::today();
        $currentMonthStart = $today->startOfMonth();

        $sales = Sale::queries()->dashboardTotals($today);
        $purchases = Purchase::queries()->dashboardReceivedTotals($today);
        $profit = SaleItem::queries()->grossProfitForCurrentAndPreviousMonth($today);
        $stock = InventoryBalance::queries()->currentStockValueReportTotals($this->allStockFilter());

        return [
            'period' => [
                'today' => $today->toDateString(),
                'current_month_start' => $currentMonthStart->toDateString(),
                'previous_month_start' => $currentMonthStart->subMonth()->toDateString(),
                'previous_month_end' => $currentMonthStart->subDay()->toDateString(),
                'days_elapsed' => $today->day,
            ],
            'sales' => [
                'total' => $sales['total_amount'],
                'today' => $sales['today_amount'],
                'current_month' => $sales['current_month_amount'],
                'previous_month' => $sales['previous_month_amount'],
                'current_month_daily_average' => $this->dashboard->dailyAverage($sales['current_month_amount'], $today->day),
                'current_month_change' => $this->dashboard->compareWithPrevious(
                    $sales['current_month_amount'],
                    $sales['previous_month_amount'],
                ),
            ],
            'purchases' => [
                'total' => $purchases['total_amount'],
                'today' => $purchases['today_amount'],
                'current_month' => $purchases['current_month_amount'],
            ],
            'profit' => [
                'current_month' => $profit['current_month'],
                'previous_month' => $profit['previous_month'],
                'current_month_change' => $this->dashboard->compareWithPrevious(
                    $profit['current_month'],
                    $profit['previous_month'],
                ),
            ],
            'payments' => [
                'total_paid' => $sales['total_paid'],
                'total_due' => $sales['total_due'],
            ],
            'stock' => [
                'current_value' => $stock['total_stock_value'],
            ],
        ];
    }

    private function allStockFilter(): CurrentStockValueReportFilterDTO
    {
        return new CurrentStockValueReportFilterDTO(
            storeId: null,
            categoryId: null,
            brandId: null,
            availability: null,
            search: null,
            sortBy: CurrentStockValueReportSortColumn::StockValue,
            sortDirection: 'desc',
        );
    }
}
