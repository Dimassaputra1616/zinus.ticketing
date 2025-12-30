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
                    <th class="px-3 py-2 text-left">User</th>
                    <th class="px-3 py-2 text-left">Device</th>
                    <th class="px-3 py-2 text-left">Kode Asset</th>
                    <th class="px-3 py-2 text-left">Tgl Pinjam</th>
                    <th class="px-3 py-2 text-left">Tgl Kembali</th>
                    <th class="px-3 py-2 text-left">Status</th>
                    <th class="px-3 py-2 text-left">Keterangan</th>
                    <th class="px-3 py-2 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white text-sm text-slate-800">
                @forelse ($logs as $log)
                    <tr class="transition-all hover:bg-emerald-50/60 hover:shadow-sm hover:-translate-y-[1px] loan-row" data-loan-id="{{ $log->id }}">
                        <td class="px-3 py-3">
                            <div class="font-semibold text-slate-900">{{ $log->user->name ?? 'User' }}</div>
                            <div class="text-xs text-slate-500">{{ $log->user->email ?? '-' }}</div>
                    </td>
                    <td class="px-3 py-3">
                        @php
                            $assetName = $log->asset?->name ?? $log->device?->name ?? '-';
                            $assetCode = $log->asset?->asset_code ?? $log->device?->code;
                        @endphp
                        <div class="font-semibold">{{ $assetName }}</div>
                        @if ($assetCode)
                            <div class="text-xs text-slate-500">{{ $assetCode }}</div>
                        @endif
                    </td>
                    <td class="px-3 py-3 text-[13px] text-slate-700">
                        {{ $log->asset_code ?? $log->asset?->asset_code ?? '—' }}
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
                    <td class="px-3 py-3 text-right">
                            <div class="flex flex-wrap items-center justify-end gap-2">
                                @foreach ([\App\Models\BorrowLog::STATUS_APPROVED => 'ACC', \App\Models\BorrowLog::STATUS_REJECTED => 'Tolak', \App\Models\BorrowLog::STATUS_RETURNED => 'Kembali'] as $statusKey => $label)
                                    <form method="POST" action="{{ route('loans.updateStatus', $log) }}" data-confirm="{{ $statusKey === \App\Models\BorrowLog::STATUS_REJECTED ? 'Tolak pengajuan ini?' : ($statusKey === \App\Models\BorrowLog::STATUS_RETURNED ? 'Tandai sudah kembali?' : '') }}" class="flex items-center gap-2 loan-action-form">
                                        @csrf
                                        <input type="hidden" name="status" value="{{ $statusKey }}">
                                        @if ($statusKey === \App\Models\BorrowLog::STATUS_APPROVED)
                                            <input
                                                type="text"
                                                name="asset_code"
                                                value="{{ $log->asset_code ?? $log->asset?->asset_code ?? $log->device?->code }}"
                                                placeholder="Kode asset"
                                                class="w-32 rounded-lg border border-slate-200 px-2 py-1 text-xs focus:border-emerald-400 focus:ring-1 focus:ring-emerald-100"
                                                required
                                            >
                                        @endif
                                        @php
                                            [$btnIcon, $tooltip] = match($statusKey) {
                                                \App\Models\BorrowLog::STATUS_APPROVED => ['<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m20 6-11 11-5-5" /></svg>', 'Setujui peminjaman'],
                                                \App\Models\BorrowLog::STATUS_REJECTED => ['<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18" /><path d="m6 6 12 12" /></svg>', 'Tolak pengajuan'],
                                                \App\Models\BorrowLog::STATUS_RETURNED => ['<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 11-4 4 4 4" /><path d="M5 15h11a4 4 0 0 0 0-8H7" /></svg>', 'Tandai sudah kembali'],
                                                default => ['', '']
                                            };
                                        @endphp
                                        <div class="relative group">
                                            <x-ui.button type="submit" size="sm" variant="{{ $statusKey === 'rejected' ? 'ghost' : 'secondary' }}" class="text-xs flex items-center gap-1 transition duration-150 hover:-translate-y-[1px]" title="{{ $tooltip }}" aria-label="{{ $tooltip }}">
                                                <span class="loan-btn-label">{!! $btnIcon !!} {{ $label }}</span>
                                                <span class="loan-btn-spinner hidden h-4 w-4 border-2 border-emerald-500 border-t-transparent rounded-full animate-spin"></span>
                                            </x-ui.button>
                                            <span class="pointer-events-none absolute -top-9 left-1/2 -translate-x-1/2 rounded-md bg-slate-900 px-2 py-1 text-[11px] font-semibold text-white opacity-0 shadow transition group-hover:opacity-100">{{ $tooltip }}</span>
                                        </div>
                                    </form>
                                @endforeach
                            </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="px-4 py-6 text-center text-sm text-slate-500">Belum ada log peminjaman.</td>
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
                    <p class="text-xs font-semibold text-slate-500">User</p>
                    <p class="text-sm font-semibold text-slate-900">{{ $log->user->name ?? 'User' }}</p>
                    <p class="text-xs text-slate-500">{{ $log->user->email ?? '-' }}</p>
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
                    <p class="font-semibold text-slate-500">Device</p>
                    @php
                        $assetName = $log->asset?->name ?? $log->device?->name ?? '-';
                        $assetCode = $log->asset?->asset_code ?? $log->device?->code;
                    @endphp
                    <p class="text-slate-800">{{ $assetName }}</p>
                    @if ($assetCode)
                        <p class="text-[11px] text-slate-500">{{ $assetCode }}</p>
                    @endif
                </div>
                <div>
                    <p class="font-semibold text-slate-500">Kode Asset</p>
                    <p class="text-slate-800">{{ $log->asset_code ?: '—' }}</p>
                </div>
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
            <div class="mt-3 flex flex-wrap items-center gap-2">
                @foreach ([\App\Models\BorrowLog::STATUS_APPROVED => 'ACC', \App\Models\BorrowLog::STATUS_REJECTED => 'Tolak', \App\Models\BorrowLog::STATUS_RETURNED => 'Kembali'] as $statusKey => $label)
                    <form method="POST" action="{{ route('loans.updateStatus', $log) }}" data-confirm="{{ $statusKey === \App\Models\BorrowLog::STATUS_REJECTED ? 'Tolak pengajuan ini?' : ($statusKey === \App\Models\BorrowLog::STATUS_RETURNED ? 'Tandai sudah kembali?' : '') }}" class="flex items-center gap-2 loan-action-form">
                        @csrf
                        <input type="hidden" name="status" value="{{ $statusKey }}">
                        @if ($statusKey === \App\Models\BorrowLog::STATUS_APPROVED)
                            <input
                                type="text"
                                name="asset_code"
                                value="{{ $log->asset_code ?? $log->asset?->asset_code ?? $log->device?->code }}"
                                placeholder="Kode asset"
                                class="w-28 rounded-lg border border-slate-200 px-2 py-1 text-xs focus:border-emerald-400 focus:ring-1 focus:ring-emerald-100"
                                required
                            >
                        @endif
                        @php
                            [$btnIcon, $tooltip] = match($statusKey) {
                                \App\Models\BorrowLog::STATUS_APPROVED => ['<svg class=\"h-4 w-4\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\"><path d=\"m20 6-11 11-5-5\" /></svg>', 'Setujui peminjaman'],
                                \App\Models\BorrowLog::STATUS_REJECTED => ['<svg class=\"h-4 w-4\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\"><path d=\"M18 6 6 18\" /><path d=\"m6 6 12 12\" /></svg>', 'Tolak pengajuan'],
                                \App\Models\BorrowLog::STATUS_RETURNED => ['<svg class=\"h-4 w-4\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\"><path d=\"m9 11-4 4 4 4\" /><path d=\"M5 15h11a4 4 0 0 0 0-8H7\" /></svg>', 'Tandai sudah kembali'],
                                default => ['', '']
                            };
                        @endphp
                        <x-ui.button type="submit" size="sm" variant="{{ $statusKey === 'rejected' ? 'ghost' : 'secondary' }}" class="text-xs flex items-center gap-1" title="{{ $tooltip }}" aria-label="{{ $tooltip }}">
                            <span class="loan-btn-label">{!! $btnIcon !!} {{ $label }}</span>
                            <span class="loan-btn-spinner hidden h-4 w-4 border-2 border-emerald-500 border-t-transparent rounded-full animate-spin"></span>
                        </x-ui.button>
                    </form>
                @endforeach
            </div>
        </article>
    @empty
        <div class="px-1 py-4 text-center text-sm text-slate-500">Belum ada log peminjaman.</div>
    @endforelse
    <div class="px-1">
        {{ $logs->links() }}
    </div>
</div>
