<?php

use App\Enums\Permission;
use DA\Admin\Models\Tenant;
use DA\Inventory\Enums\PaymentMethod;
use DA\Inventory\Models\QuickSale;
use DA\Inventory\Models\QuickSaleItem;
use DA\Inventory\Models\Store;
use Illuminate\Database\Eloquent\Model;

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function quickSalePayload(int $storeId, array $overrides = []): array
{
    return array_replace([
        'store_id' => $storeId,
        'payment_method' => PaymentMethod::CASH->value,
        'received_amount' => '1000',
        'items' => [
            ['title' => 'Tea', 'unit_amount' => '50', 'quantity' => '3'],
            ['title' => null, 'unit_amount' => '500'],
        ],
    ], $overrides);
}

beforeEach(function () {
    $this->travelTo('2026-10-15 10:00:00');
});

describe('authentication and authorization', function () {
    it('returns 401 when no token is provided', function (string $method, string $uri) {
        $this->json($method, $uri)->assertUnauthorized();
    })->with([
        ['GET', '/api/v1/quick-sales'],
        ['GET', '/api/v1/quick-sales/summary'],
        ['POST', '/api/v1/quick-sales'],
        ['GET', '/api/v1/quick-sales/1'],
        ['GET', '/api/v1/dashboard/quick-sales-today'],
    ]);

    it('returns 403 when the user cannot create a quick sale', function () {
        actingAsTenantUser(permissions: [Permission::QuickSalesView]);

        $this->postJson('/api/v1/quick-sales', quickSalePayload(Store::factory()->create()->id))->assertForbidden();
    });

    it('returns 403 when the user cannot view quick sales', function () {
        actingAsTenantUser(permissions: [Permission::QuickSalesCreate]);

        $this->getJson('/api/v1/quick-sales')->assertForbidden();
        $this->getJson('/api/v1/quick-sales/summary')->assertForbidden();
    });

    it('returns 403 for the dashboard summary without dashboard access', function () {
        actingAsTenantUser(permissions: [Permission::QuickSalesView]);

        $this->getJson('/api/v1/dashboard/quick-sales-today')->assertForbidden();
    });
});

