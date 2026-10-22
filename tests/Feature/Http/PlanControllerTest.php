<?php

use DA\Admin\Enums\BillingInterval;
use DA\Admin\Enums\Features;
use DA\Admin\Models\Currency;
use DA\Admin\Models\Plan;
use DA\Admin\Models\PlanFeature;

describe('index', function () {
    it('redirects guests from the plan list to login', function () {
        $this->get(route('admin.plans.index'))
            ->assertRedirect(route('admin.login'));
    });

    it('renders the plan datatable toolbar buttons for a platform admin', function () {
        actingAsPlatformAdmin();

        $this->get(route('admin.plans.index'))
            ->assertOk()
            ->assertSee('plans-table', false)
            ->assertSee('dataTables.buttons', false)
            ->assertSee('"extend":"create"', false)
            ->assertSee('"extend":"reset"', false)
            ->assertSee('"extend":"reload"', false)
            ->assertSee('"topStart":["buttons"]', false);
    });
});

describe('create', function () {
    it('redirects guests from the plan create page to login', function () {
        $this->get(route('admin.plans.create'))
            ->assertRedirect(route('admin.login'));
    });

    it('renders the plan create form with feature assignment controls', function () {
        actingAsPlatformAdmin();

        $currency = Currency::factory()->usd()->create();

        $this->get(route('admin.plans.create'))
            ->assertOk()
            ->assertSee('Create plan')
            ->assertSee('novalidate', false)
            ->assertSee('name="name"', false)
            ->assertSee('name="code"', false)
            ->assertSee('name="billing_interval"', false)
            ->assertSee('name="price"', false)
            ->assertSee('name="currency_id"', false)
            ->assertSee('name="trial_days"', false)
            ->assertSee('USD')
            ->assertSee('Products')
            ->assertSee('POS')
            ->assertSee('Sales Report')
            ->assertSee('Unlimited')
            ->assertSee((string) $currency->id, false);
    });
});

