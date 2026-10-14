<?php

use App\Models\User;
use DA\Admin\Enums\TenantStatus;
use DA\Admin\Models\Plan;
use DA\Admin\Models\Subscription;
use DA\Admin\Models\Tenant;
use Illuminate\Support\Facades\Hash;

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function superAdminPayload(array $overrides = []): array
{
    return array_replace([
        'name' => 'Amina Rahman',
        'email' => 'owner@acme.test',
        'password' => 'password',
        'password_confirmation' => 'password',
    ], $overrides);
}

describe('store', function () {
    it('redirects guests away from super admin creation', function () {
        $tenant = Tenant::factory()->inactive()->create();

        $this->post(route('admin.tenants.super-admin.store', $tenant), superAdminPayload())
            ->assertRedirect(route('admin.login'));

        $this->assertDatabaseMissing('users', ['email' => 'owner@acme.test']);
    });

    it('creates an inactive super admin for the selected tenant', function () {
        actingAsPlatformAdmin();

        $tenant = Tenant::factory()->inactive()->create();
        $other = Tenant::factory()->inactive()->create();

        $this->from(route('admin.tenants.show', $tenant))
            ->post(route('admin.tenants.super-admin.store', $tenant), superAdminPayload([
                'email' => 'Owner@Acme.test',
                'tenant_id' => $other->id,
                'is_active' => true,
                'is_super_admin' => false,
                'role' => 'platform_admin',
            ]))
            ->assertRedirect(route('admin.tenants.show', $tenant));

        $user = User::query()->where('email', 'owner@acme.test')->first();

        expect($user)->not->toBeNull()
            ->and($user->tenant_id)->toBe($tenant->id)
            ->and($user->role->value)->toBe('tenant_user')
            ->and($user->is_super_admin)->toBeTrue()
            ->and($user->is_active)->toBeFalse()
            ->and(Hash::check('password', $user->password))->toBeTrue();

        $this->assertDatabaseMissing('users', [
            'tenant_id' => $other->id,
            'email' => 'owner@acme.test',
        ]);
    });

    it('returns json when the create request expects json', function () {
        actingAsPlatformAdmin();

        $tenant = Tenant::factory()->inactive()->create();

        $this->postJson(route('admin.tenants.super-admin.store', $tenant), superAdminPayload())
            ->assertCreated()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('users', [
            'tenant_id' => $tenant->id,
            'email' => 'owner@acme.test',
            'is_active' => 0,
            'is_super_admin' => 1,
        ]);
    });

    it('returns validation errors when the account fields are missing', function () {
        actingAsPlatformAdmin();

        $tenant = Tenant::factory()->inactive()->create();

        $this->from(route('admin.tenants.show', $tenant))
            ->post(route('admin.tenants.super-admin.store', $tenant), [])
            ->assertRedirect(route('admin.tenants.show', $tenant))
            ->assertSessionHasErrors([
                'name' => 'The name field is required.',
                'email' => 'The email field is required.',
                'password' => 'The password field is required.',
            ]);

        $this->assertDatabaseCount('users', 0);
    });

    it('returns a validation error when the email is invalid', function () {
        actingAsPlatformAdmin();

        $tenant = Tenant::factory()->inactive()->create();

        $this->from(route('admin.tenants.show', $tenant))
            ->post(route('admin.tenants.super-admin.store', $tenant), superAdminPayload([
                'email' => 'not-an-email',
            ]))
            ->assertRedirect(route('admin.tenants.show', $tenant))
            ->assertSessionHasErrors([
                'email' => 'The email must be a valid email address.',
            ]);
    });

    it('returns a validation error when the email is already taken', function () {
        actingAsPlatformAdmin();

        $tenant = Tenant::factory()->inactive()->create();
        User::factory()->forTenant($tenant)->create(['email' => 'owner@acme.test']);

        $this->from(route('admin.tenants.show', $tenant))
            ->post(route('admin.tenants.super-admin.store', $tenant), superAdminPayload())
            ->assertRedirect(route('admin.tenants.show', $tenant))
            ->assertSessionHasErrors([
                'email' => 'The email has already been taken.',
            ]);

        $this->assertDatabaseMissing('users', [
            'email' => 'owner@acme.test',
            'is_super_admin' => 1,
        ]);
    });

    it('returns a validation error when the password confirmation does not match', function () {
        actingAsPlatformAdmin();

        $tenant = Tenant::factory()->inactive()->create();

        $this->from(route('admin.tenants.show', $tenant))
            ->post(route('admin.tenants.super-admin.store', $tenant), superAdminPayload([
                'password_confirmation' => 'different-password',
            ]))
            ->assertRedirect(route('admin.tenants.show', $tenant))
            ->assertSessionHasErrors([
                'password' => 'The password confirmation does not match.',
            ]);
    });

    it('returns a validation error when the password is too short', function () {
        actingAsPlatformAdmin();

        $tenant = Tenant::factory()->inactive()->create();

        $this->from(route('admin.tenants.show', $tenant))
            ->post(route('admin.tenants.super-admin.store', $tenant), superAdminPayload([
                'password' => 'short',
                'password_confirmation' => 'short',
            ]))
            ->assertRedirect(route('admin.tenants.show', $tenant))
            ->assertSessionHasErrors([
                'password' => 'The password field must be at least 8 characters.',
            ]);
    });

    it('rejects a second super admin for the same tenant', function () {
        actingAsPlatformAdmin();

        $tenant = Tenant::factory()->inactive()->create();
        User::factory()->forTenant($tenant)->create([
            'email' => 'first@acme.test',
            'is_super_admin' => true,
            'is_active' => false,
        ]);

        $this->from(route('admin.tenants.show', $tenant))
            ->post(route('admin.tenants.super-admin.store', $tenant), superAdminPayload())
            ->assertRedirect(route('admin.tenants.show', $tenant))
            ->assertSessionHasErrors([
                'email' => 'This tenant already has a super admin account.',
            ]);

        $this->assertDatabaseMissing('users', ['email' => 'owner@acme.test']);
    });

    it('creates an active super admin when the tenant already has a subscription', function () {
        actingAsPlatformAdmin();

        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->create();
        Subscription::factory()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
        ]);

        $this->post(route('admin.tenants.super-admin.store', $tenant), superAdminPayload())
            ->assertRedirect(route('admin.tenants.show', $tenant));

        $this->assertDatabaseHas('users', [
            'tenant_id' => $tenant->id,
            'email' => 'owner@acme.test',
            'is_super_admin' => 1,
            'is_active' => 1,
        ]);
        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'status' => TenantStatus::Active->value,
        ]);
    });

    it('activates the tenant and super admin after a plan is subscribed', function () {
        actingAsPlatformAdmin();

        $this->post(route('admin.tenants.store'), tenantPayload())
            ->assertRedirect(route('admin.tenants.index'));

        $tenant = Tenant::query()->where('name', 'Acme Coffee')->firstOrFail();

        expect($tenant->status)->toBe(TenantStatus::Inactive);

        $this->post(route('admin.tenants.super-admin.store', $tenant), superAdminPayload())
            ->assertRedirect(route('admin.tenants.show', $tenant));

        $this->assertDatabaseHas('users', [
            'tenant_id' => $tenant->id,
            'email' => 'owner@acme.test',
            'is_active' => 0,
        ]);

        $plan = Plan::factory()->create(['trial_days' => 14]);

        $this->post(route('admin.subscriptions.store'), subscriptionPayload([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'start_trial' => '1',
        ]))->assertRedirect();

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'status' => TenantStatus::Active->value,
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'owner@acme.test',
            'is_active' => 1,
            'is_super_admin' => 1,
        ]);
    });
});