describe('creating a quick sale', function () {
    beforeEach(function () {
        $this->user = actingAsTenantUser(permissions: [Permission::QuickSalesCreate, Permission::QuickSalesView]);
        $this->store = Store::factory()->create();
    });

    it('saves the lines and recomputes the totals on the server', function () {
        $response = $this->postJson('/api/v1/quick-sales', quickSalePayload($this->store->id, [
            'grand_total' => '1',
        ]));

        $response->assertCreated()
            ->assertJsonPath('data.invoice_number', 'QS-20261015-0001')
            ->assertJsonPath('data.sale_date', '2026-10-15')
            ->assertJsonPath('data.total_items', 2)
            ->assertJsonPath('data.grand_total', '650.0000')
            ->assertJsonPath('data.received_amount', '1000.0000')
            ->assertJsonPath('data.change_amount', '350.0000')
            ->assertJsonPath('data.payment_method', 'Cash')
            ->assertJsonPath('data.cashier.id', $this->user->id)
            ->assertJsonPath('data.items.0.title', 'Tea')
            ->assertJsonPath('data.items.0.quantity', '3.0000')
            ->assertJsonPath('data.items.0.amount', '150.0000')
            ->assertJsonPath('data.items.1.title', null)
            ->assertJsonPath('data.items.1.quantity', '1.0000')
            ->assertJsonPath('data.items.1.amount', '500.0000');

        $quickSale = QuickSale::query()->sole();

        expect($quickSale->tenant_id)->toBe($this->user->tenant_id)
            ->and($quickSale->items()->pluck('amount')->all())->toBe(['150.0000', '500.0000']);
    });

    it('does not touch sales, payments, or stock', function () {
        $this->postJson('/api/v1/quick-sales', quickSalePayload($this->store->id))->assertCreated();

        $this->assertDatabaseCount('sales', 0);
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('stock_movements', 0);
    });

    it('numbers each quick sale of the day in sequence', function () {
        $this->postJson('/api/v1/quick-sales', quickSalePayload($this->store->id))->assertCreated();

        $this->postJson('/api/v1/quick-sales', quickSalePayload($this->store->id))
            ->assertCreated()
            ->assertJsonPath('data.invoice_number', 'QS-20261015-0002');
    });

    it('rejects a payment that does not cover the total', function () {
        $this->postJson('/api/v1/quick-sales', quickSalePayload($this->store->id, ['received_amount' => '649.99']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['received_amount' => 'The received amount must cover the quick sale total.']);

        $this->assertDatabaseCount('quick_sales', 0);
    });

    it('requires a store, a payment method, a received amount, and at least one line', function () {
        $this->postJson('/api/v1/quick-sales', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'store_id' => 'The store is required.',
                'payment_method',
                'received_amount',
                'items' => 'Add at least one amount before taking payment.',
            ]);
    });

    it('rejects a line without a positive amount', function (mixed $unitAmount) {
        $this->postJson('/api/v1/quick-sales', quickSalePayload($this->store->id, [
            'items' => [['title' => 'Bad', 'unit_amount' => $unitAmount]],
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['items.0.unit_amount']);
    })->with([0, -5, 'abc']);

    it('does not accept due as a payment method', function () {
        $this->postJson('/api/v1/quick-sales', quickSalePayload($this->store->id, [
            'payment_method' => PaymentMethod::DUE->value,
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['payment_method' => 'The selected payment method is invalid.']);
    });

    it('rejects a store that belongs to another tenant', function () {
        $foreignStore = Model::withoutEvents(fn () => Store::factory()->create(['tenant_id' => Tenant::factory()->create()->id]));

        $this->postJson('/api/v1/quick-sales', quickSalePayload($foreignStore->id))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['store_id' => 'The selected store is invalid.']);
    });
});

describe('listing quick sales', function () {
    beforeEach(function () {
        $this->user = actingAsTenantUser(permissions: [Permission::QuickSalesView]);
        $tenantId = (int) $this->user->tenant_id;

        $this->cashSale = QuickSale::factory()->create([
            'tenant_id' => $tenantId,
            'invoice_number' => 'QS-CASH',
            'total_items' => 2,
            'grand_total' => '300',
            'payment_method' => PaymentMethod::CASH,
        ]);
        QuickSaleItem::factory()->create(['tenant_id' => $tenantId, 'quick_sale_id' => $this->cashSale->id, 'title' => 'Tea']);

        QuickSale::factory()->create([
            'tenant_id' => $tenantId,
            'invoice_number' => 'QS-CARD',
            'total_items' => 1,
            'grand_total' => '100',
            'payment_method' => PaymentMethod::CARD,
        ]);

        $this->foreignSale = Model::withoutEvents(fn () => QuickSale::factory()->create([
            'tenant_id' => Tenant::factory()->create()->id,
            'invoice_number' => 'QS-FOREIGN',
            'grand_total' => '9000',
        ]));
    });

    it('lists only the tenant quick sales, newest first', function () {
        $this->getJson('/api/v1/quick-sales')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.invoice_number', 'QS-CARD')
            ->assertJsonPath('data.1.invoice_number', 'QS-CASH');
    });

    it('filters by payment method and by line title', function () {
        $this->getJson('/api/v1/quick-sales?payment_method='.PaymentMethod::CARD->value)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.invoice_number', 'QS-CARD');

        $this->getJson('/api/v1/quick-sales?search=tea')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.invoice_number', 'QS-CASH');
    });

    it('summarizes the matching quick sales', function () {
        $this->getJson('/api/v1/quick-sales/summary')
            ->assertOk()
            ->assertExactJson(['data' => [
                'total_quick_sales' => 2,
                'total_amount' => '400.0000',
                'total_items' => 3,
                'average_amount' => '200.0000',
            ]]);
    });

    it('shows a quick sale with its lines', function () {
        $this->getJson('/api/v1/quick-sales/'.$this->cashSale->id)
            ->assertOk()
            ->assertJsonPath('data.invoice_number', 'QS-CASH')
            ->assertJsonPath('data.items.0.title', 'Tea');
    });

    it('returns 404 for a quick sale of another tenant', function () {
        $this->getJson('/api/v1/quick-sales/'.$this->foreignSale->id)->assertNotFound();
    });
});

describe('dashboard summary for today', function () {
    it('totals only today quick sales of the tenant', function () {
        $user = actingAsTenantUser(permissions: [Permission::DashboardView]);
        $tenantId = (int) $user->tenant_id;

        QuickSale::factory()->create(['tenant_id' => $tenantId, 'sale_date' => '2026-10-15', 'total_items' => 2, 'grand_total' => '300', 'payment_method' => PaymentMethod::CASH]);
        QuickSale::factory()->create(['tenant_id' => $tenantId, 'sale_date' => '2026-10-15', 'total_items' => 1, 'grand_total' => '100', 'payment_method' => PaymentMethod::CARD]);
        QuickSale::factory()->create(['tenant_id' => $tenantId, 'sale_date' => '2026-10-14', 'grand_total' => '5000']);
        Model::withoutEvents(fn () => QuickSale::factory()->create([
            'tenant_id' => Tenant::factory()->create()->id,
            'sale_date' => '2026-10-15',
            'grand_total' => '7000',
        ]));

        $this->getJson('/api/v1/dashboard/quick-sales-today')
            ->assertOk()
            ->assertExactJson(['data' => [
                'today' => '2026-10-15',
                'quick_sales_count' => 2,
                'total_amount' => '400.0000',
                'total_items' => 3,
                'cash_amount' => '300.0000',
                'average_amount' => '200.0000',
            ]]);
    });
});
