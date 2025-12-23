@php
    $statusMeta = [
        \App\Models\Asset::STATUS_IN_USE => ['label' => 'Active', 'class' => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100'],
        \App\Models\Asset::STATUS_MAINTENANCE => ['label' => 'In Repair', 'class' => 'bg-amber-50 text-amber-700 ring-1 ring-amber-100'],
        \App\Models\Asset::STATUS_AVAILABLE => ['label' => 'Spare', 'class' => 'bg-sky-50 text-sky-700 ring-1 ring-sky-100'],
        \App\Models\Asset::STATUS_BROKEN => ['label' => 'Retired', 'class' => 'bg-rose-50 text-rose-700 ring-1 ring-rose-100'],
    ];
    $statusInfo = $statusMeta[$asset->status] ?? ['label' => ucfirst(str_replace('_', ' ', $asset->status)), 'class' => 'bg-slate-100 text-slate-600 ring-1 ring-slate-200'];
    $createdByName = data_get($asset, 'createdBy.name') ?? ($asset->created_by ?? 'Belum diisi');
    $updatedByName = data_get($asset, 'updatedBy.name') ?? ($asset->updated_by ?? 'Belum diisi');

    $compactValue = function ($value) {
        return filled($value) ? $value : 'Belum diisi';
    };

    $systemValue = function ($value) {
        return filled($value) ? $value : 'Belum diisi';
    };

    $syncSourceLabel = $asset->sync_source ? Str::of($asset->sync_source)->replace('_', ' ')->title() : 'Manual';
@endphp

<x-app-layout>
    @if (session('success'))
        <div
            x-data="{ open: true }"
            x-init="setTimeout(() => open = false, 2500)"
            x-show="open"
            x-transition
            class="fixed right-4 top-4 z-50 flex items-start gap-3 rounded-2xl bg-emerald-600 px-4 py-3 text-white shadow-xl shadow-emerald-500/30"
        >
            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-white/15">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 6 9 17l-5-5" />
                </svg>
            </div>
            <div>
                <p class="text-sm font-semibold">Berhasil</p>
                <p class="text-xs text-emerald-50">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    <div class="min-h-screen bg-gradient-to-b from-slate-50 via-slate-50 to-slate-100">
        <div class="mx-auto w-full max-w-6xl px-4 py-6 md:px-6 md:py-8 lg:px-8 space-y-6">
            {{-- Hero Summary --}}
            <div class="rounded-3xl bg-white/90 border border-emerald-50 shadow-[0_24px_60px_rgba(15,23,42,.12)] backdrop-blur-xl p-6 md:p-7 flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
                <div class="space-y-3">
                    <div class="flex items-center gap-3">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700 shadow-inner shadow-emerald-200/60">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="6" width="18" height="12" rx="2" />
                                <path d="M8 6v-2h8v2" />
                                <path d="M12 18v3" />
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-3xl font-semibold text-slate-900">{{ $asset->asset_code }}</h1>
                            <p class="mt-1 text-sm text-slate-600">
                                {{ $compactValue($asset->category) }}
                                &bull; {{ $compactValue(trim(($asset->brand ?? '') . ' ' . ($asset->model ?? ''))) }}
                                @if ($asset->factory)
                                    &bull; {{ $asset->factory }}
                                @endif
                                @if ($asset->department?->name)
                                    &bull; Dept {{ $asset->department->name }}
                                @endif
                            </p>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.25em] shadow-sm {{ $statusInfo['class'] }}">
                            <span class="h-2 w-2 rounded-full bg-current"></span>
                            {{ $statusInfo['label'] }}
                        </span>
                        @if ($asset->updated_at)
                            <span class="text-xs text-slate-500">Last synced: {{ $asset->updated_at->format('Y-m-d H:i') }}</span>
                        @endif
                        @if ($asset->location)
                            <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-[11px] font-semibold text-slate-700 ring-1 ring-slate-200 shadow-sm">Location: {{ $asset->location }}</span>
                        @endif
                        @if ($asset->user?->name)
                            <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-[11px] font-semibold text-slate-700 ring-1 ring-slate-200 shadow-sm">Assigned: {{ $asset->user->name }}</span>
                        @endif
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <a href="{{ route('assets.index') }}" class="inline-flex items-center gap-2 rounded-full border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:border-emerald-200 hover:text-emerald-700">
                            &larr; Back
                        </a>
                        <a href="{{ route('assets.edit', $asset) }}" class="inline-flex items-center gap-2 rounded-full bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-emerald-400/30 hover:bg-emerald-700">
                            Edit Asset
                        </a>
                    </div>
                </div>
                <div class="flex flex-col items-start gap-3 md:items-end">
                    <p class="text-sm text-slate-600">Last updated {{ optional($asset->updated_at)->format('Y-m-d H:i') ?? 'Belum diisi' }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-1 lg:gap-7">
                {{-- Hardware first on mobile --}}
                <div class="rounded-3xl border border-emerald-100 bg-gradient-to-br from-white via-emerald-50 to-white p-5 text-slate-900 shadow-sm shadow-emerald-100/60 md:p-6">
                    <div class="flex items-center justify-between">
                        <h3 class="text-base font-semibold text-slate-900">Hardware &amp; OS</h3>
                        <span class="rounded-full bg-emerald-600 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.3em] text-white shadow-sm">System</span>
                    </div>
                    <div class="mt-4 space-y-3">
                        @php
                            $systemFields = [
                                ['label' => 'CPU', 'value' => $asset->cpu, 'icon' => '<path d="M6 18h12"/><path d="M6 14h12"/><path d="M6 10h12"/><path d="M6 6h12"/>' ],
                                ['label' => 'RAM', 'value' => $asset->ram_gb ? $asset->ram_gb . ' GB' : null, 'icon' => '<rect x="4" y="5" width="16" height="14" rx="2"/><path d="M4 9h16"/>' ],
                                [
                                    'label' => 'Storage',
                                    'value' => $asset->storage_detail
                                        ? ($asset->storage_gb ? "{$asset->storage_detail} (Total: {$asset->storage_gb} GB)" : $asset->storage_detail)
                                        : ($asset->storage_gb ? $asset->storage_gb . ' GB' : null),
                                    'icon' => '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M7 8h10"/><path d="M7 12h10"/><path d="M7 16h4"/>',
                                ],
                                ['label' => 'OS', 'value' => $asset->os_name, 'icon' => '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M7 8h10"/><path d="M7 12h5"/>' ],
                                ['label' => 'IP', 'value' => $asset->ip_address, 'icon' => '<rect x="4" y="5" width="16" height="14" rx="2"/><path d="M9 9h6"/><path d="M9 13h6"/>' , 'mono' => true],
                            ];
                        @endphp
                        @foreach ($systemFields as $sys)
                            <div class="flex items-center justify-between gap-4 rounded-2xl border border-emerald-100 bg-white px-4 py-3 shadow-inner shadow-emerald-50">
                                <div class="flex items-center gap-2">
                                    <svg class="h-4 w-4 text-emerald-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">{!! $sys['icon'] !!}</svg>
                                    <div class="text-[11px] font-semibold uppercase tracking-[0.2em] text-emerald-700">{{ $sys['label'] }}</div>
                                </div>
                                <div class="text-sm font-semibold text-slate-800 text-right {{ ($sys['mono'] ?? false) ? 'font-mono text-slate-700' : '' }}">{{ $systemValue($sys['value']) }}</div>
                            </div>
                        @endforeach
                        <div class="rounded-2xl border border-emerald-100 bg-emerald-100 px-4 py-3 text-sm text-emerald-900">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.25em] text-emerald-800">Last synced from agent</p>
                            @if ($asset->sync_source === 'agent' && $asset->last_synced_at)
                                <p class="mt-1 font-semibold">{{ $asset->last_synced_at->format('d M Y H:i') }}</p>
                                <p class="text-xs text-emerald-800/90">Source: Agent</p>
                            @else
                                <p class="mt-1 font-semibold">Belum pernah sync dari agent</p>
                                <p class="text-xs text-emerald-800/90">Source: {{ $syncSourceLabel }}</p>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Left column: Asset profile --}}
                <div class="rounded-3xl border border-slate-100 bg-white p-5 shadow-sm shadow-slate-200/70 md:p-6">
                    <div class="flex flex-col gap-1">
                        <h3 class="text-base font-semibold text-slate-900">Asset profile</h3>
                        <p class="text-sm text-slate-600">Detail identitas dan kepemilikan asset.</p>
                    </div>
                    @php
                        $fields = [
                            ['label' => 'Asset Code', 'value' => $asset->asset_code],
                            ['label' => 'Category', 'value' => $asset->category],
                            ['label' => 'Brand & Model', 'value' => trim(($asset->brand ?? '') . ' ' . ($asset->model ?? ''))],
                            ['label' => 'Location', 'value' => $asset->location],
                            ['label' => 'Department', 'value' => $asset->department->name ?? null],
                            ['label' => 'Assigned To', 'value' => $asset->user->name ?? null],
                            ['label' => 'Serial Number', 'value' => $asset->serial_number],
                            ['label' => 'Purchase Date', 'value' => optional($asset->purchase_date)->format('Y-m-d')],
                            ['label' => 'Price', 'value' => $asset->price ? 'Rp ' . number_format($asset->price, 2) : null],
                            ['label' => 'Warranty End', 'value' => optional($asset->warranty_expired)->format('Y-m-d')],
                        ];
                    @endphp
                    <dl class="mt-4 grid gap-4 text-sm text-slate-700 md:grid-cols-2">
                        @foreach ($fields as $field)
                            @php $val = $compactValue($field['value']); @endphp
                            <div class="rounded-2xl border border-slate-100 bg-slate-50/70 p-4">
                                <dt class="text-[11px] font-semibold uppercase tracking-[0.25em] text-slate-500">{{ $field['label'] }}</dt>
                                <dd class="mt-1 text-sm font-semibold text-slate-900">
                                    @if ($val === 'Belum diisi')
                                        <span class="text-gray-400 italic text-xs">{{ $val }}</span>
                                    @else
                                        {{ $val }}
                                    @endif
                                </dd>
                            </div>
                        @endforeach

                        <div class="md:col-span-2 rounded-2xl border border-slate-100 bg-slate-50/70 p-4">
                            <dt class="text-[11px] font-semibold uppercase tracking-[0.25em] text-slate-500">Notes</dt>
                            <dd class="mt-2 text-sm text-slate-800">
                                @if ($asset->notes)
                                    <span class="whitespace-pre-line">{{ $asset->notes }}</span>
                                @else
                                    <span class="italic text-gray-400 text-xs">Belum ada catatan untuk aset ini.</span>
                                @endif
                            </dd>
                        </div>
                    </dl>
                </div>

                {{-- Lifecycle --}}
                <div class="rounded-3xl border border-slate-100 bg-white p-5 shadow-sm shadow-slate-200/70 md:p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-semibold text-slate-900">Lifecycle &amp; audit trail</h3>
                            <p class="text-sm text-slate-600">Riwayat pembuatan dan pembaruan.</p>
                        </div>
                    </div>
                    <div class="mt-5 space-y-5">
                        <div class="flex gap-3">
                            <div class="flex flex-col items-center">
                                <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                                <span class="mt-1 h-full w-px bg-slate-200"></span>
                            </div>
                            <div class="flex-1">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.25em] text-slate-500">Created</p>
                                <p class="text-sm font-semibold text-slate-900">{{ optional($asset->created_at)->format('Y-m-d H:i') ?? 'Belum diisi' }}</p>
                                <p class="text-sm text-slate-600">by {{ $createdByName }}</p>
                            </div>
                        </div>
                        <div class="flex gap-3">
                            <div class="flex flex-col items-center">
                                <span class="h-2 w-2 rounded-full bg-sky-500"></span>
                                <span class="mt-1 h-full w-px bg-slate-200"></span>
                            </div>
                            <div class="flex-1">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.25em] text-slate-500">Updated</p>
                                <p class="text-sm font-semibold text-slate-900">{{ optional($asset->updated_at)->format('Y-m-d H:i') ?? 'Belum diisi' }}</p>
                                <p class="text-sm text-slate-600">by {{ $updatedByName }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
