@php
$hasTickets = $liveMonitoringQueue->isNotEmpty();
$now = now();
@endphp
<div class="w-full flex-1 flex flex-col rounded-2xl border border-[#d0e4de] bg-white shadow-[0_8px_30px_rgb(0,0,0,0.04)] sm:overflow-hidden h-full">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 px-5 sm:px-6 py-5 bg-[#FAFCFB]">
        <div class="flex items-center gap-3">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-100 to-violet-100 text-indigo-700 shadow-inner">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-9-9c2.52 0 4.93 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/><path d="M12 7v5l4 2"/></svg>
            </span>
            <div class="leading-tight">
                <h2 class="heading-font text-[15px] font-bold text-slate-900 group-hover:text-[#12824C] transition-colors inline-flex items-center gap-2">
                    Live Active Tickets
                    <span class="flex h-2 w-2 relative">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-rose-500"></span>
                    </span>
                </h2>
                <p class="text-[12px] font-medium text-slate-500 mt-0.5">Monitoring all Open and In Progress tickets</p>
            </div>
        </div>
        <div class="flex items-center gap-3 text-xs text-slate-400 font-medium bg-white px-3 py-1.5 rounded-lg border border-slate-200">
            <svg class="h-3.5 w-3.5 animate-spin text-[#53B77A]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
            Auto-refresh active
        </div>
    </div>

    <div class="flex-1 overflow-x-auto min-h-[300px]">
        @if ($hasTickets)
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-white/90 text-[11px] uppercase tracking-wider text-slate-400 sticky top-0 z-10 backdrop-blur-md shadow-sm">
                    <tr>
                        <th class="px-5 sm:px-6 py-4 font-semibold w-12">#</th>
                        <th class="px-5 sm:px-6 py-4 font-semibold">Priority</th>
                        <th class="px-5 sm:px-6 py-4 font-semibold min-w-[200px]">Detail</th>
                        <th class="px-5 sm:px-6 py-4 font-semibold">Category</th>
                        <th class="px-5 sm:px-6 py-4 font-semibold">Status</th>
                        <th class="px-5 sm:px-6 py-4 font-semibold whitespace-nowrap text-right">Timer (SLA)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @foreach ($liveMonitoringQueue as $ticket)
                        @php
                            $minutesWaiting = rtrim($ticket->created_at->diffInMinutes($now), 's');
                            if($ticket->status === 'in_progress') {
                                // For in_progress, timer typically stops or changes color, but keeping it visible
                                $timerColor = 'text-amber-600 bg-amber-50 border-amber-200';
                            } else {
                                if($ticket->priority === 'critical') {
                                    $timerColor = 'text-white bg-rose-600 border-rose-700 font-bold shadow-sm shadow-rose-500/50 animate-pulse';
                                } elseif($ticket->priority === 'high' || $minutesWaiting > 60) {
                                    $timerColor = 'text-rose-600 bg-rose-50 border-rose-200 font-bold';
                                } elseif($minutesWaiting > 30) {
                                    $timerColor = 'text-orange-600 bg-orange-50 border-orange-200';
                                } else {
                                    $timerColor = 'text-slate-600 bg-slate-50 border-slate-200';
                                }
                            }
                        @endphp
                        <tr class="group table-hover-row relative z-0">
                            <!-- Ticket ID Link -->
                            <td class="px-5 sm:px-6 py-4 font-medium text-slate-800">
                                <a href="{{ route('tickets.show', $ticket) }}" class="inline-flex items-center gap-1.5 focus:outline-none focus:ring-2 focus:ring-[#53B77A]/50 focus:ring-offset-1 rounded before:absolute before:inset-0 before:z-[-1]">
                                    <span class="text-[#12824C]">{{ $ticket->id }}</span>
                                </a>
                            </td>
                            <!-- Priority -->
                            <td class="px-5 sm:px-6 py-4">
                                @if($ticket->priority === 'critical')
                                    <span class="inline-flex items-center gap-1.5 rounded-md bg-gradient-to-r from-rose-600 to-red-600 px-2.5 py-1 text-[10px] font-black text-white uppercase tracking-widest shadow-md shadow-rose-500/30 ring-1 ring-white/20 animate-pulse">
                                        <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
                                        {{ __('messages.priority_critical') }}
                                    </span>
                                @elseif($ticket->priority === 'high')
                                    <span class="inline-flex items-center gap-1 rounded bg-orange-100 px-2 py-0.5 text-[10px] font-bold text-orange-700 uppercase tracking-widest shadow-sm ring-1 ring-orange-500/20">
                                        {{ __('messages.priority_high') }}
                                    </span>
                                @elseif($ticket->priority === 'medium')
                                    <span class="inline-flex items-center gap-1 rounded bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-700 uppercase tracking-widest">
                                        {{ __('messages.priority_medium') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-600 uppercase tracking-widest">
                                        {{ __('messages.priority_low') }}
                                    </span>
                                @endif
                            </td>
                            <!-- Detail -->
                            <td class="px-5 sm:px-6 py-4">
                                <div class="flex flex-col gap-1 w-full max-w-[220px] sm:max-w-[400px]">
                                    <p class="font-semibold text-slate-900 group-hover:text-[#12824C] transition-colors truncate" title="{{ $ticket->title }}">
                                        {{ $ticket->title }}
                                    </p>
                                    <div class="flex items-center gap-2 text-xs text-slate-500">
                                        <div class="flex items-center gap-1">
                                            <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                            <span class="truncate max-w-[120px]">{{ $ticket->user?->name ?? $ticket->reporter_name }}</span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <!-- Category -->
                            <td class="px-5 sm:px-6 py-4">
                                <div class="flex flex-col">
                                    <span class="text-[13px] font-medium text-slate-700">{{ $ticket->category?->name ?? 'Uncategorized' }}</span>
                                    <span class="text-[11px] text-slate-400">{{ $ticket->department?->name ?? '' }}</span>
                                </div>
                            </td>
                            <!-- Status -->
                            <td class="px-5 sm:px-6 py-4">
                                @if($ticket->status === 'open')
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 border border-slate-200 pl-1.5 pr-2.5 py-0.5 text-xs font-medium text-slate-600 shadow-sm transition group-hover:bg-white">
                                        <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                                        Open
                                    </span>
                                @elseif($ticket->status === 'in_progress')
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-50 border border-blue-200 pl-1.5 pr-2.5 py-0.5 text-xs font-medium text-blue-700 shadow-sm transition group-hover:bg-white">
                                        <span class="relative flex h-1.5 w-1.5">
                                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                                            <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-blue-500"></span>
                                        </span>
                                        Progress
                                    </span>
                                @endif
                            </td>
                            <!-- Timer -->
                            <td class="px-5 sm:px-6 py-4 text-right">
                                <span class="inline-flex items-center gap-1.5 rounded-md border px-2 py-1 text-xs shadow-sm {{ $timerColor }}">
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                                    @if($minutesWaiting < 60)
                                        {{ $minutesWaiting }} min
                                    @else
                                        {{ floor($minutesWaiting / 60) }}h {{ $minutesWaiting % 60 }}m
                                    @endif
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="flex flex-col items-center justify-center py-16 px-6 text-center h-full">
                <div class="h-16 w-16 rounded-full bg-[#FAFCFB] ring-8 ring-green-50 flex items-center justify-center text-emerald-300 mb-4 shadow-inner">
                    <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12l5 5L20 7"/></svg>
                </div>
                <h3 class="text-lg font-semibold text-slate-900">All caught up!</h3>
                <p class="text-sm text-slate-500 mt-1 max-w-sm">No active tickets waiting in the queue. Great job keeping the systems running smoothly.</p>
            </div>
        @endif
    </div>
</div>
