<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\User;
use App\Services\NotificationPreference;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class TicketEventNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /** @param array<int, string> $messages */
    public function __construct(
        public readonly string $event,
        public readonly array $messages,
        public readonly string $ticketKey,
        public readonly string $ticketTitle,
        public readonly string $url,
        public readonly ?string $excerpt = null,
        public readonly ?int $ticketId = null,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        if (! $notifiable instanceof User) {
            return ['database'];
        }

        $channels = [];
        foreach (['mail', 'database', 'broadcast'] as $channel) {
            if (NotificationPreference::enabled($notifiable, $this->event, $channel)) {
                $channels[] = $channel;
            }
        }

        return $channels;
    }

    /** @return array<string, string> */
    public function viaQueues(): array
    {
        return ['mail' => 'notifications', 'database' => 'notifications', 'broadcast' => 'notifications'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject("[{$this->ticketKey}] ".$this->messages[0])
            ->line($this->messages[0]);

        if (count($this->messages) > 1) {
            $message->line(__('notifications.more_updates', ['count' => count($this->messages) - 1]));
        }
        if ($this->excerpt !== null && $this->excerpt !== '') {
            $message->line($this->excerpt);
        }

        return $message->action(__('notifications.view_ticket'), $this->url);
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'event' => $this->event,
            'ticket_id' => $this->ticketId,
            'ticket_key' => $this->ticketKey,
            'title' => $this->messages[0],
            'body' => implode("\n", $this->messages),
            'url' => $this->url,
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'event' => $this->event,
            'ticket_id' => $this->ticketId,
            'ticket_key' => $this->ticketKey,
            'title' => $this->messages[0],
        ]);
    }
}
