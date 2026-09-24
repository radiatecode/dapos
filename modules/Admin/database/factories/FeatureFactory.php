<?php

namespace DA\Admin\Database\Factories;

use DA\Admin\Enums\FeatureEnforcement;
use DA\Admin\Enums\FeatureType;
use DA\Admin\Models\Feature;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Feature>
 */
class FeatureFactory extends Factory
{
    protected $model = Feature::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => Str::title($name),
            'code' => Str::slug($name, '_').'_'.fake()->unique()->numerify('###'),
            'type' => FeatureType::Limit,
            'enforcement' => FeatureEnforcement::Consumption,
            'description' => fake()->sentence(),
        ];
    }

    public function boolean(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => FeatureType::Boolean,
            'enforcement' => null,
        ]);
    }

    public function limit(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => FeatureType::Limit,
            'enforcement' => FeatureEnforcement::Consumption,
        ]);
    }

    public function resource(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => FeatureType::Limit,
            'enforcement' => FeatureEnforcement::Resource,
        ]);
    }

    public function consumption(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => FeatureType::Limit,
            'enforcement' => FeatureEnforcement::Consumption,
        ]);
    }
}
