@props([
    'tickets' => collect(),
    'title' => 'Riwayat Tiket',
    'subtitle' => null,
    'total' => null,
    'isAdmin' => false,
    'icon' => null,
    'viewAllUrl' => null,
])

<div {{ $attributes->merge(['class' => 'bg-white rounded-2xl border border-[#CFEADF] px-5 sm:px-6 py-5 space-y-4 w-full surface-card shadow-md max-h-none min-h-0 overflow-visible']) }} data-scroll-animate data-ticket-list>
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="space-y-1">
            <p class="heading-font text-[9px] font-semibold uppercase tracking-[0.32em] text-[#23455D]/70">{{ $title }}</p>
            @if ($subtitle)
                <p class="text-[12px] text-gray-600">{{ $subtitle }}</p>
            @endif
        </div>
        <div class="flex flex-wrap items-center gap-1.5 text-[11px] text-slate-600">
            <label class="sr-only">Filter status</label>
            <select data-ticket-filter class="rounded-full border border-[#CFEADF] bg-white px-2.5 py-1 text-[11px] font-medium text-[#23455D] focus:border-emerald-300 focus:ring-1 focus:ring-emerald-100">
                <option value="all">Semua</option>
                <option value="open">Open</option>
                <option value="in_progress">In Progress</option>
                <option value="done">Selesai</option>
            </select>
            <label class="sr-only">Urutkan</label>
            <select data-ticket-sort class="rounded-full border border-[#CFEADF] bg-white px-2.5 py-1 text-[11px] font-medium text-[#23455D] focus:border-emerald-300 focus:ring-1 focus:ring-emerald-100">
                <option value="newest">Terbaru</option>
                <option value="oldest">Terlama</option>
                <option value="title">Judul (A-Z)</option>
            </select>
            @if (! is_null($total))
                <span class="inline-flex items-center rounded-full border border-[#CFEADF] bg-white/80 px-2 py-0.5 text-[10px] text-[#23455D] font-medium">{{ $total }} tiket</span>
            @endif
            <span class="inline-flex items-center gap-1 rounded-full border border-[#C8E2D8] bg-[#EDF3F2] px-2 py-0.5 text-[10px] text-[#12824C] font-medium">
                Live 8s
                <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
            </span>
            @if ($viewAllUrl)
                <a href="{{ $viewAllUrl }}" class="text-[11px] font-semibold text-[#118A58] inline-flex items-center gap-1 rounded-full border border-[#CFEADF] bg-white px-2.5 py-1 shadow-sm hover:bg-[#e5f8ef] transition">
                    Lihat Semua
                    <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M7 5l5 5-5 5" />
                    </svg>
                </a>
            @endif
        </div>
    </div>

    <div class="space-y-2.5" data-ticket-body>
        @if ($tickets->count() === 0)
            <div class="text-center py-10 space-y-3 opacity-90">
                <div class="mx-auto flex h-24 w-24 items-center justify-center rounded-2xl bg-slate-50 shadow-inner shadow-white">
                    <svg class="h-14 w-14 text-slate-300" viewBox="0 0 120 120" role="img" aria-hidden="true">
                        <defs>
                            <linearGradient id="ticketEmptyGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" stop-color="#E0F2F1" />
                                <stop offset="100%" stop-color="#F3F4F6" />
                            </linearGradient>
                        </defs>
                        <rect x="15" y="28" width="90" height="58" rx="12" fill="url(#ticketEmptyGradient)" />
                        <rect x="22" y="38" width="76" height="36" rx="8" fill="#fff" stroke="#E5E7EB" stroke-width="2" />
                        <rect x="30" y="47" width="48" height="6" rx="3" fill="#CBD5F5" />
                        <rect x="30" y="59" width="32" height="6" rx="3" fill="#D1FAE5" />
                        <circle cx="86" cy="55" r="7" fill="#FDE68A" stroke="#FBBF24" stroke-width="2" />
                    </svg>
                </div>
                <div class="space-y-1">
                    <p class="text-sm font-semibold text-gray-800">Belum ada tiket terbaru</p>
                    <p class="text-sm text-gray-600">Begitu tiket baru masuk, ringkasannya otomatis muncul di sini.</p>
                </div>
            </div>
        @else
            @foreach ($tickets as $ticket)
                @php
                    $normalizedStatus = match ($ticket->status) {
                        'in_progress' => 'in_progress',
                        'resolved', 'closed' => 'done',
                        default => 'open',
                    };
                    $statusStyles = [
                        'open' => [
                            'dot' => 'bg-[#f4b764]',
                            'indicator' => 'border-l-[#f3cc8b]',
                            'chip' => 'bg-[#FFF7E8] text-[#9A6200] border border-[#F3CF8A] shadow-[0_8px_18px_rgba(243,207,138,0.25)]',
                        ],
                        'in_progress' => [
                            'dot' => 'bg-[#7abaf5]',
                            'indicator' => 'border-l-[#9cccfb]',
                            'chip' => 'bg-[#E7F2FF] text-[#1F4B7A] border border-[#BBD8F6] shadow-[0_8px_18px_rgba(155,198,246,0.25)]',
                        ],
                        'done' => [
                            'dot' => 'bg-[#4fd18b]',
                            'indicator' => 'border-l-[#8fe0b2]',
                            'chip' => 'bg-[#E8F8EF] text-[#0F6D3F] border border-[#BFE8CF] shadow-[0_8px_18px_rgba(79,209,139,0.22)]',
                        ],
                    ];
                    $statusStyle = $statusStyles[$normalizedStatus] ?? [
                        'dot' => 'bg-slate-300',
                        'indicator' => 'border-l-slate-200',
                        'chip' => 'bg-slate-100 text-slate-600 border border-slate-200',
                    ];
                    $displayStatus = [
                        'open' => 'OPEN',
                        'in_progress' => 'ON TRACK',
                        'done' => 'TUNTAS',
                    ][$normalizedStatus] ?? strtoupper(str_replace('_', ' ', $ticket->status));
                @endphp
                <article
                    class="group select-text rounded-lg border border-[#E3ECE8] {{ $statusStyle['indicator'] }} border-l-4 bg-gradient-to-br from-white via-[#F8FBFA] to-[#EAF3EF] px-4 py-3 space-y-1.5 shadow-sm shadow-emerald-900/5 transition hover:-translate-y-0.5 hover:shadow-md hover:border-[#b7e3cb] text-left"
                    data-ticket-item
                    data-ticket-title="{{ $ticket->title }}"
                    data-ticket-status="{{ $ticket->status }}"
                    data-ticket-user="{{ optional($ticket->user)->name }}"
                    data-created="{{ $ticket->created_at->timestamp }}"
                >
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div class="flex flex-col gap-0.5 min-w-0">
                            <a
                                href="{{ route('tickets.show', $ticket) }}"
                                class="text-[13px] font-semibold text-gray-900 transition group-hover:text-emerald-700 leading-snug"
                            >
                                <span class="inline-flex items-center gap-2 truncate">
                                    <span class="h-2 w-2 rounded-full {{ $statusStyle['dot'] }} shadow-inner shadow-white"></span>
                                    <span class="truncate">#{{ $ticket->id }} &bull; {{ $ticket->title }}</span>
                                </span>
                            </a>
                            <p class="text-[9px] font-semibold uppercase tracking-[0.2em] text-gray-400 truncate">
                                {{ optional($ticket->category)->name ?? 'Tanpa kategori' }} • {{ optional($ticket->department)->name ?? 'Tanpa departemen' }}
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 text-[10.5px] text-gray-600">
                        <span class="inline-flex items-center gap-1.5">
                            <svg class="h-3.5 w-3.5 text-[#53B77A]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M8 2v3" />
                                <path d="M16 2v3" />
                                <rect x="3" y="5" width  ="18" height="16" rx="2" />
                                <path d="M3 10h18" />
                            </svg>
                            {{ $ticket->created_at->format('d M Y H:i') }}
                        </span>
                        <span class="inline-flex items-center gap-1.5 truncate">
                            <svg class="h-3.5 w-3.5 text-[#53B77A]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="8" r="4" />
                                <path d="M6 20a6 6 0 0 1 12 0" />
                            </svg>
                            <span class="truncate">{{ optional($ticket->user)->name ?? 'User eksternal' }}</span>
                        </span>
                        <span class="inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-[9px] font-semibold uppercase tracking-[0.06em] {{ $statusStyle['chip'] }}">
                            <svg class="h-3 w-3" viewBox="0 0 20 20" fill="none" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" stroke="currentColor">
                                @if ($normalizedStatus === 'open')
                                    <path d="M10 4v6l3 3" />
                                @elseif ($normalizedStatus === 'in_progress')
                                    <circle cx="10" cy="10" r="6" />
                                    <path d="M10 10 13 7" />
                                @else
                                    <path d="M7 10l2 2 4-4" />
                                @endif
                            </svg>
                            {{ $displayStatus }}
                        </span>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 justify-between text-[10px] pt-1">
                        @if ($ticket->attachments_count > 0)
                            <span class="inline-flex items-center gap-1 rounded-full border border-gray-200 bg-gray-50 px-2 py-0.5 font-semibold text-gray-600">
                                {{ $ticket->attachments_count }} lampiran
                            </span>
                        @endif
                        <div class="flex flex-wrap items-center gap-1.5 ml-auto">
                            <a href="{{ route('tickets.show', $ticket) }}" class="inline-flex items-center gap-1 rounded-full border border-[#CFEADF] px-2 py-0.5 font-semibold text-[#118A58] hover:bg-[#e5f8ef] transition">
                                <svg class="h-3 w-3" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M5 10h10M5 10l4-4M5 10l4 4" /></svg>
                                Detail
                            </a>
                            <button type="button" class="inline-flex items-center gap-1 rounded-full border border-slate-200 px-2 py-0.5 font-semibold text-slate-600 hover:border-[#CFEADF] hover:bg-[#F6F9F8] transition" data-copy-id="{{ $ticket->id }}">
                                <svg class="h-3 w-3" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="7" y="7" width="10" height="10" rx="2" /><path d="M5 13H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h7a2 2 0 0 1 2 2v1" /></svg>
                                Salin ID
                            </button>
                        </div>
                    </div>
                </article>
            @endforeach
        @endif
    </div>

    <div class="space-y-3 animate-pulse" data-ticket-skeleton>
        @for ($i = 0; $i < 2; $i++)
            <div class="rounded-2xl border border-slate-200/70 bg-slate-50 px-5 py-4 space-y-3 shadow-inner shadow-white/50">
                <div class="flex items-center justify-between">
                    <span class="h-4 w-32 rounded bg-slate-200"></span>
                    <span class="h-6 w-16 rounded-full bg-slate-200"></span>
                </div>
                <div class="h-3 w-44 rounded bg-slate-200"></div>
                <div class="flex gap-2">
                    <span class="h-4 w-20 rounded bg-slate-200"></span>
                    <span class="h-4 w-24 rounded bg-slate-200"></span>
                </div>
            </div>
        @endfor
    </div>
</div>
