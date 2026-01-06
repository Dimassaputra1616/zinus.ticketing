@php
    $statusBadge = $statusBadge ?? [
        'waiting' => 'bg-amber-100 text-amber-700 border border-amber-200',
        'approved' => 'bg-emerald-100 text-emerald-700 border border-emerald-200',
        'returned' => 'bg-slate-100 text-slate-600 border border-slate-200',
        'rejected' => 'bg-rose-100 text-rose-700 border border-rose-200',
    ];
@endphp

<div class="hidden md:block pt-2" data-loan-table>
        <p class="mb-2 text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">
            Mode approval • prioritaskan status Waiting
        </p>
        <table class="w-full table-auto divide-y divide-slate-200">
            <thead class="bg-slate-50/90 backdrop-blur text-2xs uppercase tracking-[0.2em] text-slate-800 font-semibold sticky top-0 z-10">
                <tr>
                    <th class="px-3 py-2 text-left">User</th>
                    <th class="px-3 py-2 text-left">Departemen</th>
                    <th class="px-3 py-2 text-left">Asset</th>
                    <th class="px-3 py-2 text-left">Serial Number</th>
                    <th class="px-3 py-2 text-left whitespace-nowrap">Tgl Pinjam</th>
                    <th class="px-3 py-2 text-left whitespace-nowrap">Tgl Kembali</th>
                    <th class="px-3 py-2 text-left whitespace-nowrap">Status</th>
                    <th class="px-3 py-2 text-left">Keterangan</th>
                    <th class="px-3 py-2 text-right whitespace-nowrap">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white text-sm text-slate-800">
                @forelse ($logs as $log)
                    <tr class="transition-all hover:bg-emerald-50/60 hover:shadow-sm hover:-translate-y-[1px] loan-row {{ $log->status === \App\Models\BorrowLog::STATUS_WAITING ? 'bg-amber-50/40' : '' }}" data-loan-id="{{ $log->id }}">
                    <td class="px-3 py-3">
                        <div class="font-semibold text-slate-900">{{ $log->user->name ?? 'User' }}</div>
                        <div class="text-xs text-slate-500">{{ $log->user->email ?? '-' }}</div>
                    </td>
                    <td class="px-3 py-3 text-[13px] text-slate-700">
                        @if ($log->department?->name)
                            {{ $log->department->name }}
                        @elseif ($log->user?->department?->name)
                            {{ $log->user->department->name }}
                        @else
                            <span class="text-slate-400">Tidak diset</span>
                        @endif
                    </td>
                    <td class="px-3 py-3 max-w-[220px] break-words">
                        @php
                            $assetName = $log->asset?->name ?? $log->device?->name ?? '-';
                            $assetSerial = $log->asset?->serial_number
                                ?? $log->device?->serial_number
                                ?? $log->asset_code
                                ?? $log->asset?->asset_code
                                ?? $log->device?->code;
                        @endphp
                        <div class="font-semibold">{{ $assetName }}</div>
                        @if ($assetSerial)
                            <div class="text-xs text-slate-500">{{ $assetSerial }}</div>
                        @endif
                    </td>
                    <td class="px-3 py-3 text-[13px] text-slate-700 whitespace-nowrap">
                        {{ $assetSerial ?? '—' }}
                    </td>
                    <td class="px-3 py-3 text-[13px] text-slate-700 whitespace-nowrap">{{ optional($log->start_date)->format('d M Y') }}</td>
                    <td class="px-3 py-3 text-[13px] text-slate-700 whitespace-nowrap">{{ optional($log->end_date)->format('d M Y') }}</td>
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
                            'returned' => 'bg-slate-100 text-slate-600 border border-slate-200',
                            'rejected' => 'bg-rose-100 text-rose-700 border border-rose-200',
                            default => 'bg-slate-100 text-slate-700 border border-slate-200'
                        } }}">
                            {!! $statusIcon !!}
                            {{ ucfirst($statuses[$log->status] ?? $log->status) }}
                        </span>
                        @if ($log->status === \App\Models\BorrowLog::STATUS_RETURNED && $log->returned_at)
                            <div class="mt-1 text-[11px] text-slate-500">
                                Dikembalikan pada: {{ $log->returned_at->format('d M Y • H:i') }} WIB
                            </div>
                        @endif
                    </td>
                    <td class="px-3 py-3 text-[13px] text-slate-700 max-w-[220px] break-words">
                        {{ $log->reason ? Str::limit($log->reason, 80) : '—' }}
                    </td>
                    <td class="px-3 py-3 text-right">
                            @php
                                $availableActions = match($log->status) {
                                    \App\Models\BorrowLog::STATUS_WAITING => [
                                        \App\Models\BorrowLog::STATUS_APPROVED => 'Terima',
                                        \App\Models\BorrowLog::STATUS_REJECTED => 'Tolak',
                                    ],
                                    \App\Models\BorrowLog::STATUS_APPROVED => [
                                        \App\Models\BorrowLog::STATUS_RETURNED => 'Kembali',
                                    ],
                                    default => [],
                                };
                            @endphp
                            @if ($availableActions)
                                <div class="flex flex-wrap items-center justify-end gap-2">
                                    @foreach ($availableActions as $statusKey => $label)
                                        <form method="POST" action="{{ route('loans.updateStatus', $log) }}" data-confirm="{{ $statusKey === \App\Models\BorrowLog::STATUS_REJECTED ? 'Tolak pengajuan ini?' : ($statusKey === \App\Models\BorrowLog::STATUS_RETURNED ? 'Tandai sudah kembali?' : '') }}" class="loan-action-form">
                                            @csrf
                                            <input type="hidden" name="status" value="{{ $statusKey }}">
                                            @php
                                                [$btnIcon, $tooltip, $variant] = match($statusKey) {
                                                    \App\Models\BorrowLog::STATUS_APPROVED => ['<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m20 6-11 11-5-5" /></svg>', 'Setujui peminjaman', 'secondary'],
                                                    \App\Models\BorrowLog::STATUS_REJECTED => ['<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18" /><path d="m6 6 12 12" /></svg>', 'Tolak pengajuan', 'ghost'],
                                                    \App\Models\BorrowLog::STATUS_RETURNED => ['<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 11-4 4 4 4" /><path d="M5 15h11a4 4 0 0 0 0-8H7" /></svg>', 'Tandai sudah kembali', 'secondary'],
                                                    default => ['', '', 'secondary']
                                                };
                                            @endphp
                                            <x-ui.button type="submit" size="sm" variant="{{ $variant }}" class="text-xs inline-flex items-center gap-1" title="{{ $tooltip }}" aria-label="{{ $tooltip }}">
                                                {!! $btnIcon !!}
                                                <span>{{ $label }}</span>
                                                <span class="loan-btn-spinner hidden h-4 w-4 border-2 border-emerald-500 border-t-transparent rounded-full animate-spin"></span>
                                            </x-ui.button>
                                        </form>
                                    @endforeach
                                </div>
                            @else
                                <span class="text-xs text-slate-400">—</span>
                            @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="px-4 py-6 text-center text-sm text-slate-500">Belum ada pengajuan peminjaman masuk.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    @if ($logs->count())
        <div class="flex flex-wrap items-center justify-between px-4 py-3 text-sm text-slate-600">
            <div>
                Menampilkan {{ $logs->firstItem() }}–{{ $logs->lastItem() }} dari {{ $logs->total() }} data
            </div>
            <div>
                {{ $logs->links() }}
            </div>
        </div>
    @endif
