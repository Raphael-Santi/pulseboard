<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\MonitorType;
use App\Models\Monitor;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Monitor>
 */
class MonitorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $domain = fake()->unique()->domainName();

        return [
            'user_id' => User::factory(),
            'name' => $domain,
            'type' => MonitorType::Http,
            'target' => 'https://'.$domain,
            'port' => null,
            'interval_sec' => 60,
            'timeout_sec' => 10,
            'failure_threshold' => 3,
            'is_paused' => false,
        ];
    }

    public function paused(): static
    {
        return $this->state(['is_paused' => true]);
    }

    public function tcp(): static
    {
        return $this->state(fn (): array => [
            'type' => MonitorType::Tcp,
            'target' => fake()->domainName(),
            'port' => 443,
        ]);
    }

    public function heartbeat(): static
    {
        return $this->state(fn (): array => [
            'type' => MonitorType::Heartbeat,
            'target' => null,
            'token' => Str::random(48),
            'grace_sec' => 300,
        ]);
    }
}
