<?php

use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

describe('authentication and authorization', function () {
    it('returns 401 when no token is provided', function (string $method, string $uri) {
        $this->json($method, $uri, ['name' => 'Acme'])->assertUnauthorized();
    })->with([
        ['GET', '/api/tenants'],
        ['POST', '/api/tenants'],
        ['GET', '/api/tenants/1'],
        ['PUT', '/api/tenants/1'],
        ['DELETE', '/api/tenants/1'],
    ]);

    it('returns 403 for a tenant user', function (string $method, string $uri) {
        $tenant = Tenant::factory()->create();
        $other = Tenant::factory()->create();
        $user = User::factory()->forTenant($tenant)->create();

        Sanctum::actingAs($user);

        $this->json($method, str_replace('{id}', (string) $other->id, $uri), tenantPayload())
            ->assertForbidden();
    })->with([
        ['GET', '/api/tenants'],
        ['POST', '/api/tenants'],
        ['GET', '/api/tenants/{id}'],
        ['PUT', '/api/tenants/{id}'],
        ['DELETE', '/api/tenants/{id}'],
    ]);
});

describe('index', function () {
    it('lists tenants for a platform admin newest first', function () {
        $first = Tenant::factory()->create(['name' => 'First']);
        $second = Tenant::factory()->create(['name' => 'Second']);

        Sanctum::actingAs(User::factory()->platformAdmin()->create());

        $this->getJson('/api/tenants')
            ->assertOk()
            ->assertJsonPath('data.0.id', $second->id)
            ->assertJsonPath('data.1.id', $first->id)
            ->assertJsonPath('meta.total', 2);
    });
});

describe('store', function () {
    it('creates a tenant and generates a slug from the name', function () {
        Sanctum::actingAs(User::factory()->platformAdmin()->create());

        $this->postJson('/api/tenants', tenantPayload())
            ->assertCreated()
            ->assertJsonPath('data.name', 'Acme Coffee')
            ->assertJsonPath('data.slug', 'acme-coffee')
            ->assertJsonPath('data.status', TenantStatus::Active->value)
            ->assertJsonPath('data.timezone', 'Asia/Dhaka')
            ->assertJsonPath('data.currency', 'BDT')
            ->assertJsonPath('data.address.city', 'Dhaka')
            ->assertJsonPath('data.contact.email', 'jane@acme.test')
            ->assertJsonPath('data.billing.tax_id', 'TAX-123');

        $this->assertDatabaseHas('tenants', [
            'name' => 'Acme Coffee',
            'slug' => 'acme-coffee',
            'contact_email' => 'jane@acme.test',
        ]);
    });

    it('stores an uploaded logo', function () {
        Storage::fake('public');

        Sanctum::actingAs(User::factory()->platformAdmin()->create());

        $logo = UploadedFile::fake()->image('logo.jpg');

        $response = $this->post('/api/tenants', [
            ...tenantPayload(),
            'logo' => $logo,
        ], ['Accept' => 'application/json']);

        $response->assertCreated();

        $path = $response->json('data.logo');

        expect($path)->toBeString();
        Storage::disk('public')->assertExists($path);
    });

    it('returns 422 when the name is missing', function () {
        Sanctum::actingAs(User::factory()->platformAdmin()->create());

        $this->postJson('/api/tenants', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });

    it('returns 422 when the contact email is invalid', function () {
        Sanctum::actingAs(User::factory()->platformAdmin()->create());

        $this->postJson('/api/tenants', tenantPayload([
            'contact' => ['email' => 'not-an-email'],
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['contact.email']);
    });

    it('returns 422 when the slug is already taken', function () {
        Tenant::factory()->create(['slug' => 'acme-coffee']);

        Sanctum::actingAs(User::factory()->platformAdmin()->create());

        $this->postJson('/api/tenants', tenantPayload(['slug' => 'acme-coffee']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['slug']);
    });

    it('appends a suffix when the generated slug already exists', function () {
        Tenant::factory()->create(['slug' => 'acme-coffee']);

        Sanctum::actingAs(User::factory()->platformAdmin()->create());

        $this->postJson('/api/tenants', tenantPayload())
            ->assertCreated()
            ->assertJsonPath('data.slug', 'acme-coffee-1');
    });
});

describe('show', function () {
    it('returns the tenant for a platform admin', function () {
        $tenant = Tenant::factory()->create();

        Sanctum::actingAs(User::factory()->platformAdmin()->create());

        $this->getJson('/api/tenants/'.$tenant->id)
            ->assertOk()
            ->assertJsonPath('data.id', $tenant->id)
            ->assertJsonPath('data.name', $tenant->name);
    });
});

describe('update', function () {
    it('updates the tenant', function () {
        $tenant = Tenant::factory()->create(['name' => 'Old Name']);

        Sanctum::actingAs(User::factory()->platformAdmin()->create());

        $this->putJson('/api/tenants/'.$tenant->id, [
            'name' => 'New Name',
            'status' => TenantStatus::Suspended->value,
            'address' => ['city' => 'Chittagong'],
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'New Name')
            ->assertJsonPath('data.status', TenantStatus::Suspended->value)
            ->assertJsonPath('data.address.city', 'Chittagong');

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'name' => 'New Name',
            'city' => 'Chittagong',
        ]);
    });
});

describe('destroy', function () {
    it('soft deletes the tenant', function () {
        $tenant = Tenant::factory()->create();

        Sanctum::actingAs(User::factory()->platformAdmin()->create());

        $this->deleteJson('/api/tenants/'.$tenant->id)
            ->assertNoContent();

        $this->assertSoftDeleted($tenant);

        $this->getJson('/api/tenants/'.$tenant->id)
            ->assertNotFound();
    });
});
