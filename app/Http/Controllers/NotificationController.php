<?php

namespace App\Http\Controllers;

use App\Http\Resources\NotificationResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    private const PAGE_SIZE = 50;

    public function index(Request $request): Response
    {
        $user = $this->user($request);
        $total = $user->notifications()->count();
        $notifications = $user->notifications()
            ->latest()
            ->limit(self::PAGE_SIZE)
            ->get();

        return Inertia::render('Notifications/Index', [
            'notifications' => NotificationResource::collection($notifications)->resolve($request),
            'notificationMeta' => [
                'total' => $total,
                'has_more' => $notifications->count() < $total,
            ],
        ]);
    }

    public function more(Request $request): JsonResponse
    {
        $user = $this->user($request);
        $offset = max(0, $request->integer('offset'));
        $total = $user->notifications()->count();
        $notifications = $user->notifications()
            ->latest()
            ->offset($offset)
            ->limit(self::PAGE_SIZE)
            ->get();

        return response()->json([
            'notifications' => NotificationResource::collection($notifications)->resolve($request),
            'meta' => [
                'total' => $total,
                'has_more' => $offset + $notifications->count() < $total,
            ],
        ]);
    }

    private function user(Request $request): User
    {
        $user = $request->user();

        abort_unless($user instanceof User, 403);

        return $user;
    }
}
