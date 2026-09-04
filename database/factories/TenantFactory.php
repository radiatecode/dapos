<?php

namespace Database\Factories;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('###'),
            'legal_name' => $name.' Ltd',
            'status' => TenantStatus::Active,
            'timezone' => 'Asia/Dhaka',
            'currency' => 'BDT',
            'address_line_1' => fake()->streetAddress(),
            'address_line_2' => fake()->optional()->secondaryAddress(),
            'city' => fake()->city(),
            'state' => fake()->state(),
            'postal_code' => fake()->postcode(),
            'country' => fake()->countryCode(),
            'contact_name' => fake()->name(),
            'contact_email' => fake()->companyEmail(),
            'contact_phone' => fake()->phoneNumber(),
            'website' => fake()->url(),
            'billing_name' => $name.' Billing',
            'billing_email' => fake()->companyEmail(),
            'billing_phone' => fake()->phoneNumber(),
            'billing_address_line_1' => fake()->streetAddress(),
            'billing_address_line_2' => null,
            'billing_city' => fake()->city(),
            'billing_state' => fake()->state(),
            'billing_postal_code' => fake()->postcode(),
            'billing_country' => fake()->countryCode(),
            'tax_id' => fake()->numerify('TAX-######'),
        ];
    }

    public function suspended(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => TenantStatus::Suspended,
        ]);
    }
}
