<?php

use App\Enums\Permission;
use App\Models\Role;
use App\Models\User;
use DA\Admin\Models\Tenant;
use DA\Inventory\Actions\Category\UpdateCategory;
use DA\Inventory\Actions\Product\UpdateProduct;
use DA\Inventory\Actions\Purchase\UpdatePurchase;
use DA\Inventory\Actions\PurchaseReturn\UpdatePurchaseReturn;
use DA\Inventory\Actions\StockAdjustment\UpdateStockAdjustment;
use DA\Inventory\Actions\StockTransfer\UpdateStockTransfer;
use DA\Inventory\DTO\Category\CategoryDTO;
use DA\Inventory\DTO\Product\ProductDTO;
use DA\Inventory\DTO\Purchase\PurchaseDTO;
use DA\Inventory\DTO\PurchaseReturn\PurchaseReturnDTO;
use DA\Inventory\DTO\StockAdjustment\StockAdjustmentDTO;
use DA\Inventory\DTO\StockTransfer\StockTransferDTO;
use DA\Inventory\Enums\ItemTaxType;
use DA\Inventory\Enums\ProductType;
use DA\Inventory\Enums\PurchasePaymentStatus;
use DA\Inventory\Enums\PurchaseReturnStatus;
use DA\Inventory\Enums\PurchaseStatus;
use DA\Inventory\Enums\StockAdjustmentStatus;
use DA\Inventory\Enums\StockAdjustmentType;
use DA\Inventory\Enums\StockTransferStatus;
use DA\Inventory\Enums\TaxCalculationBase;
use DA\Inventory\Enums\TaxMode;
use DA\Inventory\Enums\TaxType;
use DA\Inventory\Models\Attribute;
use DA\Inventory\Models\Brand;
use DA\Inventory\Models\Category;
use DA\Inventory\Models\Customer;
use DA\Inventory\Models\Product;
use DA\Inventory\Models\Purchase;
use DA\Inventory\Models\PurchaseReturn;
use DA\Inventory\Models\StockAdjustment;
use DA\Inventory\Models\StockTransfer;
use DA\Inventory\Models\Store;
use DA\Inventory\Models\Tax;
use DA\Inventory\Models\TaxSetting;
use DA\Inventory\Models\Unit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;

/**
 * @return array{0: Tenant, 1: User}
 */
function actorBlockedFromOthersRecords(Permission $ability, Permission $ownership): array
{
    $tenant = Tenant::factory()->create();
    $creator = User::factory()->forTenant($tenant)->create();
    $role = Role::factory()->create([
        'tenant_id' => $tenant->id,
        'permissions' => [$ability->value, $ownership->value],
    ]);
    $actor = User::factory()->forTenant($tenant)->create([
        'role_id' => $role->id,
    ]);
    Sanctum::actingAs($actor);

    return [$tenant, $creator];
}

function expectNonCreatorRejected(callable $action, string $field, string $message): void
{
    try {
        $action();
    } catch (ValidationException $exception) {
        expect($exception->errors())->toBe([
            $field => [$message],
        ]);

        return;
    }

    test()->fail('Expected a non-creator to be rejected.');
}

