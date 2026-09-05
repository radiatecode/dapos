<?php

use DA\Admin\Enums\FeatureType;
use DA\Admin\Models\Feature;

describe('index', function () {
    it('redirects guests from the feature list to login', function () {
        $this->get(route('admin.features.index'))
            ->assertRedirect(route('admin.login'));
    });

    it('renders the feature datatable and create modal for a platform admin', function () {
        actingAsPlatformAdmin();

        $this->get(route('admin.features.index'))
            ->assertOk()
            ->assertSee('features-table', false)
            ->assertSee('dataTables.buttons', false)
            ->assertSee('"extend":"create"', false)
            ->assertSee('feature-form-modal', false)
            ->assertSee('Create feature')
            ->assertSee('name="name"', false)
            ->assertSee('name="code"', false)
            ->assertSee('name="type"', false)
            ->assertSee(FeatureType::Boolean->value)
            ->assertSee(FeatureType::Limit->value);
    });
});

describe('store', function () {
    it('redirects guests away from feature creation', function () {
        $this->post(route('admin.features.store'), featurePayload())
            ->assertRedirect(route('admin.login'));

        $this->assertDatabaseMissing('features', ['code' => 'users']);
    });

    it('creates a limit feature from an ajax request', function () {
        actingAsPlatformAdmin();

        $this->from(route('admin.features.index'))
            ->post(route('admin.features.store'), featurePayload(), [
                'X-Requested-With' => 'XMLHttpRequest',
                'Accept' => 'application/json',
            ])
            ->assertCreated()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('features', [
            'name' => 'Users',
            'code' => 'users',
            'type' => FeatureType::Limit->value,
        ]);
    });

    it('creates a boolean feature', function () {
        actingAsPlatformAdmin();

        $this->post(route('admin.features.store'), featurePayload([
            'name' => 'Advanced reports',
            'code' => 'advanced_reports',
            'type' => FeatureType::Boolean->value,
        ]))
            ->assertRedirect(route('admin.features.index'));

        $this->assertDatabaseHas('features', [
            'name' => 'Advanced reports',
            'code' => 'advanced_reports',
            'type' => FeatureType::Boolean->value,
        ]);
    });

    it('returns a validation error when the name is missing', function () {
        actingAsPlatformAdmin();

        $this->from(route('admin.features.index'))
            ->post(route('admin.features.store'), featurePayload(['name' => '']))
            ->assertRedirect(route('admin.features.index'))
            ->assertSessionHasErrors(['name']);

        $this->assertDatabaseMissing('features', ['code' => 'users']);
    });

    it('rejects a duplicate feature code', function () {
        actingAsPlatformAdmin();

        Feature::factory()->create(['code' => 'users']);

        $this->from(route('admin.features.index'))
            ->post(route('admin.features.store'), featurePayload(['code' => 'users']))
            ->assertRedirect(route('admin.features.index'))
            ->assertSessionHasErrors(['code']);
    });
});

describe('update', function () {
    it('redirects guests away from feature updates', function () {
        $feature = Feature::factory()->create(['name' => 'Old']);

        $this->put(route('admin.features.update', $feature), featurePayload([
            'name' => 'New',
            'code' => $feature->code,
            'type' => $feature->type->value,
        ]))
            ->assertRedirect(route('admin.login'));

        $this->assertDatabaseHas('features', ['id' => $feature->id, 'name' => 'Old']);
    });

    it('updates a feature from an ajax request', function () {
        actingAsPlatformAdmin();

        $feature = Feature::factory()->limit()->create([
            'name' => 'Products',
            'code' => 'products',
        ]);

        $this->from(route('admin.features.index'))
            ->put(route('admin.features.update', $feature), featurePayload([
                'name' => 'Catalog products',
                'code' => 'products',
                'type' => FeatureType::Limit->value,
            ]), [
                'X-Requested-With' => 'XMLHttpRequest',
                'Accept' => 'application/json',
            ])
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('features', [
            'id' => $feature->id,
            'name' => 'Catalog products',
            'code' => 'products',
        ]);
    });

    it('returns a validation error when the updated name is missing', function () {
        actingAsPlatformAdmin();

        $feature = Feature::factory()->create(['name' => 'Keep Me']);

        $this->from(route('admin.features.index'))
            ->put(route('admin.features.update', $feature), featurePayload([
                'name' => '',
                'code' => $feature->code,
                'type' => $feature->type->value,
            ]))
            ->assertRedirect(route('admin.features.index'))
            ->assertSessionHasErrors(['name']);

        $this->assertDatabaseHas('features', ['id' => $feature->id, 'name' => 'Keep Me']);
    });
});
