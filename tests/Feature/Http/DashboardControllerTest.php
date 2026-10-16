<?php

use App\Enums\Permission;
use DA\Admin\Models\Tenant;
use DA\Inventory\Models\InventoryBalance;
use DA\Inventory\Models\ProductVariant;
use DA\Inventory\Models\Purchase;
use DA\Inventory\Models\Sale;
use DA\Inventory\Models\SaleItem;
use Illuminate\Database\Eloquent\Model;

function dashboardSale(ProductVariant $variant, string $saleDate, string $grandTotal, string $paidAmount, string $cogs): Sale
{
    $sale = Sale::factory()->create([
        'tenant_id' => $variant->tenant_id,
        'sale_date' => $saleDate,
        'subtotal' => $grandTotal,
        'grand_total' => $grandTotal,
        'paid_amount' => $paidAmount,
        'due_amount' => bcsub($grandTotal, $paidAmount, 4),
    ]);

    SaleItem::factory()->create([
        'tenant_id' => $variant->tenant_id,
        'sale_id' => $sale->id,
        'product_variant_id' => $variant->id,
        'product_id' => $variant->product_id,
        'subtotal' => $grandTotal,
        'total' => $grandTotal,
        'cogs' => $cogs,
    ]);

    return $sale;
}

beforeEach(function () {
    $this->travelTo('2026-10-15 10:00:00');
});

describe('authentication and authorization', function () {
    it('returns 401 when no token is provided', function (string $uri) {
        $this->getJson($uri)->assertUnauthorized();
    })->with([
        '/api/v1/dashboard/summary',
        '/api/v1/dashboard/sales-trend',
    ]);

    it('returns 403 when the user cannot view the dashboard', function (string $uri) {
        actingAsTenantUser(permissions: [Permission::ReportsView]);

        $this->getJson($uri)->assertForbidden();
    })->with([
        '/api/v1/dashboard/summary',
        '/api/v1/dashboard/sales-trend',
    ]);
});