describe('store', function () {
    it('redirects guests away from plan creation', function () {
        $this->post(route('admin.plans.store'), planPayload())
            ->assertRedirect(route('admin.login'));

        $this->assertDatabaseMissing('plans', ['name' => 'Starter']);
    });

    it('creates a plan with boolean, numeric, and unlimited features', function () {
        actingAsPlatformAdmin();

        $currency = Currency::factory()->usd()->create();

        $this->from(route('admin.plans.create'))
            ->post(route('admin.plans.store'), planPayload([
                'currency_id' => $currency->id,
                'features' => [
                    Features::STORES->value => [
                        'assigned' => '1',
                        'feature' => Features::STORES->value,
                        'value' => '5',
                        'is_unlimited' => '0',
                    ],
                    Features::PRODUCTS->value => [
                        'assigned' => '1',
                        'feature' => Features::PRODUCTS->value,
                        'value' => '',
                        'is_unlimited' => '1',
                    ],
                    Features::POS->value => [
                        'assigned' => '1',
                        'feature' => Features::POS->value,
                        'value' => '0',
                        'is_unlimited' => '0',
                    ],
                ],
            ]))
            ->assertRedirect(route('admin.plans.index'));

        $this->assertDatabaseHas('plans', [
            'name' => 'Starter',
            'code' => 'starter',
            'billing_interval' => BillingInterval::Monthly->value,
            'price' => '19.00',
            'currency_id' => $currency->id,
            'trial_days' => 14,
            'is_active' => 1,
        ]);

        $plan = Plan::query()->where('code', 'starter')->first();

        $this->assertDatabaseHas('plan_features', [
            'plan_id' => $plan->id,
            'feature' => Features::STORES->value,
            'value' => '5',
            'is_unlimited' => 0,
        ]);
        $this->assertDatabaseHas('plan_features', [
            'plan_id' => $plan->id,
            'feature' => Features::PRODUCTS->value,
            'value' => null,
            'is_unlimited' => 1,
        ]);
        $this->assertDatabaseHas('plan_features', [
            'plan_id' => $plan->id,
            'feature' => Features::POS->value,
            'value' => '0',
            'is_unlimited' => 0,
        ]);
    });

    it('returns a validation error when the name is missing', function () {
        actingAsPlatformAdmin();

        $this->from(route('admin.plans.create'))
            ->post(route('admin.plans.store'), planPayload(['name' => '']))
            ->assertRedirect(route('admin.plans.create'))
            ->assertSessionHasErrors(['name']);

        $this->assertDatabaseMissing('plans', ['code' => 'starter']);
    });

    it('rejects a boolean feature marked unlimited', function () {
        actingAsPlatformAdmin();

        $this->from(route('admin.plans.create'))
            ->post(route('admin.plans.store'), planPayload([
                'features' => [
                    Features::POS->value => [
                        'assigned' => '1',
                        'feature' => Features::POS->value,
                        'value' => '1',
                        'is_unlimited' => '1',
                    ],
                ],
            ]))
            ->assertRedirect(route('admin.plans.create'))
            ->assertSessionHasErrors(['features.'.Features::POS->value.'.is_unlimited']);

        $this->assertDatabaseMissing('plans', ['code' => 'starter']);
    });

    it('rejects a limit feature without a value when it is not unlimited', function () {
        actingAsPlatformAdmin();

        $this->from(route('admin.plans.create'))
            ->post(route('admin.plans.store'), planPayload([
                'features' => [
                    Features::PRODUCTS->value => [
                        'assigned' => '1',
                        'feature' => Features::PRODUCTS->value,
                        'value' => '',
                        'is_unlimited' => '0',
                    ],
                ],
            ]))
            ->assertRedirect(route('admin.plans.create'))
            ->assertSessionHasErrors(['features.'.Features::PRODUCTS->value.'.value']);

        $this->assertDatabaseMissing('plans', ['code' => 'starter']);
    });

    it('returns json validation errors for an ajax create request', function () {
        actingAsPlatformAdmin();

        $this->from(route('admin.plans.create'))
            ->post(route('admin.plans.store'), planPayload([
                'code' => '',
            ]), [
                'X-Requested-With' => 'XMLHttpRequest',
                'Accept' => 'application/json',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['code']);

        $this->assertDatabaseMissing('plans', ['name' => 'Starter']);
    });
});

describe('edit', function () {
    it('redirects guests from the plan edit page to login', function () {
        $plan = Plan::factory()->create();

        $this->get(route('admin.plans.edit', $plan))
            ->assertRedirect(route('admin.login'));
    });

    it('renders the plan edit form with the current values', function () {
        actingAsPlatformAdmin();

        $plan = Plan::factory()->create([
            'name' => 'Growth',
            'code' => 'growth',
        ]);

        $this->get(route('admin.plans.edit', $plan))
            ->assertOk()
            ->assertSee('Edit plan')
            ->assertSee('Growth')
            ->assertSee('growth')
            ->assertSee('name="name"', false)
            ->assertSee('name="code"', false);
    });
});

describe('update', function () {
    it('redirects guests away from plan updates', function () {
        $plan = Plan::factory()->create(['name' => 'Old Plan']);

        $this->put(route('admin.plans.update', $plan), planPayload([
            'currency_id' => $plan->currency_id,
            'name' => 'New Plan',
            'code' => $plan->code,
        ]))
            ->assertRedirect(route('admin.login'));

        $this->assertDatabaseHas('plans', ['id' => $plan->id, 'name' => 'Old Plan']);
    });

    it('updates a plan and replaces its feature assignments', function () {
        actingAsPlatformAdmin();

        $plan = Plan::factory()->create(['name' => 'Old Plan', 'code' => 'old-plan']);

        PlanFeature::factory()->create([
            'plan_id' => $plan->id,
            'feature' => Features::USERS->value,
            'value' => '3',
        ]);
        PlanFeature::factory()->disabled()->create([
            'plan_id' => $plan->id,
            'feature' => Features::POS->value,
        ]);

        $this->from(route('admin.plans.edit', $plan))
            ->put(route('admin.plans.update', $plan), planPayload([
                'currency_id' => $plan->currency_id,
                'name' => 'Pro',
                'code' => 'pro',
                'price' => '49.00',
                'features' => [
                    Features::USERS->value => [
                        'assigned' => '1',
                        'feature' => Features::USERS->value,
                        'value' => '',
                        'is_unlimited' => '1',
                    ],
                    Features::POS->value => [
                        'assigned' => '0',
                        'feature' => Features::POS->value,
                        'value' => '1',
                        'is_unlimited' => '0',
                    ],
                ],
            ]))
            ->assertRedirect(route('admin.plans.show', $plan));

        $this->assertDatabaseHas('plans', [
            'id' => $plan->id,
            'name' => 'Pro',
            'code' => 'pro',
            'price' => '49.00',
        ]);
        $this->assertDatabaseHas('plan_features', [
            'plan_id' => $plan->id,
            'feature' => Features::USERS->value,
            'value' => null,
            'is_unlimited' => 1,
        ]);
        $this->assertDatabaseMissing('plan_features', [
            'plan_id' => $plan->id,
            'feature' => Features::POS->value,
        ]);
    });

    it('returns a validation error when the updated name is missing', function () {
        actingAsPlatformAdmin();

        $plan = Plan::factory()->create(['name' => 'Keep Me']);

        $this->from(route('admin.plans.edit', $plan))
            ->put(route('admin.plans.update', $plan), planPayload([
                'currency_id' => $plan->currency_id,
                'name' => '',
                'code' => $plan->code,
            ]))
            ->assertRedirect(route('admin.plans.edit', $plan))
            ->assertSessionHasErrors(['name']);

        $this->assertDatabaseHas('plans', ['id' => $plan->id, 'name' => 'Keep Me']);
    });
});

describe('show', function () {
    it('redirects guests from the plan details page to login', function () {
        $plan = Plan::factory()->create();

        $this->get(route('admin.plans.show', $plan))
            ->assertRedirect(route('admin.login'));
    });

    it('renders plan details and assigned feature values', function () {
        actingAsPlatformAdmin();

        $plan = Plan::factory()->create([
            'name' => 'Starter',
            'code' => 'starter',
            'price' => '19.00',
        ]);
        PlanFeature::factory()->create([
            'plan_id' => $plan->id,
            'feature' => Features::STORES->value,
            'value' => '1',
        ]);

        $this->get(route('admin.plans.show', $plan))
            ->assertOk()
            ->assertSee('Starter')
            ->assertSee('starter')
            ->assertSee('Stores')
            ->assertSee('Deactivate')
            ->assertSee(route('admin.plans.edit', $plan), false)
            ->assertSee(route('admin.plans.deactivate', $plan), false)
            ->assertDontSee(route('admin.plans.activate', $plan), false);
    });

    it('shows the activate action only when the plan is inactive', function () {
        actingAsPlatformAdmin();

        $plan = Plan::factory()->inactive()->create(['name' => 'Paused Plan']);

        $this->get(route('admin.plans.show', $plan))
            ->assertOk()
            ->assertSee('Activate')
            ->assertSee(route('admin.plans.activate', $plan), false)
            ->assertDontSee(route('admin.plans.deactivate', $plan), false);
    });

    it('escapes plan names on the details page', function () {
        actingAsPlatformAdmin();

        $plan = Plan::factory()->create([
            'name' => '<script>alert("xss")</script>',
        ]);

        $this->get(route('admin.plans.show', $plan))
            ->assertOk()
            ->assertSee('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert("xss")</script>', false);
    });
});

describe('status', function () {
    it('deactivates an active plan', function () {
        actingAsPlatformAdmin();

        $plan = Plan::factory()->create();

        $this->from(route('admin.plans.show', $plan))
            ->patch(route('admin.plans.deactivate', $plan))
            ->assertRedirect(route('admin.plans.show', $plan));

        $this->assertDatabaseHas('plans', [
            'id' => $plan->id,
            'is_active' => 0,
        ]);
    });

    it('activates an inactive plan', function () {
        actingAsPlatformAdmin();

        $plan = Plan::factory()->inactive()->create();

        $this->from(route('admin.plans.show', $plan))
            ->patch(route('admin.plans.activate', $plan))
            ->assertRedirect(route('admin.plans.show', $plan));

        $this->assertDatabaseHas('plans', [
            'id' => $plan->id,
            'is_active' => 1,
        ]);
    });
});
