<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Parts>
 */
class PartsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'part_number' => 'PN-'.fake()->unique()->numerify('####'),
            'part_name' => fake()->words(3, true),
            'customer_code' => fake()->optional()->bothify('CUST-###'),
            'supplier_code' => fake()->optional()->bothify('SUPP-###'),
            'model' => fake()->optional()->bothify('X###'),
            'variant' => fake()->optional()->randomElement(['V1', 'V2', 'V3', 'Standard']),
            'standard_packing' => fake()->numberBetween(10, 100),
            'stock' => fake()->numberBetween(0, 500),
            'address' => fake()->optional()->bothify('?##-?##-?##'),
            'is_active' => fake()->boolean(90),
        ];
    }
}
