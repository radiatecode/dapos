<?php

use App\Enums\Permission;
use DA\Admin\Models\Tenant;
use DA\Inventory\Enums\UnitType;
use DA\Inventory\Models\Unit;

describe('authentication and authorization', function () {
    it('returns 401 when no token is provided', function (string $method, string $uri) {
        $this->json($method, $uri, unitPayload())->assertUnauthorized();
    })->with([
        ['GET', '/api/v1/units'],
        ['POST', '/api/v1/units'],
        ['GET', '/api/v1/units/1'],
        ['PUT', '/api/v1/units/1'],
        ['DELETE', '/api/v1/units/1'],
    ]);

    it('returns 403 when the user lacks the required permission', function () {
        actingAsTenantUser(permissions: [Permission::BrandsView]);

        $this->getJson('/api/v1/units')->assertForbidden();
    });
});

describe('index and show', function () {
    it('lists units for the current tenant newest first', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::UnitsView]);
        $older = Unit::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Older']);
        $newer = Unit::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Newer']);
        Unit::factory()->create(['name' => 'Other tenant']);

        $this->getJson('/api/v1/units')
            ->assertOk()
            ->assertJsonPath('data.0.id', $newer->id)
            ->assertJsonPath('data.1.id', $older->id)
            ->assertJsonMissing(['name' => 'Other tenant']);
    });

    it('returns 404 when tenant A requests a tenant B unit', function () {
        $tenantA = Tenant::factory()->create();
        actingAsTenantUser($tenantA, [Permission::UnitsView]);
        $foreign = Unit::factory()->create();

        $this->getJson('/api/v1/units/'.$foreign->id)->assertNotFound();
    });
});

describe('store', function () {
    it('creates a unit and stores the tenant id from context', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::UnitsCreate]);

        $this->postJson('/api/v1/units', unitPayload())
            ->assertCreated()
            ->assertJsonPath('data.name', 'Kilogram')
            ->assertJsonPath('data.code', 'KG')
            ->assertJsonPath('data.unit_type', UnitType::Weight->value)
            ->assertJsonPath('data.precision', 3);

        $this->assertDatabaseHas('units', [
            'name' => 'Kilogram',
            'code' => 'KG',
            'tenant_id' => $tenant->id,
        ]);
    });

    it('returns 422 when the name is missing', function () {
        actingAsTenantUser(permissions: [Permission::UnitsCreate]);

        $this->postJson('/api/v1/units', unitPayload(['name' => '']))
            ->assertUnprocessable()
            ->assertJsonPath('errors.name.0', 'The name field is required.');
    });

    it('returns 422 when the code is already taken', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::UnitsCreate]);
        Unit::factory()->create([
            'tenant_id' => $tenant->id,
            'code' => 'KG',
        ]);

        $this->postJson('/api/v1/units', unitPayload())
            ->assertUnprocessable()
            ->assertJsonPath('errors.code.0', 'The code has already been taken.');
    });

    it('returns 422 when the unit type is invalid', function () {
        actingAsTenantUser(permissions: [Permission::UnitsCreate]);

        $this->postJson('/api/v1/units', unitPayload([
            'unit_type' => 'temperature',
        ]))->assertUnprocessable()
            ->assertJsonValidationErrors(['unit_type']);
    });
});

describe('update and destroy', function () {
    it('updates the unit', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::UnitsUpdate]);
        $unit = Unit::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Old',
            'code' => 'OLD',
        ]);

        $this->putJson('/api/v1/units/'.$unit->id, unitPayload([
            'name' => 'Liter',
            'short_name' => 'L',
            'code' => 'L',
            'unit_type' => UnitType::Volume->value,
            'precision' => 2,
        ]))
            ->assertOk()
            ->assertJsonPath('data.name', 'Liter')
            ->assertJsonPath('data.code', 'L')
            ->assertJsonPath('data.unit_type', UnitType::Volume->value);

        $this->assertDatabaseHas('units', [
            'id' => $unit->id,
            'name' => 'Liter',
            'code' => 'L',
        ]);
    });

    it('deletes the unit', function () {
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::UnitsDelete]);
        $unit = Unit::factory()->create(['tenant_id' => $tenant->id]);

        $this->deleteJson('/api/v1/units/'.$unit->id)->assertNoContent();

        $this->assertDatabaseMissing('units', ['id' => $unit->id]);
    });
});
