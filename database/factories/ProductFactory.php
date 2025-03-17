<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->word,
            'slug' => $this->faker->slug,
            'price' => $this->faker->randomFloat(2, 1, 100),
            'stock' => $this->faker->numberBetween(1, 100),
            'status' => $this->faker->randomElement(['available', 'unavailable']),
            'category_id' => $this->faker->numberBetween(1, 10),
            'sub_category_id' => $this->faker->numberBetween(1, 10),
            'critical_stock_threshold' => $this->faker->numberBetween(5, 10),            
        ];
    }
}
