<?php

namespace App\Http\Controllers;

use App\Notifications\TicketCreatedNotification;
use App\Notifications\UserRegisteredNotification;
use Illuminate\Http\Request;

class AdminNotificationSummaryController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        if (! $user || ! $user->isAdmin()) {
            abort(403);
        }

        $ticketCount = (int) $user->unreadNotifications()
            ->where('type', TicketCreatedNotification::class)
            ->count();

        $userCount = (int) $user->unreadNotifications()
            ->where('type', UserRegisteredNotification::class)
            ->count();

        $conversationCount = \App\Models\Conversation::where('is_open', true)
            ->withUnreadCount()
            ->get()
            ->sum('unread_count');

        return response()->json([
            'tickets' => $ticketCount,
            'users' => $userCount,
            'conversations' => $conversationCount,
        ]);
    }
}