</div>

<div class="md:hidden space-y-3">
    <p class="px-1 text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-500">
        Mode approval • prioritaskan status Waiting
    </p>
    @forelse ($logs as $log)
        <article class="rounded-2xl border border-slate-100 bg-white p-4 shadow-sm transition hover:-translate-y-[1px] hover:shadow-md loan-row {{ $log->status === \App\Models\BorrowLog::STATUS_WAITING ? 'ring-1 ring-amber-200' : '' }}" data-loan-id="{{ $log->id }}">
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
                    'returned' => 'bg-slate-100 text-slate-600 border border-slate-200',
                    'rejected' => 'bg-rose-100 text-rose-700 border border-rose-200',
                    default => 'bg-slate-100 text-slate-700 border border-slate-200'
                } }}">
                    {!! $statusIcon !!}
                    {{ ucfirst($statuses[$log->status] ?? $log->status) }}
                </span>
            </div>
            @if ($log->status === \App\Models\BorrowLog::STATUS_RETURNED && $log->returned_at)
                <div class="mt-2 text-[11px] text-slate-500">
                    Dikembalikan pada: {{ $log->returned_at->format('d M Y • H:i') }} WIB
                </div>
            @endif
            <div class="mt-3 grid grid-cols-2 gap-2 text-xs text-slate-600">
                <div>
                    <p class="font-semibold text-slate-500">Asset</p>
                    @php
                        $assetName = $log->asset?->name ?? $log->device?->name ?? '-';
                        $assetSerial = $log->asset?->serial_number
                            ?? $log->device?->serial_number
                            ?? $log->asset_code
                            ?? $log->asset?->asset_code
                            ?? $log->device?->code;
                    @endphp
                    <p class="text-slate-800">{{ $assetName }}</p>
                    @if ($assetSerial)
                        <p class="text-[11px] text-slate-500">{{ $assetSerial }}</p>
                    @endif
                </div>
                <div>
                    <p class="font-semibold text-slate-500">Serial Number</p>
                    <p class="text-slate-800">{{ $assetSerial ?? '—' }}</p>
                </div>
                <div>
                    <p class="font-semibold text-slate-500">Departemen</p>
                    @if ($log->department?->name)
                        <p class="text-slate-800">{{ $log->department->name }}</p>
                    @elseif ($log->user?->department?->name)
                        <p class="text-slate-800">{{ $log->user->department->name }}</p>
                    @else
                        <p class="text-slate-400">Tidak diset</p>
                    @endif
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
                @php
                    $availableActions = match($log->status) {
                        \App\Models\BorrowLog::STATUS_WAITING => [
                            \App\Models\BorrowLog::STATUS_APPROVED => 'Terima',
                            \App\Models\BorrowLog::STATUS_REJECTED => 'Tolak',
                        ],
                        \App\Models\BorrowLog::STATUS_APPROVED => [
                            \App\Models\BorrowLog::STATUS_RETURNED => 'Kembali',
                        ],
                        default => [],
                    };
                @endphp
                @if ($availableActions)
                    @foreach ($availableActions as $statusKey => $label)
                        <form method="POST" action="{{ route('loans.updateStatus', $log) }}" data-confirm="{{ $statusKey === \App\Models\BorrowLog::STATUS_REJECTED ? 'Tolak pengajuan ini?' : ($statusKey === \App\Models\BorrowLog::STATUS_RETURNED ? 'Tandai sudah kembali?' : '') }}" class="loan-action-form">
                            @csrf
                            <input type="hidden" name="status" value="{{ $statusKey }}">
                            @php
                                [$btnIcon, $tooltip, $variant] = match($statusKey) {
                                    \App\Models\BorrowLog::STATUS_APPROVED => ['<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m20 6-11 11-5-5" /></svg>', 'Setujui peminjaman', 'secondary'],
                                    \App\Models\BorrowLog::STATUS_REJECTED => ['<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18" /><path d="m6 6 12 12" /></svg>', 'Tolak pengajuan', 'ghost'],
                                    \App\Models\BorrowLog::STATUS_RETURNED => ['<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 11-4 4 4 4" /><path d="M5 15h11a4 4 0 0 0 0-8H7" /></svg>', 'Tandai sudah kembali', 'secondary'],
                                    default => ['', '', 'secondary']
                                };
                            @endphp
                            <x-ui.button type="submit" size="sm" variant="{{ $variant }}" class="text-xs inline-flex items-center gap-1" title="{{ $tooltip }}" aria-label="{{ $tooltip }}">
                                {!! $btnIcon !!}
                                <span>{{ $label }}</span>
                                <span class="loan-btn-spinner hidden h-4 w-4 border-2 border-emerald-500 border-t-transparent rounded-full animate-spin"></span>
                            </x-ui.button>
                        </form>
                    @endforeach
                @else
                    <span class="text-xs text-slate-400">—</span>
                @endif
            </div>
        </article>
    @empty
        <div class="px-1 py-4 text-center text-sm text-slate-500">Belum ada pengajuan peminjaman masuk.</div>
    @endforelse
    @if ($logs->count())
        <div class="px-1">
            {{ $logs->links() }}
        </div>
    @endif
</div>
