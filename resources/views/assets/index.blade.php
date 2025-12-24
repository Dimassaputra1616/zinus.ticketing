@php
    $statusMeta = [
        'in_use' => ['label' => 'Active', 'class' => 'bg-emerald-100 text-emerald-700'],
        'active' => ['label' => 'Active', 'class' => 'bg-emerald-100 text-emerald-700'],
        'maintenance' => ['label' => 'In Repair', 'class' => 'bg-amber-100 text-amber-700'],
        'in_repair' => ['label' => 'In Repair', 'class' => 'bg-amber-100 text-amber-700'],
        'available' => ['label' => 'Spare', 'class' => 'bg-sky-100 text-sky-700'],
        'spare' => ['label' => 'Spare', 'class' => 'bg-sky-100 text-sky-700'],
        'broken' => ['label' => 'Retired', 'class' => 'bg-rose-100 text-rose-700'],
        'retired' => ['label' => 'Retired', 'class' => 'bg-rose-100 text-rose-700'],
    ];

    $activeFilters = collect($filters ?? [])->filter(fn ($value) => filled($value));
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

    <div class="min-h-screen bg-slate-50 pb-12 pt-6">
        <div class="w-full space-y-6">
            <section class="rounded-3xl border border-slate-200/80 bg-gradient-to-br from-white via-white to-emerald-50/60 p-6 shadow-md shadow-emerald-500/10 lg:p-8">
                <div class="flex flex-col gap-6">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div class="space-y-2">
                            <p class="text-xs uppercase tracking-[0.35em] text-emerald-600/80">IT Asset Management</p>
                            <div class="flex flex-wrap items-center gap-3">
                                <h1 class="text-3xl font-semibold text-slate-900">Asset & Inventory Control</h1>
                                <span class="inline-flex items-center rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200/80">Showing {{ number_format($assets->total()) }} assets</span>
                            </div>
                            <p class="text-sm text-slate-600">Monitor ownership, location, lifecycle, and sync health in one modern console.</p>
                        </div>
                        <a
                            href="{{ route('assets.create') }}"
                            class="inline-flex items-center gap-2 rounded-full bg-emerald-600 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-emerald-500/30 transition hover:-translate-y-0.5 hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-200"
                        >
                            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-white/20 text-lg leading-none">+</span>
                            Add Asset
                        </a>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        @php
                            $cards = [
                                [
                                    'label' => 'Total Assets',
                                    'key' => 'total',
                                    'sublabel' => 'All registered devices',
                                    'icon' => 'collection',
                                    'emphasis' => true,
                                    'status' => '',
                                ],
                                [
                                    'label' => 'Active',
                                    'key' => 'active',
                                    'sublabel' => 'Currently in use',
                                    'icon' => 'bolt',
                                    'status' => 'active',
                                ],
                                [
                                    'label' => 'In Repair',
                                    'key' => 'in_repair',
                                    'sublabel' => 'Under maintenance',
                                    'icon' => 'wrench',
                                    'status' => 'in_repair',
                                ],
                                [
                                    'label' => 'Spare',
                                    'key' => 'spare',
                                    'sublabel' => 'Standby stock',
                                    'icon' => 'stack',
                                    'status' => 'spare',
                                ],
                                [
                                    'label' => 'Retired',
                                    'key' => 'retired',
                                    'sublabel' => 'No longer in service',
                                    'icon' => 'archive',
                                    'status' => 'retired',
                                ],
                            ];
                            $statusFilter = Str::of(request('status'))->snake()->lower()->toString();
                        @endphp
                        @foreach ($cards as $card)
                            @php
                                $isTotal = $card['emphasis'] ?? false;
                                $isActive = $card['status'] === '' ? ! filled($statusFilter) : $statusFilter === $card['status'];
                                $cardClasses = $isActive
                                    ? 'bg-emerald-600 text-white ring-1 ring-emerald-500/30 shadow-lg shadow-emerald-500/30 border border-transparent'
                                    : ($isTotal
                                        ? 'bg-gradient-to-br from-emerald-800 to-emerald-600 text-white ring-1 ring-emerald-500/30 shadow-lg shadow-emerald-500/30'
                                        : 'bg-white ring-1 ring-slate-100 text-slate-900 shadow-sm shadow-slate-200/70');
                                $queryParams = request()->except('page');
                                if ($card['status'] === '') {
                                    unset($queryParams['status']);
                                } else {
                                    $queryParams['status'] = $card['status'];
                                }
                            @endphp
                            <a
                                href="{{ route('assets.index', $queryParams) }}"
                                class="{{ $cardClasses }} rounded-2xl p-4 transition hover:-translate-y-0.5 hover:shadow-lg cursor-pointer block"
                                data-status-card="{{ $card['status'] ?? '' }}"
                            >
                                <div class="flex items-center gap-2 text-[11px] uppercase tracking-[0.3em] {{ ($isActive || $isTotal) ? 'text-emerald-50' : 'text-slate-500' }}">
                                    <span class="flex h-8 w-8 items-center justify-center rounded-xl {{ ($isActive || $isTotal) ? 'bg-white/10 text-white shadow-inner shadow-emerald-900/20' : 'bg-slate-100 text-slate-600 shadow-inner shadow-slate-100' }}">
                                        @switch($card['icon'])
                                            @case('collection')
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                                    <rect x="3" y="3" width="8" height="8" rx="2" />
                                                    <rect x="13" y="3" width="8" height="8" rx="2" />
                                                    <rect x="3" y="13" width="8" height="8" rx="2" />
                                                    <rect x="13" y="13" width="8" height="8" rx="2" />
                                                </svg>
                                                @break
                                            @case('bolt')
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M13.5 2 5 14.5h6.5L10 22l9-12h-6.5L13.5 2Z" />
                                                </svg>
                                                @break
                                            @case('wrench')
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M21 13a6 6 0 0 1-7.44-7.44L9.5 9.62a2 2 0 0 0-.5 1.31V12l-6 6a2.83 2.83 0 1 0 4 4l6-6h1.07a2 2 0 0 0 1.31-.5l4.06-4.06Z" />
                                                    <path d="M16 5h0" />
                                                </svg>
                                                @break
                                            @case('stack')
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="m12 2 9 5-9 5-9-5 9-5Z" />
                                                    <path d="m3 12 9 5 9-5" />
                                                    <path d="m3 17 9 5 9-5" />
                                                </svg>
                                                @break
                                            @case('archive')
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                                    <rect x="3" y="4" width="18" height="5" rx="1" />
                                                    <path d="M5 9v9a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V9" />
                                                    <path d="M9 13h6" />
                                                </svg>
                                                @break
                                        @endswitch
                                    </span>
                                    <span>{{ $card['label'] }}</span>
                                </div>
                                <p class="{{ ($isActive || $isTotal) ? 'mt-3 text-3xl font-semibold text-white' : 'mt-2 text-2xl font-semibold text-slate-900' }}">{{ number_format($stats[$card['key']] ?? 0) }}</p>
                                <p class="{{ ($isActive || $isTotal) ? 'mt-1 text-xs text-emerald-100/90' : 'mt-1 text-xs text-slate-500' }}">{{ $card['sublabel'] }}</p>
                            </a>
                        @endforeach
                    </div>
                </div>
            </section>

            <form
                method="GET"
                x-data="{ advancedOpen: false }"
                class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm shadow-slate-200/60"
                id="asset-filter-form"
            >
                <div class="space-y-4">
                    <div class="space-y-1">
                        <label class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-500">Search</label>
                        <div class="relative">
                            <input
                                type="search"
                                name="search"
                                value="{{ $filters['search'] ?? '' }}"
                                placeholder="Search asset code, serial, user..."
                                class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 pl-11 text-sm text-slate-800 shadow-inner shadow-slate-100 placeholder:text-slate-400 focus:border-emerald-300 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-100"
                                id="asset-search-input"
                            >
                            <svg class="absolute left-3 top-3 h-5 w-5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="8" />
                                <path d="m21 21-4.35-4.35" />
                            </svg>
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                        <div class="space-y-1">
                            <label class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-500">Factory</label>
                            <div class="relative">
                                <select
                                    name="factory"
                                    class="block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 shadow-inner shadow-slate-100 focus:border-emerald-300 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-100"
                                >
                                    <option value="">All Factories</option>
                                    @foreach ($filterOptions['factories'] as $factory)
                                        <option value="{{ $factory }}" @selected(($filters['factory'] ?? null) === $factory)>{{ $factory }}</option>
                                    @endforeach
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-slate-400">
                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.25 8.29a.75.75 0 01-.02-1.06z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-1">
                            <label class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-500">Department</label>
                            <div class="relative">
                                <select
                                    name="department"
                                    class="block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 shadow-inner shadow-slate-100 focus:border-emerald-300 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-100"
                                >
                                    <option value="">All Departments</option>
                                    @foreach ($departments as $department)
                                        <option value="{{ $department->id }}" @selected(($filters['department'] ?? null) == $department->id)>{{ $department->name }}</option>
                                    @endforeach
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-slate-400">
                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.25 8.29a.75.75 0 01-.02-1.06z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-1">
                            <label class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-500">Category</label>
                            <div class="relative">
                                <select
                                    name="category"
                                    class="block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 shadow-inner shadow-slate-100 focus:border-emerald-300 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-100"
                                >
                                    <option value="">All Categories</option>
                                    @foreach ($filterOptions['categories'] as $category)
                                        <option value="{{ $category }}" @selected(($filters['category'] ?? null) === $category)>{{ $category }}</option>
                                    @endforeach
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-slate-400">
                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.25 8.29a.75.75 0 01-.02-1.06z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-1">
                            <label class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-500">Status</label>
                            <div class="relative">
                                <select
                                    name="status"
                                    class="block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 shadow-inner shadow-slate-100 focus:border-emerald-300 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-100"
                                >
                                    <option value="">All Status</option>
                                    @foreach ($filterOptions['statuses'] as $status)
                                        <option value="{{ $status }}" @selected(($filters['status'] ?? null) === $status)>{{ Str::of($status)->replace('_', ' ')->title() }}</option>
                                    @endforeach
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-slate-400">
                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.25 8.29a.75.75 0 01-.02-1.06z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-end">
                            <button
                                type="submit"
                                class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white shadow-md shadow-emerald-500/30 transition hover:-translate-y-0.5 hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-200 disabled:opacity-75"
                            >
                                <span>Apply Filters</span>
                            </button>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex flex-wrap items-center gap-2 text-xs font-semibold text-slate-600">
                            <span>Active filters: {{ $activeFilters->count() }}</span>
                            <span class="text-slate-300">|</span>
                            <a href="{{ route('assets.index') }}" class="text-slate-500 hover:text-emerald-700">Clear</a>
                        </div>
                        <button
                            type="button"
                            class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold uppercase tracking-[0.2em] text-slate-600 transition hover:border-emerald-200 hover:text-emerald-700"
                            @click="advancedOpen = !advancedOpen"
                        >
                            Advanced Filters
                            <svg
                                class="h-3.5 w-3.5 transition"
                                :class="{ 'rotate-180': advancedOpen }"
                                viewBox="0 0 20 20"
                                fill="currentColor"
                            >
                                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.25 8.29a.75.75 0 01-.02-1.06z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>

                    <div
                        x-show="advancedOpen"
                        x-transition
                        x-cloak
                        class="rounded-2xl border border-dashed border-slate-200 bg-slate-50/70 p-3"
                    >
                        @if ($activeFilters->isNotEmpty())
                            <div class="flex flex-wrap gap-2">
                                <span class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-500">Active:</span>
                                @foreach ($activeFilters as $key => $value)
                                    <span class="inline-flex items-center gap-2 rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-700 ring-1 ring-slate-200">
                                        {{ Str::of($key)->replace('_', ' ')->title() }}: {{ $value }}
                                    </span>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-slate-500">No advanced filters applied yet.</p>
                        @endif
                    </div>
                </div>
            </form>

            <section id="asset-list-container" class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-md shadow-slate-200/80 transition-shadow duration-150 hover:shadow-lg">
                @php
                    $hasAgentSync = $assets->getCollection()->contains(fn ($asset) => $asset->sync_source === 'agent');
                @endphp
                <div class="flex flex-col gap-3 border-b border-slate-100 px-4 py-4 sm:px-5 sm:py-5 md:flex-row md:items-center md:justify-between">
                    <div>
                        <p class="text-xs uppercase tracking-[0.35em] text-emerald-600/80">Live Inventory</p>
                        <div class="flex flex-wrap items-center gap-3">
                            <h2 class="text-lg font-semibold text-slate-900">IT Asset List</h2>
                            <span class="inline-flex items-center rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-100">
                                {{ $hasAgentSync ? 'Updated in real time' : 'Manual records' }}
                            </span>
                        </div>
                        <p class="text-sm text-slate-600">Full-width table with responsive columns for factory, category, lifecycle, and assignments.</p>
                    </div>
                    <div class="flex items-center gap-2 text-xs font-semibold text-slate-500">
                        <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-3 py-1 ring-1 ring-slate-200">Filters ready</span>
                    </div>
                </div>

                <div class="md:hidden space-y-3 px-4 pb-4">
                    @forelse ($assets as $asset)
                        @php
                            $rawStatus = $asset->status ?? 'unknown';
                            $statusKey = Str::of($rawStatus)->lower()->replace(' ', '_')->toString();
                            $statusInfo = $statusMeta[$statusKey] ?? [
                                'label' => Str::of($rawStatus)->replace('_', ' ')->title(),
                                'class' => 'bg-slate-100 text-slate-600 ring-1 ring-slate-200',
                            ];
                            $statusLabel = $statusInfo['label'];
                            $statusTone = $statusInfo['class'];
                        @endphp
                        <article class="rounded-2xl border border-slate-100 bg-white p-4 shadow-sm">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-[10px] font-semibold uppercase tracking-[0.24em] text-slate-400">Asset Code</p>
                                    <p class="text-sm font-semibold text-slate-900 truncate">{{ $asset->asset_code }}</p>
                                    <p class="text-xs text-slate-500 truncate">{{ $asset->brand ?? '-' }} • {{ $asset->model ?? ($asset->name ?? '-') }}</p>
                                </div>
                                <span class="inline-flex items-center rounded-full px-3 py-1 text-[10px] font-semibold uppercase tracking-[0.25em] {{ $statusTone }}">
                                    {{ $statusLabel }}
                                </span>
                            </div>

                            <dl class="mt-3 grid grid-cols-2 gap-3 text-xs text-slate-600">
                                <div class="space-y-1">
                                    <dt class="font-semibold text-slate-500">Factory</dt>
                                    <dd class="text-slate-800">{{ $asset->factory ?? $asset->location ?? '-' }}</dd>
                                </div>
                                <div class="space-y-1">
                                    <dt class="font-semibold text-slate-500">Department</dt>
                                    <dd class="text-slate-800">{{ $asset->department->name ?? '-' }}</dd>
                                </div>
                                <div class="space-y-1">
                                    <dt class="font-semibold text-slate-500">Category</dt>
                                    <dd class="text-slate-800">{{ $asset->category ?? ($filters['category'] ?? '-') }}</dd>
                                </div>
                                <div class="space-y-1">
                                    <dt class="font-semibold text-slate-500">Serial</dt>
                                    <dd class="text-slate-800">{{ $asset->serial_number ?? '-' }}</dd>
                                </div>
                                <div class="space-y-1">
                                    <dt class="font-semibold text-slate-500">Assigned</dt>
                                    <dd class="text-slate-800">{{ $asset->user->name ?? 'Unassigned' }}</dd>
                                </div>
                                <div class="space-y-1">
                                    <dt class="font-semibold text-slate-500">Location</dt>
                                    <dd class="text-slate-800">{{ $asset->location ?? '-' }}</dd>
                                </div>
                                <div class="space-y-1">
                                    <dt class="font-semibold text-slate-500">Sync Source</dt>
                                    <dd class="text-slate-800">{{ $asset->sync_source === 'agent' ? 'Agent' : 'Manual' }}</dd>
                                </div>
                                <div class="space-y-1">
                                    <dt class="font-semibold text-slate-500">Last Synced</dt>
                                    <dd class="text-slate-800">{{ $asset->last_synced_at ? $asset->last_synced_at->diffForHumans() : 'Never' }}</dd>
                                </div>
                            </dl>

                            <div class="mt-3 flex flex-wrap items-center gap-2">
                                <a
                                    href="{{ route('assets.show', $asset) }}"
                                    class="inline-flex items-center rounded-full border border-slate-200 px-3 py-1 text-xs font-semibold text-slate-700 hover:border-emerald-200 hover:text-emerald-700"
                                >
                                    View
                                </a>
                                <a
                                    href="{{ route('assets.edit', $asset) }}"
                                    class="inline-flex items-center rounded-full bg-emerald-600 px-3 py-1 text-xs font-semibold text-white shadow-sm shadow-emerald-400/30 hover:bg-emerald-700"
                                >
                                    Edit
                                </a>
                                <div class="relative" x-data="{ open: false }">
                                    <button
                                        type="button"
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-600 shadow-sm shadow-slate-200/60 transition hover:border-emerald-200 hover:text-emerald-700"
                                        @click.stop="open = !open"
                                        @keydown.escape.window="open = false"
                                        @click.away="open = false"
                                    >
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                            <circle cx="12" cy="6" r="1.5" />
                                            <circle cx="12" cy="12" r="1.5" />
                                            <circle cx="12" cy="18" r="1.5" />
                                        </svg>
                                    </button>
                                    <div
                                        x-show="open"
                                        x-transition
                                        x-cloak
                                        class="absolute right-0 z-20 mt-2 w-36 overflow-hidden rounded-xl border border-slate-200 bg-white text-left shadow-lg shadow-slate-200"
                                    >
                                        <form
                                            method="POST"
                                            action="{{ route('assets.destroy', $asset) }}"
                                            class="delete-asset-form"
                                        >
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                type="button"
                                                data-asset="{{ $asset->asset_code }}"
                                                class="delete-asset-btn flex w-full items-center gap-2 px-4 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-50"
                                                @click.stop="open = false"
                                            >
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M3 6h18" />
                                                    <path d="M9 6V4.5A1.5 1.5 0 0 1 10.5 3h3A1.5 1.5 0 0 1 15 4.5V6m2 0v14a1 1 0 0 1-1 1H8a1 1 0 0 1-1-1V6h10Z" />
                                                    <path d="M10 11v6" />
                                                    <path d="M14 11v6" />
                                                </svg>
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="px-3 py-4 text-center text-sm text-slate-500">Belum ada asset ditemukan.</div>
                    @endforelse
                </div>

                <div class="hidden md:block w-full overflow-x-auto px-3 sm:px-4 lg:px-4">
                    <table class="w-full border-separate border-spacing-y-2">
                        <thead>
                            <tr class="text-left text-xs font-semibold tracking-[0.15em] text-slate-500">
                                <th class="bg-slate-50 px-3 py-3 w-28">Asset Code</th>
                                <th class="bg-slate-50 px-3 py-3">Factory</th>
                                <th class="bg-slate-50 px-3 py-3 w-32">Department</th>
                                <th class="bg-slate-50 px-3 py-3 w-28">Category</th>
                                <th class="bg-slate-50 px-3 py-3 w-32">Brand</th>
                                <th class="bg-slate-50 px-3 py-3">Model</th>
                                <th class="bg-slate-50 px-3 py-3 w-36">Serial Number</th>
                                <th class="bg-slate-50 px-3 py-3 w-24">Status</th>
                                <th class="bg-slate-50 px-3 py-3 w-28">Sync Source</th>
                                <th class="bg-slate-50 px-3 py-3 w-36">Last Synced</th>
                                <th class="bg-slate-50 px-3 py-3 w-40">Assigned To</th>
                                <th class="bg-slate-50 px-3 py-3">Location</th>
                                <th class="bg-slate-50 px-3 py-3 text-right w-[160px] whitespace-nowrap">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($assets as $asset)
                                @php
                                    $rawStatus = $asset->status ?? 'unknown';
                                    $statusKey = Str::of($rawStatus)->lower()->replace(' ', '_')->toString();
                                    $statusInfo = $statusMeta[$statusKey] ?? [
                                        'label' => Str::of($rawStatus)->replace('_', ' ')->title(),
                                        'class' => 'bg-slate-100 text-slate-600 ring-1 ring-slate-200',
                                    ];
                                    $statusLabel = $statusInfo['label'];
                                    $statusTone = $statusInfo['class'];
                                @endphp
                                <tr
                                    class="table-hover-row rounded-2xl border border-transparent bg-white shadow-sm shadow-slate-200/60 transition duration-150 cursor-pointer hover:bg-emerald-50"
                                    onclick="window.location='{{ route('assets.show', $asset) }}'"
                                >
                                    <td class="whitespace-nowrap px-3 py-2.5 text-sm font-semibold text-slate-900">
                                        <span class="rounded-full bg-emerald-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.25em] text-emerald-700 ring-1 ring-emerald-100">{{ $asset->asset_code }}</span>
                                    </td>
                                    <td class="px-3 py-2.5 text-sm text-slate-800 break-words">{{ $asset->factory ?? $asset->location ?? '-' }}</td>
                                    <td class="px-3 py-2.5 text-sm text-slate-800 break-words">{{ $asset->department->name ?? '-' }}</td>
                                    <td class="px-3 py-2.5 text-sm text-slate-800 break-words">{{ $asset->category ?? ($filters['category'] ?? '-') }}</td>
                                    <td class="px-3 py-2.5 text-sm text-slate-800 break-words">{{ $asset->brand ?? '-' }}</td>
                                    <td class="px-3 py-2.5 text-sm text-slate-800 break-words">{{ $asset->model ?? ($asset->name ?? '-') }}</td>
                                    <td class="px-3 py-2.5 text-sm text-slate-800 break-words">{{ $asset->serial_number ?? '-' }}</td>
                                    <td class="whitespace-nowrap px-3 py-2.5 w-24">
                                        <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.25em] {{ $statusTone }}">
                                            {{ $statusLabel }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2.5 text-sm text-slate-800 break-words">
                                        @if ($asset->sync_source === 'agent')
                                            <span class="badge badge-soft-success text-uppercase text-xs">Agent</span>
                                        @else
                                            <span class="badge badge-soft-secondary text-uppercase text-xs">Manual</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2.5 text-sm text-slate-800 break-words">
                                        @if ($asset->last_synced_at)
                                            <span class="badge badge-soft-primary text-xs">
                                                {{ $asset->last_synced_at->diffForHumans() }}
                                            </span>
                                        @else
                                            <span class="badge badge-soft-light text-xs">Never</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2.5 text-sm text-slate-800 break-words">{{ $asset->user->name ?? 'Unassigned' }}</td>
                                    <td class="px-3 py-2.5 text-sm text-slate-800 break-words">{{ $asset->location ?? '-' }}</td>
                                    <td class="whitespace-nowrap px-3 py-2.5 text-right text-sm w-[160px]">
                                        <div class="flex items-center justify-end gap-2">
                                            <a
                                                href="{{ route('assets.show', $asset) }}"
                                                onclick="event.stopPropagation()"
                                                class="inline-flex items-center rounded-full border border-slate-200 px-3 py-1 text-xs font-semibold text-slate-700 hover:border-emerald-200 hover:text-emerald-700"
                                            >
                                                View
                                            </a>
                                            <a
                                                href="{{ route('assets.edit', $asset) }}"
                                                onclick="event.stopPropagation()"
                                                class="inline-flex items-center rounded-full bg-emerald-600 px-3 py-1 text-xs font-semibold text-white shadow-sm shadow-emerald-400/30 hover:bg-emerald-700"
                                            >
                                                Edit
                                            </a>
                                            <div class="relative" x-data="{ open: false }">
                                                <button
                                                    type="button"
                                                    class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-600 shadow-sm shadow-slate-200/60 transition hover:border-emerald-200 hover:text-emerald-700"
                                                    @click.stop="open = !open"
                                                    @keydown.escape.window="open = false"
                                                    @click.away="open = false"
                                                >
                                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                        <circle cx="12" cy="6" r="1.5" />
                                                        <circle cx="12" cy="12" r="1.5" />
                                                        <circle cx="12" cy="18" r="1.5" />
                                                    </svg>
                                                </button>
                                                <div
                                                    x-show="open"
                                                    x-transition
                                                    x-cloak
                                                    class="absolute right-0 z-20 mt-2 w-36 overflow-hidden rounded-xl border border-slate-200 bg-white text-left shadow-lg shadow-slate-200"
                                                >
                                                    <form
                                                        method="POST"
                                                        action="{{ route('assets.destroy', $asset) }}"
                                                        class="delete-asset-form"
                                                    >
                                                        @csrf
                                                        @method('DELETE')
                                                        <button
                                                            type="button"
                                                            data-asset="{{ $asset->asset_code }}"
                                                            class="delete-asset-btn flex w-full items-center gap-2 px-4 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-50"
                                                            @click.stop="open = false"
                                                        >
                                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                                                <path d="M3 6h18" />
                                                                <path d="M9 6V4.5A1.5 1.5 0 0 1 10.5 3h3A1.5 1.5 0 0 1 15 4.5V6m2 0v14a1 1 0 0 1-1 1H8a1 1 0 0 1-1-1V6h10Z" />
                                                                <path d="M10 11v6" />
                                                                <path d="M14 11v6" />
                                                            </svg>
                                                            Delete
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="13" class="px-4 py-8">
                                        <div class="mx-auto flex max-w-lg flex-col items-center rounded-2xl border border-dashed border-slate-200 bg-slate-50/80 p-6 text-center shadow-sm">
                                            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 shadow-inner shadow-emerald-100">
                                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                                    <circle cx="12" cy="12" r="10" />
                                                    <path d="M8 12h8" />
                                                    <path d="M12 8v8" />
                                                </svg>
                                            </div>
                                            <p class="mt-3 text-sm font-semibold text-slate-800">Belum ada asset yang cocok dengan filter.</p>
                                            <p class="mt-1 text-sm text-slate-600">Tambah asset baru atau ubah filter pencarian.</p>
                                            <a
                                                href="{{ route('assets.create') }}"
                                                class="mt-4 inline-flex items-center gap-2 rounded-full bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-md shadow-emerald-400/30 transition hover:-translate-y-0.5 hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-200"
                                            >
                                                Tambah Asset
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="flex flex-col gap-3 border-t border-slate-100 px-6 py-4 md:flex-row md:items-center md:justify-between">
                    <div class="text-sm text-slate-600">
                        Showing <span class="font-semibold text-slate-800">{{ $assets->firstItem() ?? 0 }}</span>&ndash;<span class="font-semibold text-slate-800">{{ $assets->lastItem() ?? 0 }}</span> of <span class="font-semibold text-slate-800">{{ $assets->total() }}</span> assets
                    </div>
                    <div class="flex items-center gap-4">
                        <form method="GET" class="flex items-center gap-2">
                            <label for="per_page" class="text-sm text-slate-600">Per page</label>
                            <select
                                id="per_page"
                                name="per_page"
                                class="h-10 rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700 focus:border-emerald-300 focus:outline-none focus:ring-2 focus:ring-emerald-100"
                                onchange="this.form.submit()"
                            >
                                @foreach ([10, 25, 50, 100] as $size)
                                    <option value="{{ $size }}" @selected($perPage == $size)>{{ $size }}</option>
                                @endforeach
                            </select>
                            @foreach ($filters as $key => $value)
                                @if (filled($value))
                                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                @endif
                            @endforeach
                        </form>
                        <div>
                            {{ $assets->onEachSide(1)->links() }}
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
    <div id="delete-modal" class="fixed inset-0 z-40 hidden items-center justify-center bg-slate-900/50 px-4">
        <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl shadow-slate-900/20">
            <h3 class="text-lg font-semibold text-slate-900">Hapus Asset?</h3>
            <p class="mt-2 text-sm text-slate-600">Anda yakin ingin menghapus asset <span id="delete-asset-name" class="font-semibold text-slate-900"></span>? Tindakan ini dapat dibatalkan melalui restore.</p>
            <div class="mt-4 flex items-center justify-end gap-3">
                <button id="delete-cancel" type="button" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:border-emerald-200 hover:text-emerald-700">Batal</button>
                <button id="delete-confirm" type="button" class="rounded-xl bg-rose-600 px-4 py-2 text-sm font-semibold text-white shadow-md shadow-rose-400/30 hover:bg-rose-700">Hapus</button>
            </div>
        </div>
    </div>
    <script>
        const AssetUI = (() => {
            const modal = document.getElementById('delete-modal');
            const nameEl = document.getElementById('delete-asset-name');
            const cancelBtn = document.getElementById('delete-cancel');
            const confirmBtn = document.getElementById('delete-confirm');
            const filterForm = document.getElementById('asset-filter-form');
            let targetForm = null;

            function openModal(form, assetName) {
                targetForm = form;
                nameEl.textContent = assetName || 'ini';
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }

            function closeModal() {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                targetForm = null;
            }

            function bindDeleteButtons(scope = document) {
                scope.querySelectorAll('.delete-asset-btn').forEach((btn) => {
                    btn.addEventListener('click', (event) => {
                        event.stopPropagation();
                        const form = btn.closest('form');
                        const assetName = btn.dataset.asset || 'ini';
                        openModal(form, assetName);
                    });
                });
            }

            function submitFilters() {
                if (filterForm?.requestSubmit) {
                    filterForm.requestSubmit();
                } else {
                    filterForm?.submit();
                }
            }

            function bindStatusCards() {
                const cards = document.querySelectorAll('[data-status-card]');
                const statusSelect = filterForm?.querySelector('select[name="status"]');
                if (!filterForm || !statusSelect || !cards.length) return;

                cards.forEach((card) => {
                    card.addEventListener('click', (e) => {
                        e.preventDefault();
                        statusSelect.value = card.dataset.statusCard ?? '';
                        submitFilters();
                    });
                });
            }

            function bindLiveSearch() {
                const searchInput = document.getElementById('asset-search-input');
                if (!searchInput || !filterForm) return;

                let timer;
                const debounce = (fn, delay = 400) => (...args) => {
                    clearTimeout(timer);
                    timer = setTimeout(() => fn(...args), delay);
                };

                const updateList = debounce(() => {
                    const value = searchInput.value.trim();
                    if (value.length > 0 && value.length < 3) return;
                    submitFilters();
                }, 700);

                searchInput.addEventListener('input', updateList);
            }

            cancelBtn?.addEventListener('click', (event) => {
                event.stopPropagation();
                closeModal();
            });

            confirmBtn?.addEventListener('click', (event) => {
                event.stopPropagation();
                if (targetForm) {
                    targetForm.submit();
                }
                closeModal();
            });

            modal?.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closeModal();
                }
            });

            // initial binds
            bindDeleteButtons();
            bindStatusCards();
            bindLiveSearch();

            return { bindDeleteButtons };
        })();
    </script>
</x-app-layout>
