<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\IncidentUpdateStatus;
use App\Models\Incident;
use App\Models\IncidentUpdate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IncidentUpdate>
 */
class IncidentUpdateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'incident_id' => Incident::factory(),
            'status' => IncidentUpdateStatus::Investigating,
            'message' => fake()->sentence(),
        ];
    }

    public function resolved(): static
    {
        return $this->state(['status' => IncidentUpdateStatus::Resolved]);
    }
}
