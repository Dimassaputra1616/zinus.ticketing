@php
    $statusMeta = [
        'in_use' => ['label' => 'Active', 'class' => 'bg-emerald-50 text-emerald-700 ring-emerald-200'],
        'active' => ['label' => 'Active', 'class' => 'bg-emerald-50 text-emerald-700 ring-emerald-200'],
        'maintenance' => ['label' => 'In Repair', 'class' => 'bg-amber-50 text-amber-700 ring-amber-200'],
        'in_repair' => ['label' => 'In Repair', 'class' => 'bg-amber-50 text-amber-700 ring-amber-200'],
        'available' => ['label' => 'Spare', 'class' => 'bg-sky-50 text-sky-700 ring-sky-200'],
        'spare' => ['label' => 'Spare', 'class' => 'bg-sky-50 text-sky-700 ring-sky-200'],
        'broken' => ['label' => 'Retired', 'class' => 'bg-rose-50 text-rose-700 ring-rose-200'],
        'retired' => ['label' => 'Retired', 'class' => 'bg-rose-50 text-rose-700 ring-rose-200'],
    ];
    $activeFilterCount = collect($filters)->filter(fn ($value) => filled($value))->count();
@endphp

<section id="asset-inventory" class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-sm">
    <div class="flex flex-col gap-3 border-b border-slate-100 px-4 py-4 sm:flex-row sm:items-center sm:justify-between lg:px-6">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-[10px] font-bold uppercase tracking-[0.3em] text-emerald-600">Live Inventory</span>
                <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-100">
                    {{ number_format($assets->total()) }} assets
                </span>
            </div>
            <h2 class="mt-1 text-xl font-bold tracking-tight text-slate-900">IT Asset Inventory</h2>
            <p class="mt-0.5 text-xs text-slate-500 sm:text-sm">Agent and manually registered assets in one operational view.</p>
        </div>
        <a href="{{ route('assets.create') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-emerald-500/20 transition hover:-translate-y-0.5 hover:bg-emerald-700">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                <path d="M12 5v14M5 12h14" />
            </svg>
            Add Asset
        </a>
    </div>

    <form method="GET" action="{{ route('assets.index') }}" class="border-b border-slate-100 bg-slate-50/60 p-4 lg:px-6">
        <div class="flex flex-col gap-2.5 sm:flex-row">
            <label class="relative min-w-0 flex-1">
                <span class="sr-only">Search assets</span>
                <svg class="pointer-events-none absolute left-3.5 top-2.5 h-5 w-5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <circle cx="11" cy="11" r="7" />
                    <path d="m20 20-3.5-3.5" />
                </svg>
                <input
                    type="search"
                    name="search"
                    value="{{ $filters['search'] ?? '' }}"
                    placeholder="Search code, hostname, serial, user..."
                    class="h-10 w-full rounded-xl border-slate-200 bg-white pl-10 pr-4 text-sm text-slate-800 shadow-sm placeholder:text-slate-400 focus:border-emerald-400 focus:ring-emerald-100"
                >
            </label>
            <button type="submit" class="hidden h-10 items-center justify-center rounded-xl bg-emerald-600 px-5 text-sm font-semibold text-white shadow-md shadow-emerald-500/20 transition hover:bg-emerald-700 sm:inline-flex">
                Apply Filters
            </button>
        </div>
        <div class="mt-2.5 grid gap-2.5 sm:grid-cols-2 lg:grid-cols-4">
            <select name="factory" class="h-10 rounded-xl border-slate-200 bg-white text-sm text-slate-700 shadow-sm focus:border-emerald-400 focus:ring-emerald-100">
                <option value="">All Factories</option>
                @foreach ($filterFactories as $factory)
                    <option value="{{ $factory }}" @selected(($filters['factory'] ?? null) === $factory)>{{ $factory }}</option>
                @endforeach
            </select>
            <select name="department" class="h-10 rounded-xl border-slate-200 bg-white text-sm text-slate-700 shadow-sm focus:border-emerald-400 focus:ring-emerald-100">
                <option value="">All Departments</option>
                @foreach ($filterDepartments as $department)
                    <option value="{{ $department->id }}" @selected(($filters['department'] ?? null) == $department->id)>{{ $department->name }}</option>
                @endforeach
            </select>
            <select name="category" class="h-10 rounded-xl border-slate-200 bg-white text-sm text-slate-700 shadow-sm focus:border-emerald-400 focus:ring-emerald-100">
                <option value="">All Categories</option>
                @foreach ($filterCategories as $category)
                    <option value="{{ $category }}" @selected(($filters['category'] ?? null) === $category)>{{ $category }}</option>
                @endforeach
            </select>
            <select name="status" class="h-10 rounded-xl border-slate-200 bg-white text-sm text-slate-700 shadow-sm focus:border-emerald-400 focus:ring-emerald-100">
                <option value="">All Statuses</option>
                <option value="active" @selected(($filters['status'] ?? null) === 'active')>Active</option>
                <option value="in_repair" @selected(($filters['status'] ?? null) === 'in_repair')>In Repair</option>
                <option value="spare" @selected(($filters['status'] ?? null) === 'spare')>Spare</option>
                <option value="retired" @selected(($filters['status'] ?? null) === 'retired')>Retired</option>
            </select>
        </div>
        <button type="submit" class="mt-2.5 inline-flex h-10 w-full items-center justify-center rounded-xl bg-emerald-600 px-5 text-sm font-semibold text-white shadow-md shadow-emerald-500/20 transition hover:bg-emerald-700 sm:hidden">
            Apply Filters
        </button>

        <div class="mt-2.5 flex items-center justify-between gap-3 text-xs">
            <span class="font-medium text-slate-500">{{ $activeFilterCount }} active {{ Str::plural('filter', $activeFilterCount) }}</span>
            @if ($activeFilterCount > 0)
                <a href="{{ route('assets.index') }}#asset-inventory" class="font-semibold text-emerald-700 hover:text-emerald-800">Clear filters</a>
            @endif
        </div>
    </form>

    <div class="space-y-3 p-4 md:hidden">
        @forelse ($assets as $asset)
            @php
                $statusKey = Str::of($asset->status ?? 'unknown')->lower()->replace(' ', '_')->toString();
                $status = $statusMeta[$statusKey] ?? [
                    'label' => Str::of($asset->status ?? 'Unknown')->replace('_', ' ')->title(),
                    'class' => 'bg-slate-100 text-slate-600 ring-slate-200',
                ];
                $source = Str::lower($asset->source_type ?? $asset->sync_source ?? 'manual');
            @endphp
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <a href="{{ route('assets.show', ['asset' => $asset, 'return_to' => request()->fullUrl()]) }}" class="block truncate text-sm font-bold text-slate-900 hover:text-emerald-700">
                            {{ $asset->hostname ?? $asset->name ?? $asset->asset_code }}
                        </a>
                        <p class="mt-0.5 truncate font-mono text-[11px] text-slate-400">{{ $asset->asset_code }}</p>
                    </div>
                    <span class="inline-flex shrink-0 rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider ring-1 {{ $status['class'] }}">
                        {{ $status['label'] }}
                    </span>
                </div>
                <div class="mt-4 grid grid-cols-2 gap-3 text-xs">
                    <div>
                        <p class="text-slate-400">Category</p>
                        <p class="mt-0.5 font-semibold text-slate-700">{{ $asset->category ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-slate-400">Source</p>
                        <p class="mt-0.5 font-semibold text-slate-700">{{ $source === 'agent' ? 'Agent' : 'Manual' }}</p>
                    </div>
                    <div>
                        <p class="text-slate-400">Factory</p>
                        <p class="mt-0.5 font-semibold text-slate-700">{{ $asset->factory ?? $asset->location ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-slate-400">Assigned to</p>
                        <p class="mt-0.5 font-semibold text-slate-700">{{ $asset->assigned_to_display_name ?? 'Unassigned' }}</p>
                    </div>
                </div>
                <div class="mt-4 flex items-center gap-2 border-t border-slate-100 pt-3">
                    <a href="{{ route('assets.show', ['asset' => $asset, 'return_to' => request()->fullUrl()]) }}" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:border-emerald-200 hover:text-emerald-700">View</a>
                    <a href="{{ route('assets.edit', ['asset' => $asset, 'return_to' => request()->fullUrl()]) }}" class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700">Edit</a>
                </div>
            </article>
        @empty
            <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-5 py-10 text-center text-sm text-slate-500">
                No assets match the selected filters.
            </div>
        @endforelse
    </div>

    <div class="hidden overflow-x-auto md:block">
        <table class="min-w-full divide-y divide-slate-100">
            <thead class="bg-slate-50">
                <tr class="text-left text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500">
                    <th class="px-5 py-3.5">Asset</th>
                    <th class="px-4 py-3.5">Category</th>
                    <th class="px-4 py-3.5">Organization</th>
                    <th class="hidden px-4 py-3.5 2xl:table-cell">Device</th>
                    <th class="px-4 py-3.5">Status</th>
                    <th class="px-4 py-3.5">Assigned</th>
                    <th class="hidden px-4 py-3.5 2xl:table-cell">Updated</th>
                    <th class="px-5 py-3.5 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                @forelse ($assets as $asset)
                    @php
                        $statusKey = Str::of($asset->status ?? 'unknown')->lower()->replace(' ', '_')->toString();
                        $status = $statusMeta[$statusKey] ?? [
                            'label' => Str::of($asset->status ?? 'Unknown')->replace('_', ' ')->title(),
                            'class' => 'bg-slate-100 text-slate-600 ring-slate-200',
                        ];
                        $source = Str::lower($asset->source_type ?? $asset->sync_source ?? 'manual');
                    @endphp
                    <tr class="group transition hover:bg-emerald-50/30">
                        <td class="whitespace-nowrap px-5 py-3">
                            <a href="{{ route('assets.show', ['asset' => $asset, 'return_to' => request()->fullUrl()]) }}" class="font-bold text-slate-900 transition group-hover:text-emerald-700">
                                {{ $asset->hostname ?? $asset->name ?? $asset->asset_code }}
                            </a>
                            <p class="mt-0.5 font-mono text-[10px] text-slate-400">{{ $asset->asset_code }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <p class="text-sm font-semibold text-slate-700">{{ $asset->category ?? '-' }}</p>
                            <span class="mt-1 inline-flex rounded-md px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wider {{ $source === 'agent' ? 'bg-indigo-50 text-indigo-600' : 'bg-slate-100 text-slate-500' }}">
                                {{ $source === 'agent' ? 'Agent' : 'Manual' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm">
                            <p class="font-medium text-slate-700">{{ $asset->factory ?? $asset->location ?? '-' }}</p>
                            <p class="mt-0.5 text-xs text-slate-400">{{ $asset->department->name ?? 'No department' }}</p>
                        </td>
                        <td class="hidden px-4 py-3 text-sm 2xl:table-cell">
                            <p class="font-medium text-slate-700">{{ $asset->brand ?? '-' }} {{ $asset->model ?? '' }}</p>
                            <p class="mt-0.5 max-w-[180px] truncate text-xs text-slate-400">{{ $asset->serial_number ?? 'No serial number' }}</p>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3">
                            <span class="inline-flex rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider ring-1 {{ $status['class'] }}">
                                {{ $status['label'] }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm">
                            <p class="font-medium text-slate-700">{{ $asset->assigned_to_display_name ?? 'Unassigned' }}</p>
                            <p class="mt-0.5 text-xs text-slate-400">{{ $asset->location ?? '-' }}</p>
                        </td>
                        <td class="hidden whitespace-nowrap px-4 py-3 2xl:table-cell">
                            <p class="text-sm font-medium text-slate-700">{{ optional($asset->updated_at)->format('d M Y') ?? '-' }}</p>
                            <p class="mt-0.5 text-xs text-slate-400">{{ optional($asset->updated_at)->format('H:i') ?? '' }} WIB</p>
                        </td>
                        <td class="whitespace-nowrap px-5 py-3 text-right">
                            <div class="flex items-center justify-end gap-1.5">
                                <a href="{{ route('assets.show', ['asset' => $asset, 'return_to' => request()->fullUrl()]) }}" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700" title="View asset">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z" />
                                        <circle cx="12" cy="12" r="2.5" />
                                    </svg>
                                </a>
                                <a href="{{ route('assets.edit', ['asset' => $asset, 'return_to' => request()->fullUrl()]) }}" class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-600 text-white shadow-sm transition hover:bg-emerald-700" title="Edit asset">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5Z" />
                                    </svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-5 py-14 text-center">
                            <p class="font-semibold text-slate-700">No matching assets</p>
                            <p class="mt-1 text-sm text-slate-400">Try clearing or changing the filters.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="flex flex-col gap-3 border-t border-slate-100 bg-slate-50/70 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-xs text-slate-500">
            Showing <span class="font-semibold text-slate-700">{{ $assets->firstItem() ?? 0 }}–{{ $assets->lastItem() ?? 0 }}</span>
            of <span class="font-semibold text-slate-700">{{ number_format($assets->total()) }}</span>
        </p>
        <div class="flex items-center gap-3">
            <form method="GET" action="{{ route('assets.index') }}" class="flex items-center gap-2">
                @foreach ($filters as $key => $value)
                    @if (filled($value))
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endif
                @endforeach
                <label for="dashboard-per-page" class="text-xs font-medium text-slate-500">Rows</label>
                <select id="dashboard-per-page" name="per_page" onchange="this.form.submit()" class="h-9 rounded-lg border-slate-200 bg-white py-1 pl-3 pr-8 text-xs text-slate-700 focus:border-emerald-400 focus:ring-emerald-100">
                    @foreach ([10, 25, 50, 100] as $size)
                        <option value="{{ $size }}" @selected($perPage === $size)>{{ $size }}</option>
                    @endforeach
                </select>
            </form>
            {{ $assets->onEachSide(1)->links() }}
        </div>
    </div>
</section>
