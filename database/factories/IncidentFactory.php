<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Incident;
use App\Models\Monitor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Incident>
 */
class IncidentFactory extends Factory
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
            'opened_at' => now()->subHour(),
            'acknowledged_at' => null,
            'closed_at' => null,
            'cause' => 'Connection timed out after 3 consecutive failures',
        ];
    }

    public function acknowledged(): static
    {
        return $this->state(['acknowledged_at' => now()->subMinutes(30)]);
    }

    public function closed(): static
    {
        return $this->state(['closed_at' => now()]);
    }
}
