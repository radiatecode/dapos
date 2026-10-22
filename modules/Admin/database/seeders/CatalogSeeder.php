<?php

namespace DA\Admin\Database\Seeders;

use DA\Admin\Enums\BillingInterval;
use DA\Admin\Models\Addon;
use DA\Admin\Models\Currency;
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->currencies() as $currency) {
            Currency::query()->updateOrCreate(
                ['code' => $currency['code']],
                $currency,
            );
        }

        $usd = Currency::query()->where('code', 'USD')->first();

        if ($usd !== null) {
            foreach ($this->addons() as $addon) {
                Addon::query()->updateOrCreate(
                    ['code' => $addon['code']],
                    [...$addon, 'currency_id' => $usd->id],
                );
            }
        }
    }

    /**
     * @return list<array{code: string, name: string, symbol: string, is_active: bool}>
     */
    private function currencies(): array
    {
        return [
            ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'is_active' => true],
            ['code' => 'BDT', 'name' => 'Bangladeshi Taka', 'symbol' => '৳', 'is_active' => true],
            ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€', 'is_active' => true],
            ['code' => 'GBP', 'name' => 'British Pound', 'symbol' => '£', 'is_active' => true],
            ['code' => 'INR', 'name' => 'Indian Rupee', 'symbol' => '₹', 'is_active' => true],
            ['code' => 'AED', 'name' => 'UAE Dirham', 'symbol' => 'د.إ', 'is_active' => true],
            ['code' => 'SGD', 'name' => 'Singapore Dollar', 'symbol' => 'S$', 'is_active' => true],
            ['code' => 'SAR', 'name' => 'Saudi Riyal', 'symbol' => '﷼', 'is_active' => true],
            ['code' => 'MYR', 'name' => 'Malaysian Ringgit', 'symbol' => 'RM', 'is_active' => true],
            ['code' => 'AUD', 'name' => 'Australian Dollar', 'symbol' => 'A$', 'is_active' => true],
        ];
    }

    /**
     * @return list<array{name: string, code: string, description: string, billing_interval: BillingInterval, price: string, is_active: bool}>
     */
    private function addons(): array
    {
        return [
            [
                'name' => 'Extra store',
                'code' => 'extra-store',
                'description' => 'Add one more store location.',
                'billing_interval' => BillingInterval::Monthly,
                'price' => '9.00',
                'is_active' => true,
            ],
            [
                'name' => 'Extra user',
                'code' => 'extra-user',
                'description' => 'Add five additional user seats.',
                'billing_interval' => BillingInterval::Monthly,
                'price' => '15.00',
                'is_active' => true,
            ],
            [
                'name' => 'Extra product',
                'code' => 'extra-product',
                'description' => 'Add five additional user seats.',
                'billing_interval' => BillingInterval::Monthly,
                'price' => '15.00',
                'is_active' => true,
            ],
        ];
    }
}
