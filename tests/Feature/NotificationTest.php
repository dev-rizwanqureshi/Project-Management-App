<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_view_notifications(): void
    {
        $this->get(route('notifications.index'))
            ->assertRedirect(route('login'));

        $this->getJson(route('notifications.more'))
            ->assertUnauthorized();
    }

    public function test_header_shares_the_five_latest_notifications(): void
    {
        $user = User::factory()->create();
        $this->createNotifications($user, 6, 2);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('notifications.recent', 5)
                ->where('notifications.recent.0.title', 'Notification 6')
                ->where('notifications.recent.4.title', 'Notification 2')
                ->where('notifications.unread_count', 4)
            );
    }

    public function test_notifications_page_starts_with_fifty_notifications(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $this->createNotifications($user, 55);
        $this->createNotifications($otherUser, 1);

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Notifications/Index')
                ->has('notifications', 50)
                ->where('notifications.0.title', 'Notification 55')
                ->where('notifications.49.title', 'Notification 6')
                ->where('notificationMeta.total', 55)
                ->where('notificationMeta.has_more', true)
            );
    }

    public function test_view_more_returns_the_next_fifty_notification_batch(): void
    {
        $user = User::factory()->create();
        $this->createNotifications($user, 55);

        $this->actingAs($user)
            ->getJson(route('notifications.more', ['offset' => 50]))
            ->assertOk()
            ->assertJsonCount(5, 'notifications')
            ->assertJsonPath('notifications.0.title', 'Notification 5')
            ->assertJsonPath('notifications.4.title', 'Notification 1')
            ->assertJsonPath('meta.total', 55)
            ->assertJsonPath('meta.has_more', false);
    }

    private function createNotifications(User $user, int $count, int $readCount = 0): void
    {
        $rows = [];

        for ($index = 1; $index <= $count; $index++) {
            $createdAt = now()->subMinutes($count - $index);
            $rows[] = [
                'id' => (string) Str::uuid(),
                'type' => 'database',
                'notifiable_type' => User::class,
                'notifiable_id' => $user->id,
                'data' => json_encode([
                    'title' => "Notification {$index}",
                    'body' => "Notification body {$index}",
                ], JSON_THROW_ON_ERROR),
                'read_at' => $index <= $readCount ? $createdAt : null,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ];
        }

        DB::table('notifications')->insert($rows);
    }
}
