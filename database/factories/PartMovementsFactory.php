<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PartMovements>
 */
class PartMovementsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $stockBefore = fake()->numberBetween(0, 1000);
        $qty = fake()->numberBetween(1, 100);
        $type = fake()->randomElement(['in', 'out']);

        return [
            'part_id' => \App\Models\Parts::factory(),
            'stock_before' => $stockBefore,
            'type' => $type,
            'qty' => $qty,
            'stock_after' => $type === 'in' ? $stockBefore + $qty : $stockBefore - $qty,
            'reference_type' => 'App\Models\Receivings',
            'reference_id' => fake()->uuid(),
        ];
    }
}
