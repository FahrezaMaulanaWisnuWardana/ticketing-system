<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class InviteUser extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly string $url) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /** @return array<string, string> */
    public function viaQueues(): array
    {
        return ['mail' => 'notifications', 'database' => 'notifications'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('mail.invite_subject', ['app' => config('app.name')]))
            ->line(__('mail.invite_line', ['app' => config('app.name')]))
            ->action(__('mail.invite_action'), $this->url)
            ->line(__('mail.invite_expiry', ['hours' => (int) config('ticketing.invite_ttl_hours')]));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => __('notifications.invite_title'),
            'url' => $this->url,
            'user_id' => $notifiable instanceof User ? $notifiable->id : null,
        ];
    }
}
