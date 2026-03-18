<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    /**
     * Get the count of tickets resolved or closed today.
     *
     * @return JsonResponse
     */
    public function daily(): JsonResponse
    {
        $today = Carbon::today();
        
        // Count tickets where status is resolved or closed AND updated today
        $resolvedToday = Ticket::whereIn('status', ['resolved', 'closed'])
            ->whereDate('updated_at', $today)
            ->count();
            
        return response()->json([
            'success' => true,
            'data' => [
                'resolved_today' => $resolvedToday,
                'date' => $today->toDateString(),
            ]
        ]);
    }
}