describe('with recorded activity', function () {
    beforeEach(function () {
        $user = actingAsTenantUser(permissions: [Permission::DashboardView]);
        $tenantId = (int) $user->tenant_id;
        $variant = ProductVariant::factory()->create(['tenant_id' => $tenantId]);

        dashboardSale($variant, '2026-10-15', '100', '60', '70');
        dashboardSale($variant, '2026-10-09', '40', '40', '30');
        dashboardSale($variant, '2026-10-03', '200', '200', '120');
        dashboardSale($variant, '2026-09-20', '150', '150', '100');
        dashboardSale($variant, '2026-08-10', '50', '0', '10');

        Purchase::factory()->received()->create(['tenant_id' => $tenantId, 'order_date' => '2026-10-15', 'grand_total' => '30']);
        Purchase::factory()->received()->create(['tenant_id' => $tenantId, 'order_date' => '2026-10-02', 'grand_total' => '70']);
        Purchase::factory()->received()->create(['tenant_id' => $tenantId, 'order_date' => '2026-09-01', 'grand_total' => '100']);
        Purchase::factory()->create(['tenant_id' => $tenantId, 'order_date' => '2026-10-15', 'grand_total' => '999']);

        InventoryBalance::factory()->count(2)->create(['tenant_id' => $tenantId, 'stock_value' => '50']);

        Model::withoutEvents(function (): void {
            $otherTenant = Tenant::factory()->create();
            $otherVariant = ProductVariant::factory()->create(['tenant_id' => $otherTenant->id]);

            dashboardSale($otherVariant, '2026-10-15', '5000', '5000', '1000');
            Purchase::factory()->received()->create(['tenant_id' => $otherTenant->id, 'order_date' => '2026-10-15', 'grand_total' => '5000']);
            InventoryBalance::factory()->create(['tenant_id' => $otherTenant->id, 'stock_value' => '5000']);
        });
    });

    it('summarizes sales, received purchases, profit, payments, and stock value for the tenant only', function () {
        $this->getJson('/api/v1/dashboard/summary')
            ->assertOk()
            ->assertExactJson(['data' => [
                'period' => [
                    'today' => '2026-10-15',
                    'current_month_start' => '2026-10-01',
                    'previous_month_start' => '2026-09-01',
                    'previous_month_end' => '2026-09-30',
                    'days_elapsed' => 15,
                ],
                'sales' => [
                    'total' => '540.0000',
                    'today' => '100.0000',
                    'current_month' => '340.0000',
                    'previous_month' => '150.0000',
                    'current_month_daily_average' => '22.6666',
                    'current_month_change' => [
                        'change_amount' => '190.0000',
                        'change_percentage' => '126.6666',
                        'trend' => 'up',
                    ],
                ],
                'purchases' => [
                    'total' => '200.0000',
                    'today' => '30.0000',
                    'current_month' => '100.0000',
                ],
                'profit' => [
                    'current_month' => '120.0000',
                    'previous_month' => '50.0000',
                    'current_month_change' => [
                        'change_amount' => '70.0000',
                        'change_percentage' => '140.0000',
                        'trend' => 'up',
                    ],
                ],
                'payments' => [
                    'total_paid' => '450.0000',
                    'total_due' => '90.0000',
                ],
                'stock' => [
                    'current_value' => '100.0000',
                ],
            ]]);
    });

    it('lists the last seven days oldest first with empty days filled with zero', function () {
        $response = $this->getJson('/api/v1/dashboard/sales-trend')
            ->assertOk()
            ->assertJsonPath('data.date_from', '2026-10-09')
            ->assertJsonPath('data.date_to', '2026-10-15')
            ->assertJsonCount(7, 'data.days')
            ->assertJsonPath('data.days.0', [
                'date' => '2026-10-09',
                'invoice_count' => 1,
                'sales_amount' => '40.0000',
                'paid_amount' => '40.0000',
                'due_amount' => '0.0000',
            ])
            ->assertJsonPath('data.days.6', [
                'date' => '2026-10-15',
                'invoice_count' => 1,
                'sales_amount' => '100.0000',
                'paid_amount' => '60.0000',
                'due_amount' => '40.0000',
            ])
            ->assertJsonPath('data.totals', [
                'invoice_count' => 2,
                'sales_amount' => '140.0000',
                'paid_amount' => '100.0000',
                'due_amount' => '40.0000',
            ]);

        expect(collect($response->json('data.days'))->pluck('date')->all())->toBe([
            '2026-10-09', '2026-10-10', '2026-10-11', '2026-10-12', '2026-10-13', '2026-10-14', '2026-10-15',
        ])
            ->and($response->json('data.days.3.sales_amount'))->toBe('0.0000');
    });
});

describe('month comparison', function () {
    it('reports a decline when this month is below last month', function () {
        $user = actingAsTenantUser(permissions: [Permission::DashboardView]);
        $variant = ProductVariant::factory()->create(['tenant_id' => $user->tenant_id]);

        dashboardSale($variant, '2026-10-05', '50', '50', '40');
        dashboardSale($variant, '2026-09-10', '200', '200', '100');

        $this->getJson('/api/v1/dashboard/summary')
            ->assertOk()
            ->assertJsonPath('data.sales.current_month_change', [
                'change_amount' => '-150.0000',
                'change_percentage' => '-75.0000',
                'trend' => 'down',
            ])
            ->assertJsonPath('data.profit.current_month_change', [
                'change_amount' => '-90.0000',
                'change_percentage' => '-90.0000',
                'trend' => 'down',
            ]);
    });

    it('leaves the percentage empty when there is nothing to compare against', function () {
        actingAsTenantUser(permissions: [Permission::DashboardView]);

        $this->getJson('/api/v1/dashboard/summary')
            ->assertOk()
            ->assertJsonPath('data.sales.total', '0.0000')
            ->assertJsonPath('data.stock.current_value', '0.0000')
            ->assertJsonPath('data.profit.current_month_change', [
                'change_amount' => '0.0000',
                'change_percentage' => null,
                'trend' => 'unchanged',
            ]);
    });
});