describe('creator ownership', function () {
    it('updates a brand when the only-creator user created it', function () {
        $tenant = Tenant::factory()->create();
        $role = Role::factory()->create([
            'tenant_id' => $tenant->id,
            'permissions' => [Permission::BrandsUpdate->value, Permission::OnlyCreatorUpdate->value],
        ]);
        $user = User::factory()->forTenant($tenant)->create([
            'role_id' => $role->id,
        ]);
        Sanctum::actingAs($user);
        $brand = Brand::factory()->create([
            'tenant_id' => $tenant->id,
            'created_by' => $user->id,
            'name' => 'Old',
            'slug' => 'old',
        ]);

        $this->postJson('/api/v1/brands/'.$brand->id, [
            'name' => 'Updated',
            'slug' => 'updated',
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated');

        $this->assertDatabaseHas('brands', [
            'id' => $brand->id,
            'name' => 'Updated',
        ]);
    });

    it('returns 422 when a non-creator updates a catalog record', function (
        string $method,
        string $uri,
        string $modelClass,
        Permission $permission,
        array $payload,
        string $field,
        string $message,
        string $column,
        string $original,
    ) {
        [$tenant, $creator] = actorBlockedFromOthersRecords($permission, Permission::OnlyCreatorUpdate);
        /** @var Model $record */
        $record = $modelClass::factory()->create([
            'tenant_id' => $tenant->id,
            'created_by' => $creator->id,
            $column => $original,
        ]);

        $this->json($method, str_replace('{id}', (string) $record->getKey(), $uri), $payload)
            ->assertUnprocessable()
            ->assertJsonPath('errors.'.$field.'.0', $message);

        $this->assertDatabaseHas($record->getTable(), [
            'id' => $record->getKey(),
            $column => $original,
        ]);
    })->with([
        'brand' => [
            'POST',
            '/api/v1/brands/{id}',
            Brand::class,
            Permission::BrandsUpdate,
            ['name' => 'Updated', 'slug' => 'updated'],
            'brand',
            'You are not authorized to update this brand.',
            'name',
            'Old',
        ],
        'unit' => [
            'PUT',
            '/api/v1/units/{id}',
            Unit::class,
            Permission::UnitsUpdate,
            unitPayload(['name' => 'Gram', 'code' => 'G']),
            'unit',
            'You are not authorized to update this unit.',
            'name',
            'Kilogram',
        ],
        'attribute' => [
            'PUT',
            '/api/v1/attributes/{id}',
            Attribute::class,
            Permission::AttributesUpdate,
            attributePayload(['name' => 'Size', 'slug' => 'size']),
            'attribute',
            'You are not authorized to update this attribute.',
            'name',
            'Color',
        ],
        'customer' => [
            'PUT',
            '/api/v1/customers/{id}',
            Customer::class,
            Permission::CustomersUpdate,
            customerPayload(['name' => 'Someone Else']),
            'customer',
            'You are not authorized to update this customer.',
            'name',
            'Nadia Rahman',
        ],
        'store' => [
            'PUT',
            '/api/v1/stores/{id}',
            Store::class,
            Permission::StoresUpdate,
            storePayload(['name' => 'Other Store']),
            'store',
            'You are not authorized to update this store.',
            'name',
            'Main Store',
        ],
        'tax' => [
            'PUT',
            '/api/v1/taxes/{id}',
            Tax::class,
            Permission::TaxesUpdate,
            [
                'tax_name' => 'Changed Tax',
                'tax_type' => TaxType::PERCENTAGE->value,
                'tax_rate' => 10,
                'is_active' => true,
            ],
            'tax',
            'You are not authorized to update this tax.',
            'tax_name',
            'Sales Tax',
        ],
    ]);

    it('returns 422 when a non-creator deletes a catalog record', function (
        string $uri,
        string $modelClass,
        Permission $permission,
        string $field,
        string $message,
    ) {
        [$tenant, $creator] = actorBlockedFromOthersRecords($permission, Permission::OnlyCreatorDelete);
        /** @var Model $record */
        $record = $modelClass::factory()->create([
            'tenant_id' => $tenant->id,
            'created_by' => $creator->id,
        ]);

        $this->postJson($uri, [
            'action' => 'delete',
            'ids' => [$record->getKey()],
        ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.'.$field.'.0', $message);

        $this->assertModelExists($record);
    })->with([
        'brand' => ['/api/v1/brands/delete', Brand::class, Permission::BrandsDelete, 'brand', 'You are not authorized to delete this brand.'],
        'category' => ['/api/v1/categories/delete', Category::class, Permission::CategoriesDelete, 'category', 'You are not authorized to delete this category.'],
        'unit' => ['/api/v1/units/delete', Unit::class, Permission::UnitsDelete, 'unit', 'You are not authorized to delete this unit.'],
        'attribute' => ['/api/v1/attributes/delete', Attribute::class, Permission::AttributesDelete, 'attribute', 'You are not authorized to delete this attribute.'],
        'customer' => ['/api/v1/customers/delete', Customer::class, Permission::CustomersDelete, 'customer', 'You are not authorized to delete this customer.'],
        'store' => ['/api/v1/stores/delete', Store::class, Permission::StoresDelete, 'store', 'You are not authorized to delete this store.'],
        'tax' => ['/api/v1/taxes/delete', Tax::class, Permission::TaxesDelete, 'tax', 'You are not authorized to delete this tax.'],
        'purchase' => ['/api/v1/purchases/delete', Purchase::class, Permission::PurchasingDelete, 'purchase', 'You are not authorized to delete this purchase.'],
        'purchase return' => ['/api/v1/purchase-returns/delete', PurchaseReturn::class, Permission::PurchasingDelete, 'purchase_return', 'You are not authorized to delete this purchase return.'],
    ]);

    it('returns 422 when a non-creator changes a document status', function (
        string $uri,
        string $modelClass,
        Permission $permission,
        string $status,
        string $field,
        string $message,
        string $originalStatus,
    ) {
        [$tenant, $creator] = actorBlockedFromOthersRecords($permission, Permission::OnlyCreatorUpdate);
        /** @var Model $record */
        $record = $modelClass::factory()->create([
            'tenant_id' => $tenant->id,
            'created_by' => $creator->id,
        ]);

        $this->putJson(str_replace('{id}', (string) $record->getKey(), $uri), [
            'status' => $status,
        ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.'.$field.'.0', $message);

        $this->assertDatabaseHas($record->getTable(), [
            'id' => $record->getKey(),
            'status' => $originalStatus,
        ]);
    })->with([
        'purchase' => [
            '/api/v1/purchases/{id}/status',
            Purchase::class,
            Permission::PurchasingUpdate,
            PurchaseStatus::Ordered->value,
            'purchase',
            'You are not authorized to update this purchase.',
            PurchaseStatus::Pending->value,
        ],
        'purchase return' => [
            '/api/v1/purchase-returns/{id}/status',
            PurchaseReturn::class,
            Permission::PurchasingUpdate,
            PurchaseReturnStatus::Approved->value,
            'purchase_return',
            'You are not authorized to update this purchase return.',
            PurchaseReturnStatus::Draft->value,
        ],
        'stock adjustment' => [
            '/api/v1/stock-adjustments/{id}/status',
            StockAdjustment::class,
            Permission::InventoryUpdate,
            StockAdjustmentStatus::Cancelled->value,
            'stock_adjustment',
            'You are not authorized to update this stock adjustment.',
            StockAdjustmentStatus::Draft->value,
        ],
        'stock transfer' => [
            '/api/v1/stock-transfers/{id}/status',
            StockTransfer::class,
            Permission::InventoryUpdate,
            StockTransferStatus::Pending->value,
            'stock_transfer',
            'You are not authorized to update this stock transfer.',
            StockTransferStatus::Draft->value,
        ],
    ]);

    it('returns 422 when a non-creator updates the tax setting', function () {
        [$tenant, $creator] = actorBlockedFromOthersRecords(Permission::TaxesUpdate, Permission::OnlyCreatorUpdate);
        $setting = new TaxSetting;
        $setting->tenant_id = $tenant->id;
        $setting->tax_mode = TaxMode::ORDER_LEVEL->value;
        $setting->tax_calculation_base = TaxCalculationBase::EXCLUDE_SERVICE_CHARGES->value;
        $setting->created_by = $creator->id;
        $setting->save();

        $this->putJson('/api/v1/tax-settings', [
            'tax_mode' => TaxMode::ITEM_LEVEL->value,
            'tax_calculation_base' => TaxCalculationBase::INCLUDE_SERVICE_CHARGES->value,
        ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.tax_setting.0', 'You are not authorized to update this tax setting.');

        $this->assertDatabaseHas('tax_settings', [
            'id' => $setting->id,
            'tax_mode' => TaxMode::ORDER_LEVEL->value,
        ]);
    });

    it('rejects a category update from a non-creator', function () {
        [$tenant, $creator] = actorBlockedFromOthersRecords(Permission::CategoriesUpdate, Permission::OnlyCreatorUpdate);
        $category = Category::factory()->create([
            'tenant_id' => $tenant->id,
            'created_by' => $creator->id,
            'name' => 'Beverages',
        ]);

        expectNonCreatorRejected(
            fn () => app(UpdateCategory::class)->handle($category, new CategoryDTO(
                name: 'Renamed',
                slug: 'renamed',
                code: null,
                description: null,
                parentId: null,
                image: null,
                sortOrder: 0,
                isActive: true,
            )),
            'category',
            'You are not authorized to update this category.',
        );

        expect($category->fresh()->name)->toBe('Beverages');
    });

    it('rejects a product update from a non-creator', function () {
        [$tenant, $creator] = actorBlockedFromOthersRecords(Permission::ProductsUpdate, Permission::OnlyCreatorUpdate);
        $product = Product::factory()->create([
            'tenant_id' => $tenant->id,
            'created_by' => $creator->id,
            'name' => 'Cola',
        ]);

        expectNonCreatorRejected(
            fn () => app(UpdateProduct::class)->handle($product->id, new ProductDTO(
                categoryId: $product->category_id,
                brandId: $product->brand_id,
                unitId: $product->unit_id,
                name: 'Renamed',
                slug: null,
                description: null,
                image: null,
                productType: ProductType::Simple,
                trackInventory: false,
                isActive: true,
                isStockOut: false,
                manufactureDate: null,
                expireDate: null,
                warrantyInDays: null,
                guaranteeInDays: null,
                attributes: [],
                variants: [],
                storeId: null,
                taxIds: [],
                itemTaxType: ItemTaxType::EXCLUSIVE->value,
            )),
            'product',
            'You are not authorized to update this product.',
        );

        expect($product->fresh()->name)->toBe('Cola');
    });

    it('rejects a purchase update from a non-creator', function () {
        [$tenant, $creator] = actorBlockedFromOthersRecords(Permission::PurchasingUpdate, Permission::OnlyCreatorUpdate);
        $purchase = Purchase::factory()->create([
            'tenant_id' => $tenant->id,
            'created_by' => $creator->id,
            'notes' => 'keep',
        ]);

        expectNonCreatorRejected(
            fn () => app(UpdatePurchase::class)->handle($purchase->id, new PurchaseDTO(
                supplierId: $purchase->supplier_id,
                storeId: $purchase->store_id,
                poNumber: $purchase->po_number,
                orderDate: $purchase->order_date->toDateString(),
                currency: 'BDT',
                discountAmount: '0',
                taxAmount: '0',
                shippingAmount: '0',
                status: PurchaseStatus::Pending,
                paymentStatus: PurchasePaymentStatus::Unpaid,
                notes: 'changed',
                items: [],
            )),
            'purchase',
            'You are not authorized to update this purchase.',
        );

        expect($purchase->fresh()->notes)->toBe('keep');
    });

    it('rejects a purchase return update from a non-creator', function () {
        [$tenant, $creator] = actorBlockedFromOthersRecords(Permission::PurchasingUpdate, Permission::OnlyCreatorUpdate);
        $return = PurchaseReturn::factory()->create([
            'tenant_id' => $tenant->id,
            'created_by' => $creator->id,
            'notes' => 'keep',
        ]);

        expectNonCreatorRejected(
            fn () => app(UpdatePurchaseReturn::class)->handle($return->id, new PurchaseReturnDTO(
                purchaseId: $return->purchase_id,
                returnDate: $return->return_date->toDateString(),
                discountAmount: '0',
                taxAmount: '0',
                status: PurchaseReturnStatus::Draft,
                reason: null,
                notes: 'changed',
                items: [],
            )),
            'purchase_return',
            'You are not authorized to update this purchase return.',
        );

        expect($return->fresh()->notes)->toBe('keep');
    });

    it('rejects a stock adjustment update from a non-creator', function () {
        [$tenant, $creator] = actorBlockedFromOthersRecords(Permission::InventoryUpdate, Permission::OnlyCreatorUpdate);
        $adjustment = StockAdjustment::factory()->create([
            'tenant_id' => $tenant->id,
            'created_by' => $creator->id,
            'notes' => 'keep',
        ]);

        expectNonCreatorRejected(
            fn () => app(UpdateStockAdjustment::class)->handle($adjustment->id, new StockAdjustmentDTO(
                storeId: $adjustment->store_id,
                productVariantId: $adjustment->product_variant_id,
                adjustmentDate: $adjustment->adjustment_date->toDateString(),
                adjustmentType: StockAdjustmentType::Addition,
                reason: 'Count correction',
                adjustedQuantity: '2',
                unitCost: '5',
                notes: 'changed',
                status: StockAdjustmentStatus::Draft,
            )),
            'stock_adjustment',
            'You are not authorized to update this stock adjustment.',
        );

        expect($adjustment->fresh()->notes)->toBe('keep');
    });

    it('rejects a stock transfer update from a non-creator', function () {
        [$tenant, $creator] = actorBlockedFromOthersRecords(Permission::InventoryUpdate, Permission::OnlyCreatorUpdate);
        $transfer = StockTransfer::factory()->create([
            'tenant_id' => $tenant->id,
            'created_by' => $creator->id,
            'notes' => 'keep',
        ]);

        expectNonCreatorRejected(
            fn () => app(UpdateStockTransfer::class)->handle($transfer->id, new StockTransferDTO(
                productVariantId: $transfer->product_variant_id,
                fromStoreId: $transfer->from_store_id,
                toStoreId: $transfer->to_store_id,
                transferDate: $transfer->transfer_date->toDateString(),
                quantity: '2',
                notes: 'changed',
                status: StockTransferStatus::Draft,
            )),
            'stock_transfer',
            'You are not authorized to update this stock transfer.',
        );

        expect($transfer->fresh()->notes)->toBe('keep');
    });
});
