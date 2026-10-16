<?php

namespace DA\Inventory\Services;

use Carbon\CarbonImmutable;
use DA\Inventory\Enums\DashboardTrend;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DashboardService
{
    /**
     * The percentage is relative to the absolute previous amount, so moving
     * from a loss to a smaller loss reads as growth. It is null when there is
     * no previous amount to compare against.
     *
     * @return array{change_amount: string, change_percentage: string|null, trend: string}
     */
    public function compareWithPrevious(string $current, string $previous): array
    {
        $changeAmount = Decimal::sub($current, $previous);

        return [
            'change_amount' => $changeAmount,
            'change_percentage' => Decimal::isZero($previous)
                ? null
                : Decimal::div(Decimal::mul($changeAmount, '100'), Decimal::abs($previous)),
            'trend' => $this->trendOf($changeAmount)->value,
        ];
    }

    public function dailyAverage(string $amount, int $days): string
    {
        return Decimal::div($amount, (string) max($days, 1));
    }

    /**
     * @param  Collection<int, object{sale_date: string, invoice_count: int, sales_amount: string, paid_amount: string, due_amount: string}>  $dailyTotals
     * @return list<array{date: string, invoice_count: int, sales_amount: string, paid_amount: string, due_amount: string}>
     */
    public function fillDailySales(Collection $dailyTotals, CarbonImmutable $dateFrom, CarbonImmutable $dateTo): array
    {
        $totalsByDate = $dailyTotals->keyBy(
            fn (object $row): string => Carbon::parse($row->sale_date)->toDateString(),
        );

        $days = [];

        for ($date = $dateFrom; $date->lte($dateTo); $date = $date->addDay()) {
            $row = $totalsByDate->get($date->toDateString());

            $days[] = [
                'date' => $date->toDateString(),
                'invoice_count' => (int) ($row?->invoice_count ?? 0),
                'sales_amount' => Decimal::normalize($row?->sales_amount ?? 0),
                'paid_amount' => Decimal::normalize($row?->paid_amount ?? 0),
                'due_amount' => Decimal::normalize($row?->due_amount ?? 0),
            ];
        }

        return $days;
    }

    /**
     * @param  list<array{invoice_count: int, sales_amount: string, paid_amount: string, due_amount: string}>  $days
     * @return array{invoice_count: int, sales_amount: string, paid_amount: string, due_amount: string}
     */
    public function totalDailySales(array $days): array
    {
        $totals = ['invoice_count' => 0, 'sales_amount' => '0.0000', 'paid_amount' => '0.0000', 'due_amount' => '0.0000'];

        foreach ($days as $day) {
            $totals['invoice_count'] += $day['invoice_count'];
            $totals['sales_amount'] = Decimal::add($totals['sales_amount'], $day['sales_amount']);
            $totals['paid_amount'] = Decimal::add($totals['paid_amount'], $day['paid_amount']);
            $totals['due_amount'] = Decimal::add($totals['due_amount'], $day['due_amount']);
        }

        return $totals;
    }

    private function trendOf(string $changeAmount): DashboardTrend
    {
        return match (true) {
            Decimal::isPositive($changeAmount) => DashboardTrend::Up,
            Decimal::isNegative($changeAmount) => DashboardTrend::Down,
            default => DashboardTrend::Unchanged,
        };
    }
}
