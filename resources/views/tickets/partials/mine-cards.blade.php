@php
    $openTickets = $statusCounts['open'] ?? 0;
    $inProgressTickets = $statusCounts['in_progress'] ?? 0;
    $resolvedTickets = ($statusCounts['resolved'] ?? 0) + ($statusCounts['closed'] ?? 0);
@endphp

@include('dashboard.partials.stats', [
    'totalTickets' => $totalTickets,
    'openTickets' => $openTickets,
    'inProgressTickets' => $inProgressTickets,
    'resolvedTickets' => $resolvedTickets,
])
