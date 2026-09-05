<?php

use DA\Admin\Enums\BillingInterval;
use DA\Admin\Models\Addon;
use DA\Admin\Models\Currency;

describe('index', function () {
    it('redirects guests from the add-on list to login', function () {
        $this->get(route('admin.addons.index'))
            ->assertRedirect(route('admin.login'));
    });

    it('renders the add-on datatable and create modal for a platform admin', function () {
        actingAsPlatformAdmin();

        Currency::factory()->usd()->create();

        $this->get(route('admin.addons.index'))
            ->assertOk()
            ->assertSee('addons-table', false)
            ->assertSee('dataTables.buttons', false)
            ->assertSee('"extend":"create"', false)
            ->assertSee('addon-form-modal', false)
            ->assertSee('Create add-on')
            ->assertSee('name="name"', false)
            ->assertSee('name="code"', false)
            ->assertSee('name="billing_interval"', false)
            ->assertSee('name="price"', false)
            ->assertSee('name="currency_id"', false)
            ->assertSee('USD');
    });
});

describe('store', function () {
    it('redirects guests away from add-on creation', function () {
        $this->post(route('admin.addons.store'), addonPayload())
            ->assertRedirect(route('admin.login'));

        $this->assertDatabaseMissing('addons', ['code' => 'extra-location']);
    });

    it('creates an add-on from an ajax request', function () {
        actingAsPlatformAdmin();

        $currency = Currency::factory()->usd()->create();

        $this->from(route('admin.addons.index'))
            ->post(route('admin.addons.store'), addonPayload([
                'currency_id' => $currency->id,
            ]), [
                'X-Requested-With' => 'XMLHttpRequest',
                'Accept' => 'application/json',
            ])
            ->assertCreated()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('addons', [
            'name' => 'Extra location',
            'code' => 'extra-location',
            'billing_interval' => BillingInterval::Monthly->value,
            'price' => '9.00',
            'currency_id' => $currency->id,
            'is_active' => 1,
        ]);
    });

    it('returns a validation error when the name is missing', function () {
        actingAsPlatformAdmin();

        $this->from(route('admin.addons.index'))
            ->post(route('admin.addons.store'), addonPayload(['name' => '']))
            ->assertRedirect(route('admin.addons.index'))
            ->assertSessionHasErrors(['name']);

        $this->assertDatabaseMissing('addons', ['code' => 'extra-location']);
    });

    it('rejects a duplicate add-on code', function () {
        actingAsPlatformAdmin();

        $addon = Addon::factory()->create(['code' => 'extra-location']);

        $this->from(route('admin.addons.index'))
            ->post(route('admin.addons.store'), addonPayload([
                'code' => 'extra-location',
                'currency_id' => $addon->currency_id,
            ]))
            ->assertRedirect(route('admin.addons.index'))
            ->assertSessionHasErrors(['code']);
    });
});

describe('update', function () {
    it('redirects guests away from add-on updates', function () {
        $addon = Addon::factory()->create(['name' => 'Old']);

        $this->put(route('admin.addons.update', $addon), addonPayload([
            'name' => 'New',
            'code' => $addon->code,
            'currency_id' => $addon->currency_id,
        ]))
            ->assertRedirect(route('admin.login'));

        $this->assertDatabaseHas('addons', ['id' => $addon->id, 'name' => 'Old']);
    });

    it('updates an add-on from an ajax request', function () {
        actingAsPlatformAdmin();

        $addon = Addon::factory()->create([
            'name' => 'Extra location',
            'code' => 'extra-location',
            'price' => '9.00',
        ]);

        $this->from(route('admin.addons.index'))
            ->put(route('admin.addons.update', $addon), addonPayload([
                'name' => 'Priority support',
                'code' => 'priority-support',
                'price' => '15.00',
                'currency_id' => $addon->currency_id,
            ]), [
                'X-Requested-With' => 'XMLHttpRequest',
                'Accept' => 'application/json',
            ])
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('addons', [
            'id' => $addon->id,
            'name' => 'Priority support',
            'code' => 'priority-support',
            'price' => '15.00',
        ]);
    });

    it('returns a validation error when the updated name is missing', function () {
        actingAsPlatformAdmin();

        $addon = Addon::factory()->create(['name' => 'Keep Me']);

        $this->from(route('admin.addons.index'))
            ->put(route('admin.addons.update', $addon), addonPayload([
                'name' => '',
                'code' => $addon->code,
                'currency_id' => $addon->currency_id,
            ]))
            ->assertRedirect(route('admin.addons.index'))
            ->assertSessionHasErrors(['name']);

        $this->assertDatabaseHas('addons', ['id' => $addon->id, 'name' => 'Keep Me']);
    });
});
