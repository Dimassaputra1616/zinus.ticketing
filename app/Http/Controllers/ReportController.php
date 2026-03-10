<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function export(Request $request): StreamedResponse
    {
        $request->validate([
            'start_date' => ['required', 'date'],
            'end_date'   => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $startDate = \Carbon\Carbon::parse($request->input('start_date'))->startOfDay();
        $endDate   = \Carbon\Carbon::parse($request->input('end_date'))->endOfDay();

        $tickets = Ticket::with(['category', 'department', 'user', 'assignedAdmin'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->get();

        $filename = 'ticket-report_' . $startDate->format('Ymd') . '_to_' . $endDate->format('Ymd') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control'       => 'no-cache, no-store, must-revalidate',
            'Pragma'              => 'no-cache',
            'Expires'             => '0',
        ];

        $callback = function () use ($tickets) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM for Excel compatibility
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Header row
            fputcsv($handle, [
                'Ticket ID',
                'Title',
                'Status',
                'Priority',
                'Category',
                'Department',
                'Reporter',
                'Reporter Email',
                'Assigned To',
                'Created At',
                'Last Updated',
                'Resolution Time (hours)',
            ]);

            foreach ($tickets as $ticket) {
                $resolutionTime = '';
                if (in_array($ticket->status, ['resolved', 'closed'])) {
                    $minutes = $ticket->created_at->diffInMinutes($ticket->updated_at);
                    $resolutionTime = round($minutes / 60, 1);
                }

                fputcsv($handle, [
                    $ticket->id,
                    $ticket->title,
                    ucfirst(str_replace('_', ' ', $ticket->status)),
                    ucfirst($ticket->priority ?? '-'),
                    $ticket->category?->name ?? '-',
                    $ticket->department?->name ?? '-',
                    $ticket->reporter_name ?? ($ticket->user?->name ?? '-'),
                    $ticket->reporter_email ?? ($ticket->user?->email ?? '-'),
                    $ticket->assignedAdmin?->name ?? 'Unassigned',
                    $ticket->created_at->format('Y-m-d H:i:s'),
                    $ticket->updated_at->format('Y-m-d H:i:s'),
                    $resolutionTime,
                ]);
            }

            fclose($handle);
        };

        return new StreamedResponse($callback, 200, $headers);
    }
}
