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

    /**
     * Get all tickets for n8n sync.
     *
     * @return JsonResponse
     */
    public function syncToN8n(): JsonResponse
    {
        $tickets = Ticket::with(['user', 'category', 'department', 'assignedAdmin'])->get();
        
        $data = $tickets->map(function ($ticket) {
            return [
                'action' => 'sync',
                'ticket_id' => $ticket->id,
                'title' => $ticket->title,
                'description' => strip_tags($ticket->description),
                'status' => strtoupper($ticket->status),
                'priority' => strtoupper($ticket->priority),
                'category' => $ticket->category ? $ticket->category->name : '-',
                'department' => $ticket->department ? $ticket->department->name : '-',
                'created_by' => $ticket->user ? $ticket->user->name : 'Unknown User',
                'assigned_admin' => $ticket->assignedAdmin ? $ticket->assignedAdmin->name : 'Unassigned',
                'created_at' => $ticket->created_at ? $ticket->created_at->timezone('Asia/Jakarta')->format('Y-m-d H:i:s') : null,
                'updated_at' => $ticket->updated_at ? $ticket->updated_at->timezone('Asia/Jakarta')->format('Y-m-d H:i:s') : null,
                'resolved_at' => $ticket->resolved_at ? $ticket->resolved_at->timezone('Asia/Jakarta')->format('Y-m-d H:i:s') : null,
                'spreadsheet_id' => config('services.n8n.google_spreadsheet_id'),

                // Add keys that match Google Sheets headers exactly
                'Ticket' => $ticket->title ?: strip_tags($ticket->description),
                'Catagory' => $ticket->category ? $ticket->category->name : '-', // Matching typo in sheet "Catagory"
                'Assigned To' => $ticket->assignedAdmin ? $ticket->assignedAdmin->name : 'Unassigned',
            ];
        });

        return response()->json([
            'success' => true,
            'count' => $data->count(),
            'data' => $data
        ]);
    }
}
