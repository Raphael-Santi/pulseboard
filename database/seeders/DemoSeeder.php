<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AlertChannelType;
use App\Enums\CheckStatus;
use App\Enums\IncidentUpdateStatus;
use App\Enums\MonitorType;
use App\Models\CheckResult;
use App\Models\Monitor;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Builds a realistic 30-day demo dataset so a fresh install has a live-looking
 * dashboard and public status page. Login: demo@pulseboard.test / password.
 */
class DemoSeeder extends Seeder
{
    private const HISTORY_DAYS = 30;

    private const STEP_SECONDS = 1800; // one synthetic check every 30 minutes

    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'Demo User',
            'email' => 'demo@pulseboard.test',
            'password' => Hash::make('password'),
        ]);

        $channels = $user->alertChannels()->createMany([
            ['type' => AlertChannelType::Email, 'destination' => 'alerts@pulseboard.test'],
            ['type' => AlertChannelType::Telegram, 'destination' => '123456789'],
        ]);

        $start = CarbonImmutable::now()->subDays(self::HISTORY_DAYS);
        $end = CarbonImmutable::now();

        $definitions = [
            ['name' => 'Marketing site', 'type' => MonitorType::Http, 'target' => 'https://example.com', 'port' => null, 'latency' => 90],
            ['name' => 'Public API', 'type' => MonitorType::Http, 'target' => 'https://api.example.com/health', 'port' => null, 'latency' => 140],
            ['name' => 'Primary database', 'type' => MonitorType::Tcp, 'target' => 'db.example.com', 'port' => 5432, 'latency' => 25],
            ['name' => 'DNS resolution', 'type' => MonitorType::Dns, 'target' => 'example.com', 'port' => null, 'latency' => 40],
            ['name' => 'Edge network', 'type' => MonitorType::Ping, 'target' => '1.1.1.1', 'port' => null, 'latency' => 15],
        ];

        $monitors = [];

        foreach ($definitions as $definition) {
            $monitor = $user->monitors()->create([
                'name' => $definition['name'],
                'type' => $definition['type'],
                'target' => $definition['target'],
                'port' => $definition['port'],
                'interval_sec' => 300,
                'timeout_sec' => 10,
                'failure_threshold' => 3,
            ]);

            $monitor->alertChannels()->attach($channels->pluck('id'));

            $this->seedHistory($monitor, $start, $end, $definition['latency']);

            $monitors[] = $monitor;
        }

        $monitors[] = $this->seedHeartbeat($user, $start, $end);

        $this->seedStatusPage($user, $monitors);
    }

    /**
     * Generate 30 days of checks with one or two short outages, and open a
     * matching (already resolved) incident for each outage.
     */
    private function seedHistory(Monitor $monitor, CarbonImmutable $start, CarbonImmutable $end, int $baselineMs): void
    {
        $outages = $this->randomOutages($start, $end);

        $rows = [];

        for ($moment = $start; $moment->lessThan($end); $moment = $moment->addSeconds(self::STEP_SECONDS)) {
            $down = $this->within($outages, $moment);

            $rows[] = [
                'monitor_id' => $monitor->id,
                'status' => $down ? CheckStatus::Failed->value : CheckStatus::Ok->value,
                'latency_ms' => $down ? null : $baselineMs + random_int(-15, 45),
                'error' => $down ? 'Connection timed out' : null,
                'checked_at' => $moment->toDateTimeString(),
            ];

            if (count($rows) >= 500) {
                CheckResult::insert($rows);
                $rows = [];
            }
        }

        if ($rows !== []) {
            CheckResult::insert($rows);
        }

        foreach ($outages as [$openedAt, $closedAt]) {
            $incident = $monitor->incidents()->create([
                'opened_at' => $openedAt,
                'closed_at' => $closedAt,
                'cause' => 'Connection timed out',
            ]);

            $incident->updates()->create([
                'status' => IncidentUpdateStatus::Investigating,
                'message' => 'We are investigating connectivity problems.',
            ]);
            $incident->updates()->create([
                'status' => IncidentUpdateStatus::Resolved,
                'message' => 'The monitor is responding normally again.',
            ]);
        }
    }

    private function seedHeartbeat(User $user, CarbonImmutable $start, CarbonImmutable $end): Monitor
    {
        $monitor = new Monitor([
            'name' => 'Nightly backup',
            'type' => MonitorType::Heartbeat,
            'interval_sec' => 86400,
            'grace_sec' => 3600,
            'timeout_sec' => 10,
            'failure_threshold' => 1,
        ]);
        $monitor->token = Str::random(48);
        $user->monitors()->save($monitor);

        $rows = [];
        for ($moment = $start; $moment->lessThan($end); $moment = $moment->addDay()) {
            $rows[] = [
                'monitor_id' => $monitor->id,
                'status' => CheckStatus::Ok->value,
                'latency_ms' => null,
                'error' => null,
                'checked_at' => $moment->toDateTimeString(),
            ];
        }
        CheckResult::insert($rows);

        $monitor->last_ping_at = now();
        $monitor->save();

        return $monitor;
    }

    /**
     * @param  list<Monitor>  $monitors
     */
    private function seedStatusPage(User $user, array $monitors): void
    {
        $page = $user->statusPages()->create([
            'slug' => 'pulseboard-demo',
            'title' => 'Pulseboard Demo',
            'is_public' => true,
        ]);

        $pivot = [];
        foreach ($monitors as $index => $monitor) {
            $pivot[$monitor->id] = ['display_name' => $monitor->name, 'sort_order' => $index];
        }

        $page->monitors()->sync($pivot);
    }

    /**
     * @return list<array{0: CarbonImmutable, 1: CarbonImmutable}>
     */
    private function randomOutages(CarbonImmutable $start, CarbonImmutable $end): array
    {
        $spanMinutes = (int) $start->diffInMinutes($end);
        $outages = [];

        foreach (range(1, random_int(1, 2)) as $ignored) {
            $openedAt = $start->addMinutes(random_int(0, $spanMinutes));
            $outages[] = [$openedAt, $openedAt->addMinutes(random_int(60, 180))];
        }

        return $outages;
    }

    /**
     * @param  list<array{0: CarbonImmutable, 1: CarbonImmutable}>  $outages
     */
    private function within(array $outages, CarbonImmutable $moment): bool
    {
        foreach ($outages as [$openedAt, $closedAt]) {
            if ($moment->betweenIncluded($openedAt, $closedAt)) {
                return true;
            }
        }

        return false;
    }
}
