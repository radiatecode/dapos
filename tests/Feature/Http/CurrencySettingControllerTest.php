<?php

use App\Enums\Permission;
use DA\Admin\Models\Currency;
use DA\Admin\Models\Tenant;
use DA\Inventory\Models\CurrencySetting;

describe('authentication and authorization', function () {
    it('returns 401 when no token is provided', function (string $method, string $uri) {
        $this->json($method, $uri, currencySettingPayload())->assertUnauthorized();
    })->with([
        ['GET', '/api/v1/currencies'],
        ['GET', '/api/v1/currency-settings'],
        ['PUT', '/api/v1/currency-settings'],
    ]);

    it('returns 403 when the user lacks settings view', function (string $uri) {
        actingAsTenantUser(permissions: [Permission::CategoriesView]);

        $this->getJson($uri)->assertForbidden();
    })->with([
        '/api/v1/currencies',
        '/api/v1/currency-settings',
    ]);

    it('returns 403 when the user lacks settings update', function () {
        actingAsTenantUser(permissions: [Permission::SettingsView]);

        $this->putJson('/api/v1/currency-settings', currencySettingPayload())->assertForbidden();
    });
});

describe('show', function () {
    it('creates the tenant currency format when none is saved', function () {
        $currency = Currency::factory()->create([
            'code' => 'BDT',
            'name' => 'Bangladeshi Taka',
            'symbol' => '৳',
            'is_active' => true,
        ]);
        $tenant = Tenant::factory()->create(['currency' => $currency->code]);
        actingAsTenantUser($tenant, [Permission::SettingsView]);

        $this->getJson('/api/v1/currency-settings')
            ->assertOk()
            ->assertJsonPath('data.currency_code', 'BDT')
            ->assertJsonPath('data.currency_symbol', '৳')
            ->assertJsonPath('data.decimal_places', 2)
            ->assertJsonPath('data.decimal_separator', 'dot')
            ->assertJsonPath('data.decimal_separator_character', '.')
            ->assertJsonPath('data.thousands_separator', 'comma')
            ->assertJsonPath('data.thousands_separator_character', ',')
            ->assertJsonPath('data.symbol_position', 'left')
            ->assertJsonPath('data.symbol_position_label', 'Left of the amount')
            ->assertJsonPath('data.show_decimals', true)
            ->assertJsonPath('data.add_space', false);

        $this->getJson('/api/v1/currency-settings')->assertOk();

        $this->assertDatabaseCount('currency_settings', 1);
        $this->assertDatabaseHas('currency_settings', [
            'tenant_id' => $tenant->id,
            'currency_code' => 'BDT',
            'currency_symbol' => '৳',
            'decimal_places' => 2,
        ]);
    });

    it('uses the first active currency when the tenant currency is inactive', function () {
        Currency::factory()->create([
            'code' => 'USD',
            'name' => 'US Dollar',
            'symbol' => '$',
            'is_active' => false,
        ]);
        Currency::factory()->create([
            'code' => 'EUR',
            'name' => 'Euro',
            'symbol' => '€',
            'is_active' => true,
        ]);
        $tenant = Tenant::factory()->create(['currency' => 'USD']);
        actingAsTenantUser($tenant, [Permission::SettingsView]);

        $this->getJson('/api/v1/currency-settings')
            ->assertOk()
            ->assertJsonPath('data.currency_code', 'EUR')
            ->assertJsonPath('data.currency_symbol', '€');
    });

    it('returns 422 when no active currency exists', function () {
        $tenant = Tenant::factory()->create(['currency' => 'BDT']);
        actingAsTenantUser($tenant, [Permission::SettingsView]);

        $this->getJson('/api/v1/currency-settings')
            ->assertUnprocessable()
            ->assertJsonPath('message', 'No active currency is available to configure.');
    });

    it('does not return another tenant currency setting', function () {
        $bdt = Currency::factory()->create([
            'code' => 'BDT',
            'name' => 'Bangladeshi Taka',
            'symbol' => '৳',
        ]);
        $usd = Currency::factory()->create([
            'code' => 'USD',
            'name' => 'US Dollar',
            'symbol' => '$',
        ]);
        $tenantA = Tenant::factory()->create(['currency' => 'BDT']);
        $tenantB = Tenant::factory()->create(['currency' => 'USD']);
        $foreign = CurrencySetting::withoutEvents(fn () => CurrencySetting::factory()->using($bdt)->create([
            'tenant_id' => $tenantA->id,
            'currency_symbol' => 'Tk',
        ]));
        actingAsTenantUser($tenantB, [Permission::SettingsView]);

        $this->getJson('/api/v1/currency-settings')
            ->assertOk()
            ->assertJsonPath('data.currency_code', $usd->code)
            ->assertJsonPath('data.currency_symbol', '$')
            ->assertJsonMissing(['id' => $foreign->id]);

        $this->assertDatabaseHas('currency_settings', [
            'id' => $foreign->id,
            'tenant_id' => $tenantA->id,
            'currency_symbol' => 'Tk',
        ]);
    });
});

