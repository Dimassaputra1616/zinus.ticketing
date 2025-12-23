@php
    $statusBadge = $statusBadge ?? [
        'waiting' => 'bg-amber-100 text-amber-700 border border-amber-200',
        'approved' => 'bg-emerald-100 text-emerald-700 border border-emerald-200',
        'returned' => 'bg-sky-100 text-sky-700 border border-sky-200',
        'rejected' => 'bg-rose-100 text-rose-700 border border-rose-200',
    ];
@endphp

@if ($isAdmin)
    @include('loans.partials.table_admin', ['logs' => $logs, 'statuses' => $statuses, 'statusBadge' => $statusBadge])
@else
    @include('loans.partials.table_user', ['logs' => $logs, 'statuses' => $statuses, 'statusBadge' => $statusBadge])
@endif
