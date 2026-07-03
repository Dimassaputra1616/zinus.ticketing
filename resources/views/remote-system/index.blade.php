@php
    $isCustomServer = filter_var(config('services.rustdesk.custom_server'), FILTER_VALIDATE_BOOLEAN);
    $rustdeskUriFor = function ($asset) use ($isCustomServer) {
        $uri = "rustdesk://connection/new/{$asset->rustdesk_id}";

        if ($isCustomServer && config('services.rustdesk.id_server')) {
            $configuration = [
                'custom-rendezvous-server' => config('services.rustdesk.id_server'),
                'key' => config('services.rustdesk.key'),
            ];
            $uri .= '?conf=' . base64_encode(json_encode($configuration));
        }

        return $uri;
    };
    $pageDevices = $assets->getCollection()
        ->map(fn ($asset) => ['id' => $asset->id, 'ip' => $asset->ip_address])
        ->values();
@endphp

<x-app-layout>
    <div
        class="w-full space-y-4 pb-8"
        x-data="remoteSystemPage({
            devices: @js($pageDevices),
            pingUrl: @js(route('remote-system.ping'))
        })"
        x-init="init()"
    >
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 bg-gradient-to-r from-white via-white to-emerald-50/60 px-4 py-4 sm:px-5">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <rect x="3" y="4" width="18" height="13" rx="2" />
                                    <path d="M8 21h8m-4-4v4" />
                                </svg>
                            </span>
                            <div>
                                <h2 class="text-lg font-bold tracking-tight text-slate-900">Remote endpoints</h2>
                                <p class="text-xs text-slate-500">PC dan laptop yang terdaftar untuk monitoring dan koneksi RustDesk.</p>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                        <div class="rounded-xl border border-slate-200 bg-white px-3 py-2">
                            <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400">Registered</p>
                            <p class="mt-0.5 text-lg font-bold text-slate-900">{{ number_format($totalEndpoints) }}</p>
                        </div>
                        <div class="rounded-xl border border-emerald-100 bg-emerald-50/70 px-3 py-2">
                            <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-emerald-600">Ready</p>
                            <p class="mt-0.5 text-lg font-bold text-emerald-800">{{ number_format($readyEndpoints) }}</p>
                        </div>
                        <div class="rounded-xl border border-amber-100 bg-amber-50/70 px-3 py-2">
                            <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-amber-600">Missing ID</p>
                            <p class="mt-0.5 text-lg font-bold text-amber-800">{{ number_format($missingEndpoints) }}</p>
                        </div>
                        <div class="rounded-xl border border-sky-100 bg-sky-50/70 px-3 py-2">
                            <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-sky-600">Online page</p>
                            <p class="mt-0.5 text-lg font-bold text-sky-800">
                                <span x-text="onlineCount">0</span><span class="text-xs font-semibold text-sky-500">/{{ $assets->count() }}</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-3 px-4 py-3 sm:px-5">
                <form method="GET" class="grid gap-2 lg:grid-cols-[minmax(260px,1fr)_190px_auto_auto]">
                    <label class="relative block">
                        <span class="sr-only">Search remote endpoints</span>
                        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <circle cx="11" cy="11" r="7" />
                            <path d="m20 20-3.5-3.5" stroke-linecap="round" />
                        </svg>
                        <input
                            type="search"
                            name="q"
                            value="{{ $search }}"
                            placeholder="Search name, hostname, IP, user..."
                            class="h-10 w-full rounded-xl border-slate-200 bg-slate-50 pl-9 pr-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-emerald-400 focus:ring-emerald-200"
                        >
                    </label>

                    <select
                        name="connection"
                        class="h-10 rounded-xl border-slate-200 bg-slate-50 py-0 text-sm font-medium text-slate-700 focus:border-emerald-400 focus:ring-emerald-200"
                    >
                        <option value="all" @selected($connection === 'all')>All configurations</option>
                        <option value="ready" @selected($connection === 'ready')>RustDesk ready</option>
                        <option value="missing" @selected($connection === 'missing')>Missing RustDesk ID</option>
                    </select>

                    <button
                        type="submit"
                        class="inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-slate-950 px-4 text-sm font-semibold text-white transition hover:bg-slate-800"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path d="M4 5h16l-6 7v5l-4 2v-7L4 5Z" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        Apply
                    </button>

                    @if ($search !== '' || $connection !== 'all')
                        <a
                            href="{{ route('remote-system.index') }}"
                            class="inline-flex h-10 items-center justify-center rounded-xl border border-slate-200 px-4 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 hover:text-slate-900"
                        >
                            Reset
                        </a>
                    @endif
                </form>

                <div class="flex flex-col gap-2 border-t border-slate-100 pt-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-1 rounded-xl bg-slate-100 p-1">
                        @foreach ([
                            ['key' => 'all', 'label' => 'All'],
                            ['key' => 'online', 'label' => 'Online'],
                            ['key' => 'offline', 'label' => 'Offline'],
                        ] as $filter)
                            <button
                                type="button"
                                @click="setLiveFilter('{{ $filter['key'] }}')"
                                class="inline-flex h-8 items-center gap-1.5 rounded-lg px-3 text-xs font-bold transition"
                                :class="liveFilter === '{{ $filter['key'] }}' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-800'"
                            >
                                {{ $filter['label'] }}
                                @if ($filter['key'] === 'online')
                                    <span class="text-[10px] text-emerald-600" x-text="onlineCount"></span>
                                @elseif ($filter['key'] === 'offline')
                                    <span class="text-[10px] text-slate-500" x-text="offlineCount"></span>
                                @endif
                            </button>
                        @endforeach
                    </div>

                    <p class="text-xs text-slate-500">
                        Showing <span class="font-semibold text-slate-700">{{ $assets->firstItem() ?? 0 }}-{{ $assets->lastItem() ?? 0 }}</span>
                        of <span class="font-semibold text-slate-700">{{ number_format($assets->total()) }}</span> matching endpoints
                    </p>
                </div>
            </div>
        </section>

        @if ($assets->isNotEmpty())
            <div
                x-cloak
                x-show="checkingCount === 0 && visibleCount === 0"
                class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800"
            >
                Tidak ada perangkat <span class="font-bold" x-text="liveFilter"></span> pada halaman ini.
            </div>

            <section class="hidden overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm lg:block">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[980px] text-left text-sm">
                        <thead class="bg-slate-50 text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500">
                            <tr>
                                <th class="px-4 py-3">Device</th>
                                <th class="px-4 py-3">Owner / Department</th>
                                <th class="px-4 py-3">IP Address</th>
                                <th class="px-4 py-3">Live Status</th>
                                <th class="px-4 py-3">RustDesk</th>
                                <th class="px-4 py-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($assets as $asset)
                                @php
                                    $hasRustdesk = filled($asset->rustdesk_id);
                                    $editUrl = $asset->source_type === 'manual'
                                        ? route('admin.assets.manual.edit', $asset)
                                        : route('assets.edit', $asset);
                                @endphp
                                <tr
                                    data-remote-device
                                    data-device-id="{{ $asset->id }}"
                                    data-live-status="checking"
                                    class="transition hover:bg-emerald-50/30"
                                >
                                    <td class="px-4 py-3">
                                        <div class="flex min-w-0 items-center gap-3">
                                            <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-600">
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                    <rect x="3" y="4" width="18" height="13" rx="2" />
                                                    <path d="M8 21h8m-4-4v4" stroke-linecap="round" />
                                                </svg>
                                            </span>
                                            <div class="min-w-0">
                                                <a href="{{ route('assets.show', $asset) }}" class="block max-w-[250px] truncate font-bold text-slate-900 hover:text-emerald-700">
                                                    {{ $asset->name }}
                                                </a>
                                                <p class="mt-0.5 max-w-[250px] truncate font-mono text-[11px] text-slate-400">{{ $asset->hostname ?: $asset->asset_code }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <p class="max-w-[180px] truncate font-medium text-slate-700">{{ $asset->user?->name ?: 'Unassigned' }}</p>
                                        <p class="mt-0.5 max-w-[180px] truncate text-xs text-slate-400">{{ $asset->department?->name ?: 'No department' }}</p>
                                    </td>
                                    <td class="px-4 py-3 font-mono text-xs text-slate-600">{{ $asset->ip_address ?: '—' }}</td>
                                    <td class="px-4 py-3">
                                        <span
                                            data-status-badge="{{ $asset->id }}"
                                            class="inline-flex items-center gap-1.5 rounded-full border border-sky-200 bg-sky-50 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-sky-700"
                                        >
                                            <span data-status-dot class="h-1.5 w-1.5 animate-pulse rounded-full bg-sky-500"></span>
                                            <span data-status-text>Checking</span>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        @if ($hasRustdesk)
                                            <span class="inline-flex rounded-lg bg-emerald-50 px-2.5 py-1 font-mono text-xs font-bold text-emerald-700 ring-1 ring-emerald-200">
                                                {{ $asset->rustdesk_id }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 text-xs font-medium text-amber-600">
                                                <span class="h-1.5 w-1.5 rounded-full bg-amber-400"></span>
                                                Missing ID
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex justify-end">
                                            @if ($hasRustdesk)
                                                <a
                                                    href="{{ $rustdeskUriFor($asset) }}"
                                                    class="inline-flex h-9 items-center justify-center gap-2 rounded-xl bg-emerald-600 px-3.5 text-xs font-bold text-white shadow-sm transition hover:bg-emerald-700"
                                                >
                                                    Connect
                                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                        <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4M10 17l5-5-5-5m5 5H3" stroke-linecap="round" stroke-linejoin="round" />
                                                    </svg>
                                                </a>
                                            @else
                                                <a
                                                    href="{{ $editUrl }}"
                                                    class="inline-flex h-9 items-center justify-center rounded-xl border border-amber-200 bg-amber-50 px-3.5 text-xs font-bold text-amber-700 transition hover:bg-amber-100"
                                                >
                                                    Set RustDesk ID
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="grid gap-3 lg:hidden sm:grid-cols-2">
                @foreach ($assets as $asset)
                    @php
                        $hasRustdesk = filled($asset->rustdesk_id);
                        $editUrl = $asset->source_type === 'manual'
                            ? route('admin.assets.manual.edit', $asset)
                            : route('assets.edit', $asset);
                    @endphp
                    <article
                        data-remote-device
                        data-device-id="{{ $asset->id }}"
                        data-live-status="checking"
                        class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <a href="{{ route('assets.show', $asset) }}" class="block truncate text-sm font-bold text-slate-900">{{ $asset->name }}</a>
                                <p class="mt-0.5 truncate font-mono text-[11px] text-slate-400">{{ $asset->hostname ?: $asset->asset_code }}</p>
                            </div>
                            <span
                                data-status-badge="{{ $asset->id }}"
                                class="inline-flex shrink-0 items-center gap-1.5 rounded-full border border-sky-200 bg-sky-50 px-2 py-1 text-[9px] font-bold uppercase tracking-wider text-sky-700"
                            >
                                <span data-status-dot class="h-1.5 w-1.5 animate-pulse rounded-full bg-sky-500"></span>
                                <span data-status-text>Checking</span>
                            </span>
                        </div>

                        <dl class="mt-3 grid grid-cols-2 gap-2 text-xs">
                            <div class="rounded-xl bg-slate-50 px-3 py-2">
                                <dt class="text-[9px] font-bold uppercase tracking-wider text-slate-400">Department</dt>
                                <dd class="mt-1 truncate font-semibold text-slate-700">{{ $asset->department?->name ?: '—' }}</dd>
                            </div>
                            <div class="rounded-xl bg-slate-50 px-3 py-2">
                                <dt class="text-[9px] font-bold uppercase tracking-wider text-slate-400">IP Address</dt>
                                <dd class="mt-1 truncate font-mono font-semibold text-slate-700">{{ $asset->ip_address ?: '—' }}</dd>
                            </div>
                        </dl>

                        <div class="mt-3 flex items-center justify-between gap-3 border-t border-slate-100 pt-3">
                            <span class="truncate font-mono text-[11px] {{ $hasRustdesk ? 'text-emerald-700' : 'text-amber-600' }}">
                                {{ $hasRustdesk ? $asset->rustdesk_id : 'Missing RustDesk ID' }}
                            </span>
                            @if ($hasRustdesk)
                                <a href="{{ $rustdeskUriFor($asset) }}" class="inline-flex h-8 shrink-0 items-center rounded-lg bg-emerald-600 px-3 text-[11px] font-bold text-white">
                                    Connect
                                </a>
                            @else
                                <a href="{{ $editUrl }}" class="inline-flex h-8 shrink-0 items-center rounded-lg bg-amber-50 px-3 text-[11px] font-bold text-amber-700 ring-1 ring-amber-200">
                                    Set ID
                                </a>
                            @endif
                        </div>
                    </article>
                @endforeach
            </section>

            <div class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm sm:flex-row sm:items-center sm:justify-between">
                <p class="text-xs text-slate-500">Page {{ $assets->currentPage() }} of {{ $assets->lastPage() }}</p>
                <div>{{ $assets->onEachSide(1)->links() }}</div>
            </div>
        @else
            <section class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center shadow-sm">
                <span class="mx-auto inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                        <rect x="3" y="4" width="18" height="13" rx="2" />
                        <path d="M8 21h8m-4-4v4" stroke-linecap="round" />
                    </svg>
                </span>
                <h3 class="mt-4 text-base font-bold text-slate-800">No remote endpoints found</h3>
                <p class="mt-1 text-sm text-slate-500">Try changing the search or RustDesk configuration filter.</p>
                @if ($search !== '' || $connection !== 'all')
                    <a href="{{ route('remote-system.index') }}" class="mt-4 inline-flex h-9 items-center rounded-xl bg-slate-950 px-4 text-xs font-bold text-white">
                        Clear filters
                    </a>
                @endif
            </section>
        @endif
    </div>

    <script>
        function remoteSystemPage(config) {
            return {
                devices: config.devices || [],
                pingUrl: config.pingUrl || '',
                liveFilter: 'all',
                onlineCount: 0,
                offlineCount: 0,
                checkingCount: (config.devices || []).length,
                get visibleCount() {
                    if (this.liveFilter === 'online') return this.onlineCount;
                    if (this.liveFilter === 'offline') return this.offlineCount;
                    return this.devices.length;
                },
                init() {
                    this.devices.forEach((device) => this.checkDevice(device));
                },
                setLiveFilter(filter) {
                    this.liveFilter = filter;
                    this.applyLiveFilter();
                },
                applyLiveFilter() {
                    document.querySelectorAll('[data-remote-device]').forEach((element) => {
                        const visible = this.liveFilter === 'all'
                            || element.dataset.liveStatus === this.liveFilter;
                        element.classList.toggle('hidden', !visible);
                    });
                },
                async checkDevice(device) {
                    if (!device.ip) {
                        this.updateDeviceStatus(device.id, 'offline');
                        return;
                    }

                    try {
                        const target = new URL(this.pingUrl, window.location.origin);
                        target.searchParams.set('ip', device.ip);
                        const response = await fetch(target.toString(), {
                            headers: { 'Accept': 'application/json' },
                            credentials: 'same-origin',
                        });
                        const data = response.ok ? await response.json() : {};
                        this.updateDeviceStatus(device.id, data.status === 'online' ? 'online' : 'offline');
                    } catch (error) {
                        this.updateDeviceStatus(device.id, 'offline');
                    }
                },
                updateDeviceStatus(id, status) {
                    document.querySelectorAll(`[data-device-id="${id}"]`).forEach((element) => {
                        element.dataset.liveStatus = status;
                    });

                    document.querySelectorAll(`[data-status-badge="${id}"]`).forEach((badge) => {
                        const dot = badge.querySelector('[data-status-dot]');
                        const text = badge.querySelector('[data-status-text]');
                        badge.className = status === 'online'
                            ? 'inline-flex shrink-0 items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-emerald-700'
                            : 'inline-flex shrink-0 items-center gap-1.5 rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-slate-600';
                        dot.className = status === 'online'
                            ? 'h-1.5 w-1.5 rounded-full bg-emerald-500 shadow-[0_0_7px_rgba(16,185,129,.65)]'
                            : 'h-1.5 w-1.5 rounded-full bg-slate-400';
                        text.textContent = status;
                    });

                    if (status === 'online') {
                        this.onlineCount++;
                    } else {
                        this.offlineCount++;
                    }
                    this.checkingCount = Math.max(0, this.checkingCount - 1);
                    this.applyLiveFilter();
                },
            };
        }
    </script>
</x-app-layout>
