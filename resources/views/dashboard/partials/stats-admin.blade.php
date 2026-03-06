@php
    $cards = [
        [
            'label' => 'High Priority',
            'value' => $highPriorityTickets ?? 0,
            'description' => 'Tickets needing immediate attention',
            'badge' => 'URGENT',
            'badge_classes' => 'bg-gradient-to-r from-[#FFF0F0] to-[#FFE5E5] text-[#E11D48] border border-[#FECDD3]',
            'icon_color' => '#E11D48',
            'number_color' => '#BE123C',
        ],
        [
            'label' => __('messages.stat_open'),
            'value' => $openTickets,
            'description' => __('messages.stat_open_desc'),
            'badge' => __('messages.stat_open_badge'),
            'badge_classes' => 'bg-gradient-to-r from-[#FFF4CC] to-[#FFE3D0] text-[#B45309] border border-[#FFD966]',
            'icon_color' => '#FFD966',
            'number_color' => '#B45309',
        ],
        [
            'label' => __('messages.stat_progress'),
            'value' => $inProgressTickets,
            'description' => __('messages.stat_progress_desc'),
            'badge' => __('messages.stat_progress_badge'),
            'badge_classes' => 'bg-gradient-to-r from-[#E3EEFB] to-[#F5F8FF] text-[#23455D] border border-[#BBD2EA]',
            'icon_color' => '#23455D',
            'number_color' => '#23455D',
        ],
        [
            'label' => __('messages.stat_resolved'),
            'value' => $resolvedTickets,
            'description' => __('messages.stat_resolved_desc'),
            'badge' => __('messages.stat_resolved_badge'),
            'badge_classes' => 'bg-gradient-to-r from-[#E1F5EB] to-[#F2FBF6] text-[#12824C] border border-[#B7E3CB]',
            'icon_color' => '#12824C',
            'number_color' => '#12824C',
        ],
];
@endphp

<div class="space-y-3" data-stats-wrapper>
    <div class="hidden grid grid-cols-1 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4 items-stretch animate-pulse" data-stats-skeleton>
        @for ($i = 0; $i < 4; $i++)
            <div class="h-[160px] rounded-xl border border-slate-200/60 bg-slate-50 shadow-inner shadow-white/50 flex flex-col gap-2.5 p-4">
                <div class="h-3.5 w-20 rounded bg-slate-200"></div>
                <div class="h-6 w-14 rounded bg-slate-200"></div>
                <div class="h-3.5 w-28 rounded bg-slate-200"></div>
                <div class="mt-auto h-7 w-20 rounded-full bg-slate-200"></div>
            </div>
        @endfor
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4 items-stretch" style="grid-auto-rows: 1fr;" data-stats-content>
        @foreach ($cards as $card)
            @php
                $statusParam = match ($card['label']) {
                    'High Priority' => 'priority_high',
                    __('messages.stat_open') => 'open',
                    __('messages.stat_progress') => 'in_progress',
                    __('messages.stat_resolved') => 'resolved',
                    default => \Illuminate\Support\Str::slug($card['label'], '_'),
                };
            @endphp
            <a
                class="stat-card surface-card group relative flex flex-col h-full min-h-[170px] w-full rounded-xl border border-slate-200 px-4 py-4 lg:px-5 lg:py-5 bg-white/95 shadow-md shadow-emerald-900/10 transition hover:border-[#A7DCC1] hover:shadow-lg hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-emerald-200"
                href="{{ $statusParam === 'priority_high' ? route('tickets.index', ['priority' => 'high']) : route('tickets.index', ['status' => $statusParam]) }}"
                title="{{ $card['description'] }}"
            >
                <span class="pointer-events-none absolute right-4 top-3 text-[9px] font-semibold uppercase tracking-[0.24em] text-slate-400 opacity-0 transition group-hover:opacity-100">
                    Quick filter
                </span>
                <div class="flex-1 flex flex-col items-center justify-center text-center gap-2.5">
                    <div class="flex items-center gap-2.5 text-slate-500">
                        <span class="h-7 w-7 rounded-full bg-white/80 shadow-inner shadow-white flex items-center justify-center" aria-hidden="true">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="{{ $card['icon_color'] }}" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                @switch($card['label'])
                                    @case('High Priority')
                                        <path d="M12 2v20"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                                        @break
                                    @case(__('messages.stat_open'))
                                        <circle cx="12" cy="12" r="9" />
                                        <path d="M12 8v4" />
                                        <path d="M12 16h.01" />
                                        @break
                                    @case(__('messages.stat_progress'))
                                        <circle cx="12" cy="12" r="9" />
                                        <path d="M12 7v5l3 3" />
                                        @break
                                    @case(__('messages.stat_resolved'))
                                        <path d="m9 12 2 2 4-4" />
                                        <circle cx="12" cy="12" r="9" />
                                        @break
                                @endswitch
                            </svg>
                        </span>
                        <p class="heading-font text-xs font-semibold uppercase tracking-[0.28em]" style="color: #23455D;">{{ $card['label'] }}</p>
                    </div>
                    <p
                        class="text-4xl font-extrabold leading-tight"
                        style="color: {{ $card['number_color'] ?? '#0C1F2C' }}"
                        data-countup="{{ (int) $card['value'] }}"
                    >
                        0
                    </p>
                    <p class="text-[11px] text-gray-600 leading-relaxed max-w-[200px]">{{ $card['description'] }}</p>
                </div>

                <div class="mt-3 flex justify-center">
                    <span class="badge-chip inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-[11px] font-bold uppercase tracking-[0.18em] {{ $card['badge_classes'] }}">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="none" stroke="{{ $card['icon_color'] ?? '#118A58' }}" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 11l2 2 4-4" />
                            <circle cx="9" cy="9" r="7" />
                        </svg>
                        {{ $card['badge'] }}
                    </span>
                </div>
            </a>
        @endforeach
    </div>
</div>
