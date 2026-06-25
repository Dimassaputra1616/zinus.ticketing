@php
    $statusMeta = [
        'in_use' => ['label' => __('messages.active'), 'class' => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200', 'dot' => 'bg-emerald-500'],
        'active' => ['label' => __('messages.active'), 'class' => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200', 'dot' => 'bg-emerald-500'],
        'maintenance' => ['label' => __('messages.in_repair'), 'class' => 'bg-amber-50 text-amber-700 ring-1 ring-amber-200', 'dot' => 'bg-amber-500'],
        'in_repair' => ['label' => __('messages.in_repair'), 'class' => 'bg-amber-50 text-amber-700 ring-1 ring-amber-200', 'dot' => 'bg-amber-500'],
        'available' => ['label' => __('messages.spare'), 'class' => 'bg-sky-50 text-sky-700 ring-1 ring-sky-200', 'dot' => 'bg-sky-500'],
        'spare' => ['label' => __('messages.spare'), 'class' => 'bg-sky-50 text-sky-700 ring-1 ring-sky-200', 'dot' => 'bg-sky-500'],
        'broken' => ['label' => __('messages.retired'), 'class' => 'bg-rose-50 text-rose-700 ring-1 ring-rose-200', 'dot' => 'bg-rose-500'],
        'retired' => ['label' => __('messages.retired'), 'class' => 'bg-rose-50 text-rose-700 ring-1 ring-rose-200', 'dot' => 'bg-rose-500'],
    ];

    $conditionMeta = [
        'good' => ['label' => 'Good', 'class' => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200'],
        'minor_issue' => ['label' => 'Minor Issue', 'class' => 'bg-amber-50 text-amber-700 ring-1 ring-amber-200'],
        'damaged' => ['label' => 'Damaged', 'class' => 'bg-rose-50 text-rose-700 ring-1 ring-rose-200'],
        'repair' => ['label' => 'In Repair', 'class' => 'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-200'],
        'disposed' => ['label' => 'Disposed', 'class' => 'bg-slate-100 text-slate-700 ring-1 ring-slate-200'],
        'lost' => ['label' => 'Lost', 'class' => 'bg-red-50 text-red-700 ring-1 ring-red-200'],
    ];

    $lifecycleMeta = [
        'active' => ['label' => 'Active', 'class' => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200'],
        'assigned' => ['label' => 'Assigned', 'class' => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200'],
        'spare' => ['label' => 'Spare', 'class' => 'bg-sky-50 text-sky-700 ring-1 ring-sky-200'],
        'in_repair' => ['label' => 'In Repair', 'class' => 'bg-amber-50 text-amber-700 ring-1 ring-amber-200'],
        'disposed' => ['label' => 'Disposed', 'class' => 'bg-slate-100 text-slate-700 ring-1 ring-slate-200'],
        'lost' => ['label' => 'Lost', 'class' => 'bg-red-50 text-red-700 ring-1 ring-red-200'],
        'replaced' => ['label' => 'Replaced', 'class' => 'bg-violet-50 text-violet-700 ring-1 ring-violet-200'],
    ];

    $formatValue = fn ($value, $fallback = 'Not set') => filled($value) ? $value : $fallback;
    $formatDate = fn ($date) => $date ? $date->format('d M Y') : null;
    $formatDateTime = fn ($date) => $date ? $date->format('d M Y H:i') : 'Not set';
    $formatMoney = fn ($value) => filled($value) ? 'Rp ' . number_format((float) $value, 0, ',', '.') : null;
    $formatLogValue = function ($value) {
        if (is_array($value)) {
            return json_encode($value);
        }

        return filled($value) ? (string) $value : 'Empty';
    };

    $statusKey = strtolower((string) $asset->status);
    $conditionKey = $asset->condition ?: 'good';
    $lifecycleKey = $asset->lifecycle_status ?: ($statusKey === 'available' ? 'spare' : 'active');
    $statusInfo = $statusMeta[$statusKey] ?? ['label' => ucfirst(str_replace('_', ' ', (string) $asset->status)), 'class' => 'bg-slate-100 text-slate-700 ring-1 ring-slate-200', 'dot' => 'bg-slate-500'];
    $conditionInfo = $conditionMeta[$conditionKey] ?? ['label' => ucfirst(str_replace('_', ' ', $conditionKey)), 'class' => 'bg-slate-100 text-slate-700 ring-1 ring-slate-200'];
    $lifecycleInfo = $lifecycleMeta[$lifecycleKey] ?? ['label' => ucfirst(str_replace('_', ' ', $lifecycleKey)), 'class' => 'bg-slate-100 text-slate-700 ring-1 ring-slate-200'];

    $warrantyDate = $asset->warranty_until ?: $asset->warranty_expired;
    $warrantyState = 'Not set';
    $warrantyClass = 'bg-slate-100 text-slate-700 ring-1 ring-slate-200';
    if ($warrantyDate) {
        if ($warrantyDate->isPast()) {
            $warrantyState = 'Expired';
            $warrantyClass = 'bg-rose-50 text-rose-700 ring-1 ring-rose-200';
        } elseif ($warrantyDate->diffInDays(now()) <= 30) {
            $warrantyState = 'Expiring soon';
            $warrantyClass = 'bg-amber-50 text-amber-700 ring-1 ring-amber-200';
        } else {
            $warrantyState = 'Covered';
            $warrantyClass = 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200';
        }
    }

    $sourceLabel = $asset->source_type
        ? \Illuminate\Support\Str::of($asset->source_type)->replace('_', ' ')->title()
        : ($asset->sync_source ? \Illuminate\Support\Str::of($asset->sync_source)->replace('_', ' ')->title() : 'Manual');

    $assetTitle = filled($asset->name) ? $asset->name : $asset->asset_code;
    $brandModel = trim(($asset->brand ?? '') . ' ' . ($asset->model ?? ''));
    $rawSpecMap = collect(explode('|', (string) $asset->specs))
        ->map(fn ($part) => trim($part))
        ->filter()
        ->mapWithKeys(function ($part) {
            [$key, $value] = array_pad(explode(':', $part, 2), 2, null);
            $key = strtolower(trim((string) $key));
            $value = trim((string) $value);

            return $key !== '' && $value !== '' ? [$key => $value] : [];
        });
    $specFallback = function (string ...$keys) use ($rawSpecMap) {
        foreach ($keys as $key) {
            $value = $rawSpecMap->get(strtolower($key));

            if (filled($value)) {
                return $value;
            }
        }

        return null;
    };

    $cpuValue = $asset->cpu ?: $specFallback('cpu', 'processor');
    $ramValue = $asset->ram_gb ? $asset->ram_gb . ' GB' : $specFallback('ram', 'memory');
    $storageValue = $asset->storage_detail
        ? ($asset->storage_gb ? "{$asset->storage_detail} ({$asset->storage_gb} GB)" : $asset->storage_detail)
        : ($asset->storage_gb ? $asset->storage_gb . ' GB' : $specFallback('storage', 'disk'));
    $osValue = $asset->os_name ?: $specFallback('os', 'operating system');
    $ipValue = $asset->ip_address ?: $specFallback('ip', 'ip address');
    $rawUserValue = $specFallback('user', 'username', 'logged user');

    $trackedFields = [
        $asset->asset_code,
        $asset->name,
        $asset->category,
        $asset->serial_number,
        $asset->brand,
        $asset->model,
        $asset->department_id,
        $asset->location,
        $asset->condition,
        $asset->lifecycle_status,
        $asset->warranty_until ?: $asset->warranty_expired,
        $cpuValue,
        $ramValue,
        $storageValue,
        $osValue,
        $ipValue,
        $asset->notes,
    ];
    $filledFields = collect($trackedFields)->filter(fn ($field) => filled($field))->count();
    $dataCompleteness = (int) round(($filledFields / max(count($trackedFields), 1)) * 100);

    $identityFields = [
        ['label' => 'Asset Code', 'value' => $asset->asset_code, 'copy' => true],
        ['label' => 'Serial Number', 'value' => $asset->serial_number, 'mono' => true, 'copy' => true],
        ['label' => 'Hostname', 'value' => $asset->hostname, 'mono' => true, 'copy' => true],
        ['label' => 'Category', 'value' => $asset->category],
        ['label' => 'Sub Category', 'value' => $asset->sub_category],
        ['label' => 'Source', 'value' => $sourceLabel],
    ];

    $hardwareFields = [
        ['label' => 'Brand / Model', 'value' => $brandModel],
        ['label' => 'CPU', 'value' => $cpuValue],
        ['label' => 'RAM', 'value' => $ramValue],
        ['label' => 'Storage', 'value' => $storageValue],
        ['label' => 'Operating System', 'value' => $osValue],
        ['label' => 'IP Address', 'value' => $ipValue, 'mono' => true, 'copy' => true],
        ['label' => 'RustDesk ID', 'value' => $asset->rustdesk_id, 'mono' => true, 'copy' => true],
    ];

    $ownershipFields = [
        ['label' => 'Factory', 'value' => $asset->factory],
        ['label' => 'Department', 'value' => $asset->department?->name],
        ['label' => 'Assigned User', 'value' => $asset->user?->name ?: $rawUserValue],
        ['label' => 'Location', 'value' => $asset->location ?: $asset->factory],
        ['label' => 'Purchase Date', 'value' => $formatDate($asset->purchase_date)],
        ['label' => 'Warranty Until', 'value' => $formatDate($warrantyDate)],
        ['label' => 'Price', 'value' => $formatMoney($asset->price)],
    ];

    $metricCards = [
        [
            'label' => 'Data Completeness',
            'value' => $dataCompleteness . '%',
            'caption' => "{$filledFields}/" . count($trackedFields) . ' key fields',
            'class' => 'border-emerald-100 bg-emerald-50/60 text-emerald-800',
        ],
        [
            'label' => 'Warranty',
            'value' => $warrantyState,
            'caption' => $warrantyDate ? $formatDate($warrantyDate) : 'No warranty date',
            'class' => 'border-amber-100 bg-amber-50/60 text-amber-800',
        ],
        [
            'label' => $isParentCategory ? 'Attached Assets' : 'Host Relation',
            'value' => $isParentCategory ? $attachedAssets->count() : ($parentAsset ? 'Linked' : 'Spare'),
            'caption' => $relationHistory->count() . ' movement records',
            'class' => 'border-indigo-100 bg-indigo-50/60 text-indigo-800',
        ],
        [
            'label' => 'Audit Events',
            'value' => $mutationHistory->count(),
            'caption' => $asset->last_synced_at ? 'Synced ' . $asset->last_synced_at->diffForHumans() : 'Manual update flow',
            'class' => 'border-slate-200 bg-white text-slate-800',
        ],
    ];
@endphp

<x-app-layout>
    <div
        class="min-h-screen bg-slate-50 pb-10 pt-5"
        x-data="{
            tab: 'overview',
            copied: '',
            copy(value, label) {
                if (!value || !navigator.clipboard) return;
                navigator.clipboard.writeText(value).then(() => {
                    this.copied = label;
                    setTimeout(() => this.copied = '', 1600);
                });
            }
        }"
    >
        @if (session('success') || session('error'))
            <div
                x-data="{ open: true }"
                x-init="setTimeout(() => open = false, 3000)"
                x-show="open"
                x-transition
                class="fixed right-4 top-4 z-50 flex max-w-sm items-start gap-3 rounded-xl px-4 py-3 text-white shadow-xl {{ session('success') ? 'bg-emerald-600 shadow-emerald-500/30' : 'bg-rose-600 shadow-rose-500/30' }}"
            >
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white/15">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                        @if (session('success'))
                            <path d="M20 6 9 17l-5-5" />
                        @else
                            <path d="M18 6 6 18M6 6l12 12" />
                        @endif
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold">{{ session('success') ? 'Success' : 'Error' }}</p>
                    <p class="text-xs text-white/90">{{ session('success') ?: session('error') }}</p>
                </div>
            </div>
        @endif

        <div
            x-show="copied"
            x-transition
            class="fixed bottom-5 right-5 z-50 rounded-lg bg-slate-950 px-4 py-2 text-sm font-semibold text-white shadow-xl"
        >
            <span x-text="copied"></span> copied
        </div>

        <div class="mx-auto max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex flex-wrap items-center gap-2 text-xs font-semibold text-slate-500">
                    <a href="{{ route('admin.assets.overview') }}" class="hover:text-emerald-700">Asset Center</a>
                    <span>/</span>
                    <a href="{{ $asset->source_type === 'manual' ? route('admin.assets.manual.index') : route('assets.index') }}" class="hover:text-emerald-700">{{ $sourceLabel }}</a>
                    <span>/</span>
                    <span class="text-slate-900">{{ $asset->asset_code }}</span>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <a
                        href="{{ url()->previous() }}"
                        class="inline-flex h-9 items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 shadow-sm transition hover:border-emerald-200 hover:text-emerald-700"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m15 18-6-6 6-6" />
                        </svg>
                        Back
                    </a>
                    <a
                        href="{{ $asset->source_type === 'manual' ? route('admin.assets.manual.edit', $asset) : route('assets.edit', $asset) }}"
                        class="inline-flex h-9 items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 text-xs font-semibold text-white shadow-sm transition hover:bg-emerald-700"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 20h9" />
                            <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z" />
                        </svg>
                        Edit Asset
                    </a>
                </div>
            </div>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="grid gap-0 lg:grid-cols-[1.8fr_1fr]">
                    <div class="p-5 sm:p-6 lg:p-7">
                        <div class="flex flex-col gap-5 md:flex-row md:items-start md:justify-between">
                            <div class="min-w-0 space-y-4">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-bold uppercase tracking-wide {{ $statusInfo['class'] }}">
                                        <span class="h-2 w-2 rounded-full {{ $statusInfo['dot'] }}"></span>
                                        {{ $statusInfo['label'] }}
                                    </span>
                                    <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $conditionInfo['class'] }}">
                                        {{ $conditionInfo['label'] }}
                                    </span>
                                    <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $lifecycleInfo['class'] }}">
                                        {{ $lifecycleInfo['label'] }}
                                    </span>
                                    <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700 ring-1 ring-slate-200">
                                        {{ $sourceLabel }}
                                    </span>
                                </div>

                                <div>
                                    <h1 class="break-words text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">{{ $assetTitle }}</h1>
                                    <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-slate-600">
                                        <span class="font-semibold text-slate-900">{{ $asset->asset_code }}</span>
                                        @if ($asset->category)
                                            <span>{{ $asset->category }}</span>
                                        @endif
                                        @if ($brandModel)
                                            <span>{{ $brandModel }}</span>
                                        @endif
                                        @if ($asset->hostname)
                                            <span class="font-mono text-xs">{{ $asset->hostname }}</span>
                                        @endif
                                    </div>
                                </div>

                                <div class="grid gap-3 text-sm sm:grid-cols-2 xl:grid-cols-4">
                                    @foreach ([
                                        ['label' => 'Department', 'value' => $asset->department?->name],
                                        ['label' => 'Assigned User', 'value' => $asset->user?->name ?: $rawUserValue],
                                        ['label' => 'Location', 'value' => $asset->location ?: $asset->factory],
                                        ['label' => 'Updated', 'value' => $formatDateTime($asset->updated_at)],
                                    ] as $heroField)
                                        <div class="border-l border-slate-200 pl-3">
                                            <div class="text-[10px] font-bold uppercase tracking-[0.22em] text-slate-400">{{ $heroField['label'] }}</div>
                                            <div class="mt-1 truncate font-semibold text-slate-900" title="{{ $formatValue($heroField['value']) }}">{{ $formatValue($heroField['value']) }}</div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="flex shrink-0 flex-row gap-2 md:flex-col">
                                <button
                                    type="button"
                                    class="inline-flex h-10 items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 shadow-sm transition hover:border-emerald-200 hover:text-emerald-700"
                                    title="Copy asset code"
                                    @click="copy(@js($asset->asset_code), 'Asset code')"
                                >
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="9" y="9" width="13" height="13" rx="2" />
                                        <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" />
                                    </svg>
                                    Copy Code
                                </button>
                                @if ($asset->rustdesk_id)
                                    <button
                                        type="button"
                                        class="inline-flex h-10 items-center justify-center gap-2 rounded-lg border border-indigo-200 bg-indigo-50 px-3 text-xs font-semibold text-indigo-700 shadow-sm transition hover:bg-indigo-100"
                                        title="Copy RustDesk ID"
                                        @click="copy(@js($asset->rustdesk_id), 'RustDesk ID')"
                                    >
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                            <rect x="3" y="4" width="18" height="12" rx="2" />
                                            <path d="M8 20h8" />
                                            <path d="M12 16v4" />
                                        </svg>
                                        Remote ID
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-slate-200 bg-slate-50 p-5 sm:p-6 lg:border-l lg:border-t-0">
                        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-1">
                            <div>
                                <div class="text-[10px] font-bold uppercase tracking-[0.22em] text-slate-400">Warranty</div>
                                <div class="mt-2 flex items-center justify-between gap-3">
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $warrantyClass }}">{{ $warrantyState }}</span>
                                    <span class="text-sm font-semibold text-slate-900">{{ $warrantyDate ? $formatDate($warrantyDate) : 'Not set' }}</span>
                                </div>
                            </div>
                            <div class="border-t border-slate-200 pt-4 sm:border-t-0 sm:pt-0 lg:border-t lg:pt-4">
                                <div class="text-[10px] font-bold uppercase tracking-[0.22em] text-slate-400">Sync Health</div>
                                <div class="mt-2 text-sm font-semibold text-slate-900">
                                    {{ $asset->last_synced_at ? $asset->last_synced_at->diffForHumans() : 'Manual record' }}
                                </div>
                                <div class="mt-1 text-xs text-slate-500">{{ $asset->sync_source ? 'Source: ' . $sourceLabel : 'No agent sync source' }}</div>
                            </div>
                            <div class="border-t border-slate-200 pt-4 sm:col-span-2 lg:col-span-1">
                                <div class="mb-2 flex items-center justify-between text-xs font-semibold text-slate-500">
                                    <span>Completeness</span>
                                    <span>{{ $dataCompleteness }}%</span>
                                </div>
                                <div class="h-2 overflow-hidden rounded-full bg-slate-200">
                                    <div class="h-full rounded-full bg-emerald-500" style="width: {{ $dataCompleteness }}%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($metricCards as $card)
                    <div class="rounded-xl border p-4 shadow-sm {{ $card['class'] }}">
                        <div class="text-[10px] font-bold uppercase tracking-[0.24em] opacity-70">{{ $card['label'] }}</div>
                        <div class="mt-2 text-2xl font-bold">{{ $card['value'] }}</div>
                        <div class="mt-1 text-xs font-medium opacity-75">{{ $card['caption'] }}</div>
                    </div>
                @endforeach
            </div>

            <div class="sticky top-0 z-20 -mx-4 border-y border-slate-200 bg-slate-50/95 px-4 py-2 backdrop-blur sm:static sm:mx-0 sm:rounded-xl sm:border sm:bg-white sm:px-2">
                <div class="flex gap-1 overflow-x-auto">
                    @foreach ([
                        ['key' => 'overview', 'label' => 'Overview'],
                        ['key' => 'relationships', 'label' => 'Relationships', 'count' => $isParentCategory ? $attachedAssets->count() : ($parentAsset ? 1 : 0)],
                        ['key' => 'history', 'label' => 'History', 'count' => $mutationHistory->count()],
                    ] as $nav)
                        <button
                            type="button"
                            @click="tab = '{{ $nav['key'] }}'"
                            class="inline-flex h-10 shrink-0 items-center justify-center gap-2 rounded-lg px-4 text-sm font-semibold transition"
                            :class="tab === '{{ $nav['key'] }}' ? 'bg-slate-950 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-950'"
                        >
                            {{ $nav['label'] }}
                            @if (isset($nav['count']))
                                <span class="rounded-full bg-white/15 px-2 py-0.5 text-[10px]">{{ $nav['count'] }}</span>
                            @endif
                        </button>
                    @endforeach
                </div>
            </div>

            <div x-show="tab === 'overview'" x-transition.opacity class="grid gap-5 lg:grid-cols-[1.45fr_.95fr]">
                <div class="space-y-5">
                    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <h2 class="text-lg font-bold text-slate-950">Technical Inventory</h2>
                                <p class="mt-1 text-sm text-slate-500">{{ $asset->category ?: 'Asset' }} specification and identifiers.</p>
                            </div>
                            <span class="hidden rounded-lg bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600 sm:inline-flex">{{ $sourceLabel }}</span>
                        </div>

                        <div class="mt-5 grid gap-x-6 gap-y-1 md:grid-cols-2">
                            @foreach (array_merge($identityFields, $hardwareFields) as $field)
                                <div class="flex min-h-[54px] items-center justify-between gap-4 border-b border-slate-100 py-3">
                                    <div class="min-w-0">
                                        <div class="text-[10px] font-bold uppercase tracking-[0.22em] text-slate-400">{{ $field['label'] }}</div>
                                        <div class="mt-1 truncate text-sm font-semibold text-slate-900 {{ ($field['mono'] ?? false) ? 'font-mono' : '' }}" title="{{ $formatValue($field['value']) }}">
                                            {{ $formatValue($field['value']) }}
                                        </div>
                                    </div>
                                    @if (($field['copy'] ?? false) && filled($field['value']))
                                        <button
                                            type="button"
                                            class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:border-emerald-200 hover:text-emerald-700"
                                            title="Copy {{ $field['label'] }}"
                                            @click="copy(@js($field['value']), @js($field['label']))"
                                        >
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                <rect x="9" y="9" width="13" height="13" rx="2" />
                                                <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" />
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </section>

                    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h2 class="text-lg font-bold text-slate-950">Ownership & Commercials</h2>
                        <div class="mt-4 grid gap-x-6 gap-y-1 md:grid-cols-2">
                            @foreach ($ownershipFields as $field)
                                <div class="flex min-h-[54px] items-center justify-between gap-4 border-b border-slate-100 py-3">
                                    <div class="min-w-0">
                                        <div class="text-[10px] font-bold uppercase tracking-[0.22em] text-slate-400">{{ $field['label'] }}</div>
                                        <div class="mt-1 truncate text-sm font-semibold text-slate-900" title="{{ $formatValue($field['value']) }}">{{ $formatValue($field['value']) }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>
                </div>

                <div class="space-y-5">
                    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h2 class="text-lg font-bold text-slate-950">Lifecycle Control</h2>
                        <form method="POST" action="{{ route('admin.assets.lifecycle.update', $asset) }}" class="mt-4 space-y-4">
                            @csrf
                            @method('PATCH')
                            <div>
                                <label class="text-xs font-bold uppercase tracking-[0.22em] text-slate-400">Lifecycle</label>
                                <select name="lifecycle_status" class="mt-2 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-800 focus:border-emerald-300 focus:bg-white focus:outline-none">
                                    @foreach (['active' => 'Active', 'assigned' => 'Assigned', 'spare' => 'Spare', 'in_repair' => 'In Repair', 'disposed' => 'Disposed', 'lost' => 'Lost', 'replaced' => 'Replaced'] as $value => $label)
                                        <option value="{{ $value }}" @selected($lifecycleKey === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="text-xs font-bold uppercase tracking-[0.22em] text-slate-400">Condition</label>
                                <select name="condition" class="mt-2 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-800 focus:border-emerald-300 focus:bg-white focus:outline-none">
                                    @foreach (['good' => 'Good', 'minor_issue' => 'Minor Issue', 'damaged' => 'Damaged', 'repair' => 'In Repair', 'disposed' => 'Disposed', 'lost' => 'Lost'] as $value => $label)
                                        <option value="{{ $value }}" @selected($conditionKey === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="text-xs font-bold uppercase tracking-[0.22em] text-slate-400">Warranty Until</label>
                                <input
                                    type="date"
                                    name="warranty_until"
                                    value="{{ old('warranty_until', optional($asset->warranty_until ?: $asset->warranty_expired)->format('Y-m-d')) }}"
                                    class="mt-2 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-800 focus:border-emerald-300 focus:bg-white focus:outline-none"
                                >
                            </div>
                            <div>
                                <label class="text-xs font-bold uppercase tracking-[0.22em] text-slate-400">Update Note</label>
                                <textarea
                                    name="notes"
                                    rows="3"
                                    class="mt-2 w-full resize-none rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-800 focus:border-emerald-300 focus:bg-white focus:outline-none"
                                >{{ old('notes', $asset->notes) }}</textarea>
                            </div>
                            <button type="submit" class="inline-flex h-10 w-full items-center justify-center rounded-lg bg-emerald-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700">
                                Save Lifecycle
                            </button>
                        </form>
                    </section>

                    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h2 class="text-lg font-bold text-slate-950">Notes & Specs</h2>
                        <div class="mt-4 space-y-4">
                            <div>
                                <div class="text-[10px] font-bold uppercase tracking-[0.22em] text-slate-400">Notes</div>
                                <p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-700">{{ $asset->notes ?: 'No notes recorded.' }}</p>
                            </div>
                            @if ($asset->specs)
                                <details class="border-t border-slate-100 pt-4">
                                    <summary class="cursor-pointer select-none text-[10px] font-bold uppercase tracking-[0.22em] text-slate-400 transition hover:text-slate-600">
                                        Original Agent Payload
                                    </summary>
                                    <p class="mt-3 whitespace-pre-line break-words rounded-lg bg-slate-50 p-3 font-mono text-xs leading-6 text-slate-700">{{ $asset->specs }}</p>
                                </details>
                            @endif
                        </div>
                    </section>
                </div>
            </div>

            <div x-show="tab === 'relationships'" x-transition.opacity class="grid gap-5 lg:grid-cols-[1.25fr_.9fr]">
                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="text-lg font-bold text-slate-950">Relationship Workspace</h2>
                            <p class="mt-1 text-sm text-slate-500">{{ $isParentCategory ? 'Attached devices under this host asset.' : 'Host assignment for this asset.' }}</p>
                        </div>
                        <span class="inline-flex w-fit rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700 ring-1 ring-slate-200">
                            {{ $isParentCategory ? $attachedAssets->count() . ' attached' : ($parentAsset ? 'Linked' : 'Spare') }}
                        </span>
                    </div>

                    <div class="mt-5">
                        @if ($isParentCategory)
                            @if ($attachedAssets->isEmpty())
                                <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center">
                                    <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-lg bg-white text-slate-400 shadow-sm">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M10 13a5 5 0 0 0 7.1 0l1.4-1.4a5 5 0 0 0-7.1-7.1L10.7 5" />
                                            <path d="M14 11a5 5 0 0 0-7.1 0l-1.4 1.4a5 5 0 0 0 7.1 7.1l.7-.7" />
                                        </svg>
                                    </div>
                                    <p class="mt-3 text-sm font-semibold text-slate-700">No active child assets.</p>
                                </div>
                            @else
                                <div class="divide-y divide-slate-100">
                                    @foreach ($attachedAssets as $child)
                                        <div class="flex flex-col gap-3 py-4 sm:flex-row sm:items-center sm:justify-between">
                                            <div class="min-w-0">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <a href="{{ route('assets.show', $child) }}" class="truncate text-base font-bold text-slate-950 hover:text-emerald-700">{{ $child->name }}</a>
                                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold uppercase text-slate-600">{{ $child->category ?: 'Asset' }}</span>
                                                </div>
                                                <div class="mt-1 flex flex-wrap gap-x-3 gap-y-1 text-xs text-slate-500">
                                                    <span class="font-mono">{{ $child->asset_code }}</span>
                                                    <span>Serial: {{ $child->serial_number ?: 'Not set' }}</span>
                                                    <span>Started: {{ optional($child->pivot->started_at)->format('d M Y H:i') ?: 'Not set' }}</span>
                                                </div>
                                            </div>
                                            <form method="POST" action="{{ route('admin.assets.relations.detach', $child->pivot->id) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="inline-flex h-9 items-center justify-center rounded-lg border border-rose-200 bg-white px-3 text-xs font-semibold text-rose-600 transition hover:bg-rose-50">
                                                    Detach
                                                </button>
                                            </form>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        @else
                            @if ($parentAsset)
                                <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                                    <div class="text-[10px] font-bold uppercase tracking-[0.22em] text-emerald-700">Current Host</div>
                                    <div class="mt-2 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                        <div>
                                            <a href="{{ route('assets.show', $parentAsset) }}" class="text-lg font-bold text-slate-950 hover:text-emerald-800">{{ $parentAsset->name }}</a>
                                            <div class="mt-1 flex flex-wrap gap-x-3 gap-y-1 text-xs text-slate-600">
                                                <span class="font-mono">{{ $parentAsset->asset_code }}</span>
                                                <span>{{ $parentAsset->serial_number ?: 'No serial' }}</span>
                                                <span>{{ $parentAsset->user?->name ?: 'Unassigned' }}</span>
                                            </div>
                                        </div>
                                        <form method="POST" action="{{ route('admin.assets.relations.detach', $activeParentRelation->id) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="inline-flex h-9 items-center justify-center rounded-lg border border-rose-200 bg-white px-3 text-xs font-semibold text-rose-600 transition hover:bg-rose-50">
                                                Detach
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @else
                                <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center">
                                    <p class="text-sm font-semibold text-slate-700">No active host relation.</p>
                                </div>
                            @endif
                        @endif
                    </div>

                    <div class="mt-6 border-t border-slate-100 pt-5">
                        @if ($isParentCategory)
                            <h3 class="text-sm font-bold text-slate-950">Attach Asset</h3>
                            <form method="POST" action="{{ route('admin.assets.relations.attach', $asset) }}" class="mt-3 grid gap-3 md:grid-cols-[1fr_.7fr_auto]">
                                @csrf
                                <select name="child_asset_id" required class="h-10 rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm font-semibold text-slate-800 focus:border-emerald-300 focus:bg-white focus:outline-none" @disabled($attachableAssets->isEmpty())>
                                    <option value="" disabled selected>{{ $attachableAssets->isEmpty() ? 'No attachable assets available' : 'Select asset' }}</option>
                                    @foreach ($attachableAssets as $available)
                                        <option value="{{ $available->id }}">[{{ $available->category }}] {{ $available->name }} - {{ $available->asset_code }}</option>
                                    @endforeach
                                </select>
                                <input name="notes" type="text" class="h-10 rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm text-slate-800 focus:border-emerald-300 focus:bg-white focus:outline-none" placeholder="Relation note">
                                <button type="submit" class="h-10 rounded-lg bg-emerald-600 px-4 text-sm font-semibold text-white transition hover:bg-emerald-700" @disabled($attachableAssets->isEmpty())>Attach</button>
                            </form>
                        @elseif (! $parentAsset)
                            <h3 class="text-sm font-bold text-slate-950">Attach Host</h3>
                            <form method="POST" action="{{ route('admin.assets.relations.attach-parent', $asset) }}" class="mt-3 grid gap-3 md:grid-cols-[1fr_.7fr_auto]">
                                @csrf
                                <select name="parent_asset_id" required class="h-10 rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm font-semibold text-slate-800 focus:border-emerald-300 focus:bg-white focus:outline-none" @disabled($attachableParents->isEmpty())>
                                    <option value="" disabled selected>{{ $attachableParents->isEmpty() ? 'No host assets available' : 'Select PC or laptop' }}</option>
                                    @foreach ($attachableParents as $parent)
                                        <option value="{{ $parent->id }}">[{{ $parent->category }}] {{ $parent->name }} - {{ $parent->asset_code }}</option>
                                    @endforeach
                                </select>
                                <input name="notes" type="text" class="h-10 rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm text-slate-800 focus:border-emerald-300 focus:bg-white focus:outline-none" placeholder="Relation note">
                                <button type="submit" class="h-10 rounded-lg bg-emerald-600 px-4 text-sm font-semibold text-white transition hover:bg-emerald-700" @disabled($attachableParents->isEmpty())>Attach</button>
                            </form>
                        @endif
                    </div>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-bold text-slate-950">Relationship Movement</h2>
                    @if ($relationHistory->isEmpty())
                        <div class="mt-5 rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center text-sm font-semibold text-slate-600">No movement history.</div>
                    @else
                        <div class="mt-5 space-y-0">
                            @foreach ($relationHistory as $history)
                                @php
                                    $otherAsset = $asset->id === $history->parent_asset_id ? $history->childAsset : $history->parentAsset;
                                    $direction = $asset->id === $history->parent_asset_id ? 'Child asset' : 'Host asset';
                                @endphp
                                <div class="relative border-l border-slate-200 pb-5 pl-5 last:pb-0">
                                    <span class="absolute -left-[5px] top-1 h-2.5 w-2.5 rounded-full {{ $history->ended_at ? 'bg-slate-300' : 'bg-emerald-500' }}"></span>
                                    <div class="flex flex-wrap items-start justify-between gap-2">
                                        <div>
                                            <div class="text-[10px] font-bold uppercase tracking-[0.22em] text-slate-400">{{ $direction }}</div>
                                            @if ($otherAsset)
                                                <a href="{{ route('assets.show', $otherAsset) }}" class="mt-1 block text-sm font-bold text-slate-950 hover:text-emerald-700">{{ $otherAsset->name }}</a>
                                                <div class="mt-0.5 text-xs font-mono text-slate-500">{{ $otherAsset->asset_code }}</div>
                                            @else
                                                <div class="mt-1 text-sm font-bold text-slate-500">Deleted asset</div>
                                            @endif
                                        </div>
                                        <span class="rounded-full px-2 py-0.5 text-[10px] font-bold uppercase {{ $history->ended_at ? 'bg-slate-100 text-slate-600' : 'bg-emerald-100 text-emerald-700' }}">
                                            {{ $history->ended_at ? 'Ended' : 'Active' }}
                                        </span>
                                    </div>
                                    <div class="mt-2 text-xs leading-5 text-slate-500">
                                        <div>Started: {{ $history->started_at ? $history->started_at->format('d M Y H:i') : 'Not set' }}</div>
                                        <div>Ended: {{ $history->ended_at ? $history->ended_at->format('d M Y H:i') : 'Still active' }}</div>
                                        @if ($history->notes)
                                            <div class="mt-1 text-slate-700">{{ $history->notes }}</div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </section>
            </div>

            <div x-show="tab === 'history'" x-transition.opacity class="grid gap-5 lg:grid-cols-[1.15fr_.85fr]">
                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-bold text-slate-950">Asset Mutation Timeline</h2>
                    @if ($mutationHistory->isEmpty())
                        <div class="mt-5 rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center text-sm font-semibold text-slate-600">No audit events recorded.</div>
                    @else
                        <div class="mt-5 space-y-0">
                            @foreach ($mutationHistory as $log)
                                @php
                                    $changes = data_get($log->metadata, 'changes', []);
                                    $previous = data_get($log->metadata, 'previous', []);
                                @endphp
                                <div class="relative border-l border-slate-200 pb-6 pl-5 last:pb-0">
                                    <span class="absolute -left-[13px] top-0 flex h-6 w-6 items-center justify-center rounded-full bg-white ring-1 ring-slate-200">
                                        <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                                    </span>
                                    <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between">
                                        <div>
                                            <div class="text-sm font-bold text-slate-950">{{ \Illuminate\Support\Str::of($log->action)->replace('_', ' ')->title() }}</div>
                                            <div class="mt-0.5 text-xs text-slate-500">By {{ $log->actor?->name ?: 'System' }}</div>
                                        </div>
                                        <div class="text-xs font-semibold text-slate-500">{{ $log->created_at->format('d M Y H:i') }}</div>
                                    </div>
                                    @if ($log->notes)
                                        <p class="mt-3 rounded-lg bg-slate-50 px-3 py-2 text-sm leading-6 text-slate-700">{{ $log->notes }}</p>
                                    @endif
                                    @if (! empty($changes))
                                        <div class="mt-3 grid gap-2 sm:grid-cols-2">
                                            @foreach ($changes as $field => $value)
                                                <div class="rounded-lg border border-slate-200 bg-white p-3">
                                                    <div class="text-[10px] font-bold uppercase tracking-[0.22em] text-slate-400">{{ \Illuminate\Support\Str::of($field)->replace('_', ' ')->title() }}</div>
                                                    <div class="mt-1 text-xs text-slate-500">Before: <span class="font-semibold text-slate-700">{{ $formatLogValue($previous[$field] ?? null) }}</span></div>
                                                    <div class="mt-0.5 text-xs text-slate-500">After: <span class="font-semibold text-slate-900">{{ $formatLogValue($value) }}</span></div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-bold text-slate-950">Record Metadata</h2>
                    <div class="mt-4 divide-y divide-slate-100">
                        @foreach ([
                            ['label' => 'Created At', 'value' => $formatDateTime($asset->created_at)],
                            ['label' => 'Updated At', 'value' => $formatDateTime($asset->updated_at)],
                            ['label' => 'Last Synced At', 'value' => $asset->last_synced_at ? $formatDateTime($asset->last_synced_at) : 'Never synced'],
                            ['label' => 'Source Type', 'value' => $sourceLabel],
                            ['label' => 'Sync Source', 'value' => $asset->sync_source ?: 'Not set'],
                        ] as $meta)
                            <div class="flex items-center justify-between gap-4 py-3">
                                <div class="text-[10px] font-bold uppercase tracking-[0.22em] text-slate-400">{{ $meta['label'] }}</div>
                                <div class="max-w-[60%] truncate text-right text-sm font-semibold text-slate-900" title="{{ $meta['value'] }}">{{ $meta['value'] }}</div>
                            </div>
                        @endforeach
                    </div>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
