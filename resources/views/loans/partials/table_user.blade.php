@php
    $statusBadge = $statusBadge ?? [
        'waiting' => 'bg-amber-100 text-amber-700 border border-amber-200',
        'approved' => 'bg-emerald-100 text-emerald-700 border border-emerald-200',
        'returned' => 'bg-sky-100 text-sky-700 border border-sky-200',
        'rejected' => 'bg-rose-100 text-rose-700 border border-rose-200',
    ];
@endphp

<div class="hidden md:block pt-2" data-loan-table>
    <div class="overflow-x-auto">
    <table class="w-full table-auto divide-y divide-slate-200">
        <thead class="bg-slate-50/90 backdrop-blur text-2xs uppercase tracking-[0.2em] text-slate-800 font-semibold sticky top-0 z-10">
            <tr>
                <th class="px-3 py-2 text-left">Device</th>
                <th class="px-3 py-2 text-left">Tgl Pinjam</th>
                <th class="px-3 py-2 text-left">Tgl Kembali</th>
                <th class="px-3 py-2 text-left">Status</th>
                <th class="px-3 py-2 text-left">Keterangan</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 bg-white text-sm text-slate-800">
            @forelse ($logs as $log)
                <tr class="transition-all hover:bg-emerald-50/60 hover:shadow-sm hover:-translate-y-[1px] loan-row" data-loan-id="{{ $log->id }}">
                    <td class="px-3 py-3">
                        <div class="font-semibold">{{ $log->device->name ?? '-' }}</div>
                        @if ($log->device?->code)
                            <div class="text-xs text-slate-500">{{ $log->device->code }}</div>
                        @endif
                    </td>
                    <td class="px-3 py-3 text-[13px] text-slate-700">{{ optional($log->start_date)->format('d M Y') }}</td>
                    <td class="px-3 py-3 text-[13px] text-slate-700">{{ optional($log->end_date)->format('d M Y') }}</td>
                    <td class="px-3 py-3">
                        @php
                            $statusIcon = [
                                'waiting' => '<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 6v6l3 3" /><circle cx="12" cy="12" r="9" /></svg>',
                                'approved' => '<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m20 6-11 11-5-5" /></svg>',
                                'returned' => '<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 11-4 4 4 4" /><path d="M5 15h11a4 4 0 0 0 0-8H7" /></svg>',
                                'rejected' => '<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18" /><path d="m6 6 12 12" /></svg>',
                            ][$log->status] ?? '';
                        @endphp
                        <span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] {{ match($log->status) {
                            'approved' => 'bg-emerald-100 text-emerald-700 border border-emerald-200',
                            'waiting' => 'bg-amber-100 text-amber-700 border border-amber-200',
                            'returned' => 'bg-sky-100 text-sky-700 border border-sky-200',
                            'rejected' => 'bg-rose-100 text-rose-700 border border-rose-200',
                            default => 'bg-slate-100 text-slate-700 border border-slate-200'
                        } }}">
                            {!! $statusIcon !!}
                            {{ ucfirst($statuses[$log->status] ?? $log->status) }}
                        </span>
                    </td>
                    <td class="px-3 py-3 text-[13px] text-slate-700">
                        {{ $log->reason ? Str::limit($log->reason, 80) : '—' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-6 text-center text-sm text-slate-500">Belum ada log peminjaman.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    </div>
    <div class="flex flex-wrap items-center justify-between px-4 py-3 text-sm text-slate-600">
        <div>
            @if ($logs->count())
                Menampilkan {{ $logs->firstItem() }}–{{ $logs->lastItem() }} dari {{ $logs->total() }} data
            @else
                Tidak ada data
            @endif
        </div>
        <div>
            {{ $logs->links() }}
        </div>
    </div>
</div>

<div class="md:hidden space-y-3">
    @forelse ($logs as $log)
        <article class="rounded-2xl border border-slate-100 bg-white p-4 shadow-sm transition hover:-translate-y-[1px] hover:shadow-md loan-row" data-loan-id="{{ $log->id }}">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold text-slate-500">Device</p>
                    <p class="text-sm font-semibold text-slate-900">{{ $log->device->name ?? '-' }}</p>
                    @if ($log->device?->code)
                        <p class="text-xs text-slate-500">{{ $log->device->code }}</p>
                    @endif
                </div>
                @php
                    $statusIcon = [
                        'waiting' => '<svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 6v6l3 3" /><circle cx="12" cy="12" r="9" /></svg>',
                        'approved' => '<svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m20 6-11 11-5-5" /></svg>',
                        'returned' => '<svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 11-4 4 4 4" /><path d="M5 15h11a4 4 0 0 0 0-8H7" /></svg>',
                        'rejected' => '<svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18" /><path d="m6 6 12 12" /></svg>',
                    ][$log->status] ?? '';
                @endphp
                <span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.2em] {{ match($log->status) {
                    'approved' => 'bg-emerald-100 text-emerald-700 border border-emerald-200',
                    'waiting' => 'bg-amber-100 text-amber-700 border border-amber-200',
                    'returned' => 'bg-sky-100 text-sky-700 border border-sky-200',
                    'rejected' => 'bg-rose-100 text-rose-700 border border-rose-200',
                    default => 'bg-slate-100 text-slate-700 border border-slate-200'
                } }}">
                    {!! $statusIcon !!}
                    {{ ucfirst($statuses[$log->status] ?? $log->status) }}
                </span>
            </div>
            <div class="mt-3 grid grid-cols-2 gap-2 text-xs text-slate-600">
                <div>
                    <p class="font-semibold text-slate-500">Tgl Pinjam</p>
                    <p class="text-slate-800">{{ optional($log->start_date)->format('d M Y') }}</p>
                </div>
                <div>
                    <p class="font-semibold text-slate-500">Tgl Kembali</p>
                    <p class="text-slate-800">{{ optional($log->end_date)->format('d M Y') }}</p>
                </div>
            </div>
            <div class="mt-2 text-xs text-slate-600">
                <p class="font-semibold text-slate-500">Keterangan</p>
                <p class="text-slate-800">{{ $log->reason ?: '—' }}</p>
            </div>
        </article>
    @empty
        <div class="px-1 py-4 text-center text-sm text-slate-500">Belum ada log peminjaman.</div>
    @endforelse
    <div class="px-1">
        {{ $logs->links() }}
    </div>
</div>
