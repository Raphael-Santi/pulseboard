<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\AlertChannelType;
use App\Models\AlertChannel;
use App\Models\Incident;
use App\Notifications\Channels\TelegramChannel;
use App\Notifications\Contracts\TelegramNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent once, when a monitor's open incident is auto-closed by a passing check.
 */
class MonitorRecoveredNotification extends Notification implements ShouldQueue, TelegramNotification
{
    use Queueable;

    public function __construct(public readonly Incident $incident) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return [$this->channelFor($notifiable)];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $monitor = $this->incident->monitor;

        return (new MailMessage)
            ->success()
            ->subject("[Pulseboard] {$monitor->name} восстановлен")
            ->line("{$monitor->name} снова отвечает — инцидент закрыт.");
    }

    public function toTelegram(object $notifiable): string
    {
        $monitor = $this->incident->monitor;

        return "🟢 *{$monitor->name} восстановлен*\nИнцидент решён.";
    }

    private function channelFor(object $notifiable): string
    {
        return $notifiable instanceof AlertChannel && $notifiable->type === AlertChannelType::Telegram
            ? TelegramChannel::class
            : 'mail';
    }
}
