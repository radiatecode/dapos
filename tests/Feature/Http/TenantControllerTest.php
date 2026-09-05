<?php

use DA\Admin\Enums\TenantStatus;
use DA\Admin\Models\Tenant;

describe('index', function () {
    it('redirects guests from the tenant list to login', function () {
        $this->get(route('admin.tenants.index'))
            ->assertRedirect(route('admin.login'));
    });

    it('renders the tenant datatable toolbar buttons for a platform admin', function () {
        actingAsPlatformAdmin();

        $this->get(route('admin.tenants.index'))
            ->assertOk()
            ->assertSee('tenants-table', false)
            ->assertSee('dataTables.buttons', false)
            ->assertSee('"extend":"create"', false)
            ->assertSee('"extend":"reset"', false)
            ->assertSee('"extend":"reload"', false)
            ->assertSee('"topStart":["buttons"]', false);
    });
});

describe('create', function () {
    it('redirects guests from the tenant create page to login', function () {
        $this->get(route('admin.tenants.create'))
            ->assertRedirect(route('admin.login'));
    });

    it('renders the tenant create form for a platform admin', function () {
        actingAsPlatformAdmin();

        $this->get(route('admin.tenants.create'))
            ->assertOk()
            ->assertSee('Create tenant')
            ->assertSee('Basic Info')
            ->assertSee('Contact')
            ->assertSee('Billing Info')
            ->assertSee('novalidate', false)
            ->assertSee('data-parsley-excluded="input[type=hidden], [disabled]"', false)
            ->assertSee('vendor/admin/js/form.tabs.js', false)
            ->assertSee('name="name"', false)
            ->assertSee('name="logo"', false)
            ->assertSee('name="timezone"', false)
            ->assertSee('name="currency"', false)
            ->assertSee('name="address[line_1]"', false)
            ->assertSee('name="contact[name]"', false)
            ->assertSee('name="contact[email]"', false)
            ->assertSee('name="billing[name]"', false)
            ->assertSee('name="billing[address][line_1]"', false)
            ->assertDontSee('name="slug"', false)
            ->assertDontSee('name="status"', false)
            ->assertSee('Asia/Dhaka')
            ->assertSee('BDT');
    });
});

describe('store', function () {
    it('redirects guests away from tenant creation', function () {
        $this->post(route('admin.tenants.store'), tenantPayload())
            ->assertRedirect(route('admin.login'));

        $this->assertDatabaseMissing('tenants', ['name' => 'Acme Coffee']);
    });

    it('creates a tenant and redirects to the tenant list', function () {
        actingAsPlatformAdmin();

        $this->from(route('admin.tenants.create'))
            ->post(route('admin.tenants.store'), tenantPayload())
            ->assertRedirect(route('admin.tenants.index'));

        $this->assertDatabaseHas('tenants', [
            'name' => 'Acme Coffee',
            'slug' => 'acme-coffee',
            'timezone' => 'Asia/Dhaka',
            'currency' => 'BDT',
            'city' => 'Dhaka',
            'contact_person_email' => 'jane@acme.test',
            'billing_name' => 'Acme Billing',
            'status' => TenantStatus::Active->value,
        ]);
    });

    it('returns json validation errors for an ajax create request', function () {
        actingAsPlatformAdmin();

        $this->from(route('admin.tenants.create'))
            ->post(route('admin.tenants.store'), tenantPayload([
                'contact' => ['email' => 'not-an-email'],
            ]), [
                'X-Requested-With' => 'XMLHttpRequest',
                'Accept' => 'application/json',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['contact.email']);

        $this->assertDatabaseMissing('tenants', ['name' => 'Acme Coffee']);
    });

    it('returns a validation error when the name is missing', function () {
        actingAsPlatformAdmin();

        $this->from(route('admin.tenants.create'))
            ->post(route('admin.tenants.store'), tenantPayload(['name' => '']))
            ->assertRedirect(route('admin.tenants.create'))
            ->assertSessionHasErrors(['name']);

        $this->assertDatabaseMissing('tenants', ['contact_person_email' => 'jane@acme.test']);
    });
});

describe('edit', function () {
    it('redirects guests from the tenant edit page to login', function () {
        $tenant = Tenant::factory()->create();

        $this->get(route('admin.tenants.edit', $tenant))
            ->assertRedirect(route('admin.login'));
    });

    it('renders the tenant edit form with the current values', function () {
        actingAsPlatformAdmin();

        $tenant = Tenant::factory()->create([
            'name' => 'Blue Roastery',
            'contact_person_email' => 'blue@roastery.test',
        ]);

        $this->get(route('admin.tenants.edit', $tenant))
            ->assertOk()
            ->assertSee('Edit tenant')
            ->assertSee('Basic Info')
            ->assertSee('Contact')
            ->assertSee('Billing Info')
            ->assertSee('Blue Roastery')
            ->assertSee('blue@roastery.test')
            ->assertSee('name="name"', false)
            ->assertSee('name="contact[email]"', false)
            ->assertDontSee('name="slug"', false)
            ->assertDontSee('name="status"', false);
    });
});

describe('update', function () {
    it('redirects guests away from tenant updates', function () {
        $tenant = Tenant::factory()->create(['name' => 'Old Name']);

        $this->put(route('admin.tenants.update', $tenant), tenantPayload(['name' => 'New Name']))
            ->assertRedirect(route('admin.login'));

        $this->assertDatabaseHas('tenants', ['id' => $tenant->id, 'name' => 'Old Name']);
    });

    it('updates the tenant and redirects to the details page', function () {
        actingAsPlatformAdmin();

        $tenant = Tenant::factory()->create([
            'name' => 'Old Name',
            'slug' => 'old-name',
        ]);

        $this->from(route('admin.tenants.edit', $tenant))
            ->put(route('admin.tenants.update', $tenant), tenantPayload([
                'name' => 'New Roastery',
                'contact' => ['email' => 'ops@new.test'],
            ]))
            ->assertRedirect(route('admin.tenants.show', $tenant));

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'name' => 'New Roastery',
            'slug' => 'old-name',
            'contact_person_email' => 'ops@new.test',
        ]);
    });

    it('returns a validation error when the updated name is missing', function () {
        actingAsPlatformAdmin();

        $tenant = Tenant::factory()->create(['name' => 'Keep Me']);

        $this->from(route('admin.tenants.edit', $tenant))
            ->put(route('admin.tenants.update', $tenant), tenantPayload(['name' => '']))
            ->assertRedirect(route('admin.tenants.edit', $tenant))
            ->assertSessionHasErrors(['name']);

        $this->assertDatabaseHas('tenants', ['id' => $tenant->id, 'name' => 'Keep Me']);
    });
});

