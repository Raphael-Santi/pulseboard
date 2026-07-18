<?php

declare(strict_types=1);

namespace App\Notifications\Contracts;

/**
 * A notification that can render itself as Telegram message text.
 */
interface TelegramNotification
{
    public function toTelegram(object $notifiable): string;
}
