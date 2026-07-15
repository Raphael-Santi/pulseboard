<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CheckStatus;
use App\Models\CheckResult;
use App\Models\Monitor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CheckResult>
 */
class CheckResultFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'monitor_id' => Monitor::factory(),
            'status' => CheckStatus::Ok,
            'latency_ms' => fake()->numberBetween(5, 900),
            'error' => null,
            'checked_at' => now(),
        ];
    }

    public function failed(): static
    {
        return $this->state([
            'status' => CheckStatus::Failed,
            'latency_ms' => null,
            'error' => 'Connection timed out',
        ]);
    }
}
