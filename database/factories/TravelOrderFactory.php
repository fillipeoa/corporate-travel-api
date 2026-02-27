<?php

namespace Database\Factories;

use App\Enums\TravelOrderStatus;
use App\Models\TravelOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TravelOrder>
 */
class TravelOrderFactory extends Factory
{
    protected $model = TravelOrder::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $departureDate = fake()->dateTimeBetween('+1 week', '+3 months');

        return [
            'user_id' => User::factory(),
            'destination' => fake()->city(),
            'departure_date' => $departureDate,
            'return_date' => fake()->dateTimeBetween($departureDate, (clone $departureDate)->modify('+2 weeks')),
            'status' => TravelOrderStatus::Requested,
        ];
    }

    /**
     * Set the travel order status to approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TravelOrderStatus::Approved,
        ]);
    }

    /**
     * Set the travel order status to cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TravelOrderStatus::Cancelled,
        ]);
    }
}
