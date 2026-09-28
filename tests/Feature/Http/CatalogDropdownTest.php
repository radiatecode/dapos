<?php

use App\Enums\Permission;
use DA\Admin\Models\Tenant;
use DA\Inventory\Enums\UnitType;
use DA\Inventory\Models\Attribute;
use DA\Inventory\Models\AttributeValue;
use DA\Inventory\Models\Brand;
use DA\Inventory\Models\Category;
use DA\Inventory\Models\Store;
use DA\Inventory\Models\Supplier;
use DA\Inventory\Models\Unit;

describe('authentication and authorization', function () {
    it('returns 401 when no token is provided', function (string $uri) {
        $this->getJson($uri)->assertUnauthorized();
    })->with([
        '/api/v1/categories-dropdown',
        '/api/v1/brands-dropdown',
        '/api/v1/units-dropdown',
        '/api/v1/attribute-values-dropdown',
        '/api/v1/suppliers-dropdown',
        '/api/v1/stores-dropdown',
    ]);

    it('returns 403 when the user lacks the required permission', function (string $uri, Permission $granted) {
        actingAsTenantUser(permissions: [$granted]);

        $this->getJson($uri)->assertForbidden();
    })->with([
        ['/api/v1/categories-dropdown', Permission::BrandsView],
        ['/api/v1/brands-dropdown', Permission::UnitsView],
        ['/api/v1/units-dropdown', Permission::BrandsView],
        ['/api/v1/attribute-values-dropdown', Permission::BrandsView],
        ['/api/v1/suppliers-dropdown', Permission::StoresView],
        ['/api/v1/stores-dropdown', Permission::SuppliersView],
    ]);
});

describe('catalog dropdowns', function () {
    it('returns tenant categories in select2 format and hides other tenants', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::CategoriesView]);
        $match = Category::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Beverages',
            'code' => 'BEV',
        ]);
        Category::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Snacks',
        ]);
        Category::withoutEvents(fn () => Category::factory()->create([
            'name' => 'Foreign category',
        ]));

        $this->getJson('/api/v1/categories-dropdown?search=Bev')
            ->assertOk()
            ->assertJsonPath('results.0.id', $match->id)
            ->assertJsonPath('results.0.label', 'Beverages')
            ->assertJsonPath('results.0.code', 'BEV')
            ->assertJsonPath('pagination.more', false)
            ->assertJsonCount(1, 'results')
            ->assertJsonMissing(['label' => 'Foreign category']);
    });

    it('returns tenant brands in select2 format and hides other tenants', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::BrandsView]);
        $match = Brand::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Acme Cola',
            'code' => 'ACME',
        ]);
        Brand::withoutEvents(fn () => Brand::factory()->create([
            'name' => 'Foreign brand',
        ]));

        $this->getJson('/api/v1/brands-dropdown?search=Acme')
            ->assertOk()
            ->assertJsonPath('results.0.id', $match->id)
            ->assertJsonPath('results.0.label', 'Acme Cola')
            ->assertJsonPath('pagination.more', false)
            ->assertJsonMissing(['label' => 'Foreign brand']);
    });

    it('returns tenant units in select2 format and hides other tenants', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::UnitsView]);
        $match = new Unit;
        $match->tenant_id = $tenant->id;
        $match->name = 'Piece';
        $match->code = 'PC';
        $match->unit_type = UnitType::Quantity;
        $match->precision = 0;
        $match->is_active = true;
        $match->save();

        $foreignTenant = Tenant::factory()->create();
        Unit::withoutEvents(function () use ($foreignTenant): void {
            $foreign = new Unit;
            $foreign->tenant_id = $foreignTenant->id;
            $foreign->name = 'Foreign unit';
            $foreign->code = 'FU';
            $foreign->unit_type = UnitType::Quantity;
            $foreign->precision = 0;
            $foreign->is_active = true;
            $foreign->save();
        });

        $this->getJson('/api/v1/units-dropdown?search=Pie')
            ->assertOk()
            ->assertJsonPath('results.0.id', $match->id)
            ->assertJsonPath('results.0.label', 'Piece')
            ->assertJsonPath('pagination.more', false)
            ->assertJsonMissing(['label' => 'Foreign unit']);
    });

    it('returns an empty attribute value dropdown when attribute_id is missing', function () {
        actingAsTenantUser(permissions: [Permission::AttributesView]);
        AttributeValue::factory()->create(['value' => 'Red']);

        $this->getJson('/api/v1/attribute-values-dropdown')
            ->assertOk()
            ->assertJsonPath('pagination.more', false)
            ->assertJsonCount(0, 'results');
    });

    it('returns values for the selected attribute and hides other attributes and tenants', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::AttributesView]);
        $color = Attribute::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Color',
        ]);
        $size = Attribute::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Size',
        ]);
        $red = AttributeValue::factory()->create([
            'attribute_id' => $color->id,
            'value' => 'Red',
            'code' => 'RD',
        ]);
        AttributeValue::factory()->create([
            'attribute_id' => $size->id,
            'value' => 'Large',
        ]);
        AttributeValue::factory()->create(['value' => 'Foreign red']);

        $this->getJson('/api/v1/attribute-values-dropdown?attribute_id='.$color->id.'&search=Re')
            ->assertOk()
            ->assertJsonCount(1, 'results')
            ->assertJsonPath('results.0.id', $red->id)
            ->assertJsonPath('results.0.label', 'Red')
            ->assertJsonPath('results.0.code', 'RD')
            ->assertJsonPath('results.0.attribute_id', $color->id)
            ->assertJsonMissing(['label' => 'Large'])
            ->assertJsonMissing(['label' => 'Foreign red']);
    });

    it('returns tenant suppliers in select2 format and hides other tenants', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::SuppliersView]);
        $match = Supplier::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Acme Supplies',
            'email' => 'orders@acme.test',
            'phone' => '01700000000',
        ]);
        Supplier::withoutEvents(fn () => Supplier::factory()->create([
            'name' => 'Foreign supplier',
        ]));

        $this->getJson('/api/v1/suppliers-dropdown?search=Acme')
            ->assertOk()
            ->assertJsonPath('results.0.id', $match->id)
            ->assertJsonPath('results.0.label', 'Acme Supplies')
            ->assertJsonPath('results.0.email', 'orders@acme.test')
            ->assertJsonPath('results.0.phone', '01700000000')
            ->assertJsonPath('pagination.more', false)
            ->assertJsonMissing(['label' => 'Foreign supplier']);
    });

    it('returns tenant stores in select2 format and hides other tenants', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::StoresView]);
        $match = Store::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Main Store',
        ]);
        Store::withoutEvents(fn () => Store::factory()->create([
            'name' => 'Foreign store',
        ]));

        $this->getJson('/api/v1/stores-dropdown?search=Main')
            ->assertOk()
            ->assertJsonPath('results.0.id', $match->id)
            ->assertJsonPath('results.0.label', 'Main Store')
            ->assertJsonPath('results.0.name', 'Main Store')
            ->assertJsonPath('pagination.more', false)
            ->assertJsonMissing(['label' => 'Foreign store']);
    });
});
