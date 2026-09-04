<?php

use DA\Admin\Enums\TenantStatus;

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

    it('returns a validation error when the name is missing', function () {
        actingAsPlatformAdmin();

        $this->from(route('admin.tenants.create'))
            ->post(route('admin.tenants.store'), tenantPayload(['name' => '']))
            ->assertRedirect(route('admin.tenants.create'))
            ->assertSessionHasErrors(['name']);

        $this->assertDatabaseMissing('tenants', ['contact_person_email' => 'jane@acme.test']);
    });
});
