<?php

declare(strict_types=1);

use App\Models\AlertChannel;
use App\Models\Incident;
use App\Notifications\Channels\TelegramChannel;
use App\Notifications\MonitorDownNotification;
use Illuminate\Support\Facades\Http;

it('posts the message to the telegram bot api', function () {
    Http::fake(['*' => Http::response(['ok' => true])]);
    config(['services.telegram.bot_token' => 'test-token']);

    $channel = AlertChannel::factory()->telegram()->create(['destination' => '987654']);
    $incident = Incident::factory()->create();

    (new TelegramChannel)->send($channel, new MonitorDownNotification($incident));

    Http::assertSent(function ($request): bool {
        return str_contains($request->url(), 'bottest-token/sendMessage')
            && $request['chat_id'] === '987654'
            && str_contains((string) $request['text'], 'недоступен');
    });
});

it('skips sending when no bot token is configured', function () {
    Http::fake();
    config(['services.telegram.bot_token' => null]);

    $channel = AlertChannel::factory()->telegram()->create();
    $incident = Incident::factory()->create();

    (new TelegramChannel)->send($channel, new MonitorDownNotification($incident));

    Http::assertNothingSent();
});
