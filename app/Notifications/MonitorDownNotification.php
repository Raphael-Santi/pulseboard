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
 * Sent once, when a monitor's incident is opened. The delivery channel is
 * chosen from the alert channel's type, so one notification class serves both
 * email and Telegram.
 */
class MonitorDownNotification extends Notification implements ShouldQueue, TelegramNotification
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
            ->error()
            ->subject("[Pulseboard] {$monitor->name} is down")
            ->line("{$monitor->name} failed its checks and an incident was opened.")
            ->line("Cause: {$this->incident->cause}");
    }

    public function toTelegram(object $notifiable): string
    {
        $monitor = $this->incident->monitor;

        return "🔴 *{$monitor->name} is down*\nCause: {$this->incident->cause}";
    }

    private function channelFor(object $notifiable): string
    {
        return $notifiable instanceof AlertChannel && $notifiable->type === AlertChannelType::Telegram
            ? TelegramChannel::class
            : 'mail';
    }
}
