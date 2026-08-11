<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Notifications\DatabaseNotification;

class NotificationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof DatabaseNotification) {
            return [];
        }

        $notification = $this->resource;

        $data = $notification->data;

        return [
            'id' => (string) $notification->id,
            'title' => (string) ($data['title'] ?? 'Notification'),
            'body' => isset($data['body']) ? (string) $data['body'] : null,
            'url' => isset($data['url']) ? (string) $data['url'] : null,
            'read_at' => $notification->read_at?->toISOString(),
            'created_at' => $notification->created_at?->toISOString(),
        ];
    }
}
