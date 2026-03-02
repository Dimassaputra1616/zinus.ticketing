@php
    $openTickets = ($statusCounts['open'] ?? 0);
    $inProgressTickets = ($statusCounts['in_progress'] ?? 0) + ($statusCounts['assigned'] ?? 0) + ($statusCounts['waiting_user'] ?? 0);
    $resolvedTickets = ($statusCounts['resolved'] ?? 0) + ($statusCounts['closed'] ?? 0);
@endphp

<x-ui.stats-panel
    title="{{ __('messages.stats_title') }}"
    subtitle="{{ __('messages.stats_subtitle') }}"
    class="mt-1 w-full max-w-none"
>
    @include('dashboard.partials.stats', [
        'totalTickets' => $totalTickets,
        'openTickets' => $openTickets,
        'inProgressTickets' => $inProgressTickets,
        'resolvedTickets' => $resolvedTickets,
    ])
</x-ui.stats-panel>
