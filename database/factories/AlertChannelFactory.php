<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AlertChannelType;
use App\Models\AlertChannel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AlertChannel>
 */
class AlertChannelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => AlertChannelType::Email,
            'destination' => fake()->safeEmail(),
            'is_enabled' => true,
        ];
    }

    public function telegram(): static
    {
        return $this->state(fn (): array => [
            'type' => AlertChannelType::Telegram,
            'destination' => (string) fake()->randomNumber(9, strict: true),
        ]);
    }

    public function disabled(): static
    {
        return $this->state(['is_enabled' => false]);
    }
}