describe('show', function () {
    it('redirects guests from the tenant details page to login', function () {
        $tenant = Tenant::factory()->create();

        $this->get(route('admin.tenants.show', $tenant))
            ->assertRedirect(route('admin.login'));
    });

    it('renders tenant details in sections for a platform admin', function () {
        actingAsPlatformAdmin();

        $tenant = Tenant::factory()->create([
            'name' => 'Harbor Cafe',
            'contact_person_name' => 'Mina Ali',
            'billing_name' => 'Harbor Billing',
        ]);

        $this->get(route('admin.tenants.show', $tenant))
            ->assertOk()
            ->assertSee('Harbor Cafe')
            ->assertSee('Basic Info')
            ->assertSee('Contact')
            ->assertSee('Billing Info')
            ->assertSee('Mina Ali')
            ->assertSee('Harbor Billing')
            ->assertSee('Suspend')
            ->assertDontSee(route('admin.tenants.activate', $tenant), false)
            ->assertSee(route('admin.tenants.edit', $tenant), false)
            ->assertSee(route('admin.tenants.suspend', $tenant), false)
            ->assertSee(route('admin.tenants.destroy', $tenant), false);
    });

    it('shows the activate action only when the tenant is suspended', function () {
        actingAsPlatformAdmin();

        $tenant = Tenant::factory()->suspended()->create(['name' => 'Paused Shop']);

        $this->get(route('admin.tenants.show', $tenant))
            ->assertOk()
            ->assertSee('Activate')
            ->assertSee(route('admin.tenants.activate', $tenant), false)
            ->assertDontSee(route('admin.tenants.suspend', $tenant), false);
    });

    it('escapes tenant names on the details page', function () {
        actingAsPlatformAdmin();

        $tenant = Tenant::factory()->create([
            'name' => '<script>alert("xss")</script>',
        ]);

        $this->get(route('admin.tenants.show', $tenant))
            ->assertOk()
            ->assertSee('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert("xss")</script>', false);
    });
});

describe('status', function () {
    it('suspends an active tenant', function () {
        actingAsPlatformAdmin();

        $tenant = Tenant::factory()->create();

        $this->from(route('admin.tenants.show', $tenant))
            ->post(route('admin.tenants.suspend', $tenant))
            ->assertRedirect(route('admin.tenants.show', $tenant));

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'status' => TenantStatus::Suspended->value,
        ]);
    });

    it('activates a suspended tenant', function () {
        actingAsPlatformAdmin();

        $tenant = Tenant::factory()->suspended()->create();

        $this->from(route('admin.tenants.show', $tenant))
            ->post(route('admin.tenants.activate', $tenant))
            ->assertRedirect(route('admin.tenants.show', $tenant));

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'status' => TenantStatus::Active->value,
        ]);
    });
});

describe('destroy', function () {
    it('deletes a tenant from the details page and redirects to the list', function () {
        actingAsPlatformAdmin();

        $tenant = Tenant::factory()->create(['name' => 'Gone Shop']);

        $this->from(route('admin.tenants.show', $tenant))
            ->delete(route('admin.tenants.destroy', $tenant))
            ->assertRedirect(route('admin.tenants.index'));

        $this->assertSoftDeleted('tenants', ['id' => $tenant->id]);
    });
});
