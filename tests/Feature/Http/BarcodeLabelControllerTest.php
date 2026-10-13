<?php

use App\Enums\Permission;
use DA\Admin\Models\Tenant;
use DA\Inventory\Models\Category;
use DA\Inventory\Models\Product;
use DA\Inventory\Models\ProductVariant;
use Illuminate\Database\Eloquent\Model;

/**
 * @param  array<string, mixed>  $productAttributes
 * @param  array<string, mixed>  $variantAttributes
 */
function barcodeLabelVariant(
    int $tenantId,
    Category $category,
    string $sku,
    string $name,
    array $productAttributes = [],
    array $variantAttributes = [],
): ProductVariant {
    $product = Product::factory()->create([
        'tenant_id' => $tenantId,
        'category_id' => $category->id,
        'name' => $name,
        ...$productAttributes,
    ]);

    return ProductVariant::factory()->create([
        'tenant_id' => $tenantId,
        'product_id' => $product->id,
        'sku' => $sku,
        ...$variantAttributes,
    ]);
}

describe('authentication and authorization', function () {
    it('returns 401 when no token is provided', function () {
        $this->getJson('/api/v1/barcode-labels/variants')->assertUnauthorized();
    });

    it('returns 403 when the user cannot print barcodes', function () {
        actingAsTenantUser(permissions: [Permission::ProductsView]);

        $this->getJson('/api/v1/barcode-labels/variants')->assertForbidden();
    });
});

describe('with catalog variants', function () {
    beforeEach(function () {
        $this->user = actingAsTenantUser(permissions: [Permission::BarcodesPrint]);
        $tenantId = (int) $this->user->tenant_id;

        $this->drinks = Category::factory()->create(['tenant_id' => $tenantId, 'name' => 'Drinks']);
        $this->soda = Category::factory()->childOf($this->drinks)->create(['name' => 'Soda']);
        $this->snacks = Category::factory()->create(['tenant_id' => $tenantId, 'name' => 'Snacks']);

        $this->cola = barcodeLabelVariant($tenantId, $this->soda, 'COLA-1', 'Cola', variantAttributes: [
            'barcode' => 'DAPR0000001',
            'barcode_type' => 'CODE128',
            'selling_price' => '1.5000',
        ]);
        $this->chips = barcodeLabelVariant($tenantId, $this->snacks, 'CHIPS-1', 'Chips');

        barcodeLabelVariant($tenantId, $this->snacks, 'INACTIVE-1', 'Inactive product', ['is_active' => false]);
        barcodeLabelVariant($tenantId, $this->snacks, 'OFF-1', 'Inactive variant', variantAttributes: ['is_active' => false]);
        barcodeLabelVariant($tenantId, $this->snacks, 'GONE-1', 'Deleted')->delete();

        Model::withoutEvents(function (): void {
            $otherTenant = Tenant::factory()->create();

            barcodeLabelVariant(
                $otherTenant->id,
                Category::factory()->create(['tenant_id' => $otherTenant->id]),
                'FOREIGN-1',
                'Foreign product',
            );
        });
    });

    it('lists only active variants of the current tenant with label fields', function () {
        $response = $this->getJson('/api/v1/barcode-labels/variants')->assertOk();

        expect(collect($response->json('data'))->pluck('sku')->all())->toBe(['CHIPS-1', 'COLA-1']);

        $response->assertJsonPath('data.1', [
            'product_variant_id' => $this->cola->id,
            'product_id' => $this->cola->product_id,
            'product_name' => 'Cola',
            'variant_name' => null,
            'sku' => 'COLA-1',
            'barcode' => 'DAPR0000001',
            'barcode_type' => 'CODE128',
            'selling_price' => '1.5000',
            'category_name' => 'Soda',
            'image_url' => asset('media/no-image-product.jpg'),
        ]);
    });

    it('filters by a parent category including its children', function () {
        $response = $this->getJson('/api/v1/barcode-labels/variants?category_id='.$this->drinks->id)->assertOk();

        expect(collect($response->json('data'))->pluck('sku')->all())->toBe(['COLA-1']);
    });

    it('searches by name, sku and barcode', function (string $term, string $expectedSku) {
        $response = $this->getJson('/api/v1/barcode-labels/variants?search='.$term)->assertOk();

        expect(collect($response->json('data'))->pluck('sku')->all())->toBe([$expectedSku]);
    })->with([
        'name' => ['chip', 'CHIPS-1'],
        'sku' => ['COLA-1', 'COLA-1'],
        'barcode' => ['DAPR0000001', 'COLA-1'],
    ]);

    it('does not leak another tenant\'s variants through search', function () {
        $response = $this->getJson('/api/v1/barcode-labels/variants?search=FOREIGN')->assertOk();

        expect($response->json('data'))->toBe([]);
    });

    it('rejects an oversized page', function () {
        $this->getJson('/api/v1/barcode-labels/variants?per_page=500')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('per_page');
    });
});