describe('currencies', function () {
    it('lists active catalog currencies', function () {
        Currency::factory()->create([
            'code' => 'EUR',
            'name' => 'Euro',
            'symbol' => '€',
            'is_active' => true,
        ]);
        Currency::factory()->create([
            'code' => 'USD',
            'name' => 'US Dollar',
            'symbol' => '$',
            'is_active' => false,
        ]);
        actingAsTenantUser(permissions: [Permission::SettingsView]);

        $this->getJson('/api/v1/currencies')
            ->assertOk()
            ->assertJsonFragment([
                'code' => 'EUR',
                'name' => 'Euro',
                'symbol' => '€',
            ])
            ->assertJsonMissing(['code' => 'USD']);
    });
});

describe('update', function () {
    it('saves the currency format for the current tenant', function () {
        Currency::factory()->create([
            'code' => 'USD',
            'name' => 'US Dollar',
            'symbol' => '$',
            'is_active' => true,
        ]);
        $tenant = Tenant::factory()->create();
        actingAsTenantUser($tenant, [Permission::SettingsUpdate]);

        $this->putJson('/api/v1/currency-settings', currencySettingPayload([
            'currency_code' => 'usd',
            'currency_symbol' => 'US$',
            'decimal_places' => 0,
            'decimal_separator' => 'dot',
            'thousands_separator' => 'none',
            'symbol_position' => 'right',
            'show_decimals' => false,
            'add_space' => true,
            'tenant_id' => 999,
        ]))->assertOk()
            ->assertJsonPath('data.currency_code', 'USD')
            ->assertJsonPath('data.currency_symbol', 'US$')
            ->assertJsonPath('data.decimal_places', 0)
            ->assertJsonPath('data.decimal_separator', 'dot')
            ->assertJsonPath('data.thousands_separator', 'none')
            ->assertJsonPath('data.thousands_separator_character', '')
            ->assertJsonPath('data.symbol_position', 'right')
            ->assertJsonPath('data.symbol_position_label', 'Right of the amount')
            ->assertJsonPath('data.show_decimals', false)
            ->assertJsonPath('data.add_space', true);

        $this->assertDatabaseCount('currency_settings', 1);
        $this->assertDatabaseHas('currency_settings', [
            'tenant_id' => $tenant->id,
            'currency_code' => 'USD',
            'currency_symbol' => 'US$',
            'decimal_places' => 0,
            'decimal_separator' => 'dot',
            'thousands_separator' => 'none',
            'symbol_position' => 'right',
            'show_decimals' => false,
            'add_space' => true,
        ]);
    });

    it('returns 422 when required fields are missing', function () {
        actingAsTenantUser(permissions: [Permission::SettingsUpdate]);

        $this->putJson('/api/v1/currency-settings', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'currency_code',
                'currency_symbol',
                'decimal_places',
                'decimal_separator',
                'thousands_separator',
                'symbol_position',
                'show_decimals',
                'add_space',
            ])
            ->assertJsonPath('errors.currency_code.0', 'The currency code field is required.');
    });

    it('returns 422 when the currency code is unknown', function () {
        actingAsTenantUser(permissions: [Permission::SettingsUpdate]);

        $this->putJson('/api/v1/currency-settings', currencySettingPayload([
            'currency_code' => 'ZZZ',
        ]))->assertUnprocessable()
            ->assertJsonValidationErrors([
                'currency_code' => 'The selected currency code is invalid.',
            ]);
    });

    it('returns 422 when the currency code is inactive', function () {
        Currency::factory()->create([
            'code' => 'USD',
            'name' => 'US Dollar',
            'symbol' => '$',
            'is_active' => false,
        ]);
        actingAsTenantUser(permissions: [Permission::SettingsUpdate]);

        $this->putJson('/api/v1/currency-settings', currencySettingPayload([
            'currency_code' => 'USD',
        ]))->assertUnprocessable()
            ->assertJsonValidationErrors([
                'currency_code' => 'The selected currency code is invalid.',
            ]);
    });

    it('returns 422 when decimal places are outside 0 to 4', function () {
        Currency::factory()->create([
            'code' => 'BDT',
            'name' => 'Bangladeshi Taka',
            'symbol' => '৳',
        ]);
        actingAsTenantUser(permissions: [Permission::SettingsUpdate]);

        $this->putJson('/api/v1/currency-settings', currencySettingPayload([
            'decimal_places' => 5,
        ]))->assertUnprocessable()
            ->assertJsonValidationErrors([
                'decimal_places' => 'The selected decimal places is invalid.',
            ]);
    });

    it('returns 422 when the thousands separator matches the decimal separator', function () {
        Currency::factory()->create([
            'code' => 'BDT',
            'name' => 'Bangladeshi Taka',
            'symbol' => '৳',
        ]);
        actingAsTenantUser(permissions: [Permission::SettingsUpdate]);

        $this->putJson('/api/v1/currency-settings', currencySettingPayload([
            'decimal_separator' => 'comma',
            'thousands_separator' => 'comma',
        ]))->assertUnprocessable()
            ->assertJsonValidationErrors([
                'thousands_separator' => 'The thousands separator must be different from the decimal separator.',
            ]);
    });
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function currencySettingPayload(array $overrides = []): array
{
    return array_replace([
        'currency_code' => 'BDT',
        'currency_symbol' => '৳',
        'decimal_places' => 2,
        'decimal_separator' => 'dot',
        'thousands_separator' => 'comma',
        'symbol_position' => 'left',
        'show_decimals' => true,
        'add_space' => false,
    ], $overrides);
}
