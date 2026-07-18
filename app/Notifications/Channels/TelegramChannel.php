<?php

declare(strict_types=1);

namespace App\Notifications\Channels;

use App\Notifications\Contracts\TelegramNotification;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;

/**
 * Minimal Telegram delivery channel. It sends the notification's `toTelegram`
 * text to the chat id routed by the notifiable via the Telegram Bot API.
 * If no bot token is configured the send is skipped, so the app runs without
 * Telegram credentials in development and CI.
 */
class TelegramChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        if (! $notification instanceof TelegramNotification) {
            return;
        }

        $token = config('services.telegram.bot_token');
        $chatId = $notifiable->routeNotificationFor('telegram', $notification);

        if ($token === null || $chatId === null) {
            return;
        }

        $message = $notification->toTelegram($notifiable);

        Http::asJson()->post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => 'Markdown',
        ]);
    }
}
