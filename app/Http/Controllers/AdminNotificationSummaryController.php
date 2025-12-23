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

        return response()->json([
            'tickets' => $ticketCount,
            'users' => $userCount,
        ]);
    }
}
