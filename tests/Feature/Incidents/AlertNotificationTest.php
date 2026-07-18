<?php

declare(strict_types=1);

use App\Models\AlertChannel;
use App\Models\CheckResult;
use App\Models\Incident;
use App\Models\Monitor;
use App\Notifications\Channels\TelegramChannel;
use App\Notifications\MonitorDownNotification;
use App\Notifications\MonitorRecoveredNotification;
use App\Services\IncidentManager;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();
});

it('alerts only the enabled channels when an incident opens', function () {
    $monitor = Monitor::factory()->create(['failure_threshold' => 1]);
    $enabled = AlertChannel::factory()->for($monitor->user)->create();
    $disabled = AlertChannel::factory()->for($monitor->user)->disabled()->create();
    $monitor->alertChannels()->attach([$enabled->id, $disabled->id]);

    CheckResult::factory()->for($monitor)->failed()->create();
    app(IncidentManager::class)->record($monitor, $monitor->checkResults()->firstOrFail());

    Notification::assertSentTo($enabled, MonitorDownNotification::class);
    Notification::assertNotSentTo($disabled, MonitorDownNotification::class);
});

it('routes a telegram channel through the telegram driver', function () {
    $monitor = Monitor::factory()->create(['failure_threshold' => 1]);
    $telegram = AlertChannel::factory()->for($monitor->user)->telegram()->create();
    $monitor->alertChannels()->attach($telegram);

    CheckResult::factory()->for($monitor)->failed()->create();
    app(IncidentManager::class)->record($monitor, $monitor->checkResults()->firstOrFail());

    Notification::assertSentTo(
        $telegram,
        MonitorDownNotification::class,
        function (MonitorDownNotification $notification, array $channels): bool {
            return in_array(TelegramChannel::class, $channels, true);
        },
    );
});

it('sends a recovery alert when the incident closes', function () {
    $monitor = Monitor::factory()->create();
    $channel = AlertChannel::factory()->for($monitor->user)->create();
    $monitor->alertChannels()->attach($channel);
    Incident::factory()->for($monitor)->create(['closed_at' => null]);

    CheckResult::factory()->for($monitor)->create();
    app(IncidentManager::class)->record($monitor, $monitor->checkResults()->firstOrFail());

    Notification::assertSentTo($channel, MonitorRecoveredNotification::class);
});
