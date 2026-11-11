<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ServiceRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $requestId,
        public string $title,
        public string $event,
        public string $message
    ) {
    }

    /**
     * Store the notification in the database.
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Data stored in the notifications table.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'service_request_id' => $this->requestId,
            'title' => $this->title,
            'event' => $this->event,
            'message' => $this->message,
        ];
    }
}
