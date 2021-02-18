<?php

namespace Marqant\MarqantPaySubscriptions\Factories;

use Marqant\MarqantPaySubscriptions\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlanFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Plan::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name'        => $this->faker->name,
            'description' => $this->faker->text(),
            'amount'      => 9.99,
            'type'        => $this->faker->randomElement(['monthly', 'yearly']),
            'active'      => 1
        ];
    }
}
