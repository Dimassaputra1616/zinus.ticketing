@php
    $statusMeta = [
        'in_use' => ['label' => __('messages.active'), 'class' => 'bg-emerald-50 text-emerald-700 ring-emerald-200', 'dot' => 'bg-emerald-500'],
        'active' => ['label' => __('messages.active'), 'class' => 'bg-emerald-50 text-emerald-700 ring-emerald-200', 'dot' => 'bg-emerald-500'],
        'maintenance' => ['label' => __('messages.in_repair'), 'class' => 'bg-amber-50 text-amber-700 ring-amber-200', 'dot' => 'bg-amber-500'],
        'in_repair' => ['label' => __('messages.in_repair'), 'class' => 'bg-amber-50 text-amber-700 ring-amber-200', 'dot' => 'bg-amber-500'],
        'available' => ['label' => __('messages.spare'), 'class' => 'bg-sky-50 text-sky-700 ring-sky-200', 'dot' => 'bg-sky-500'],
        'spare' => ['label' => __('messages.spare'), 'class' => 'bg-sky-50 text-sky-700 ring-sky-200', 'dot' => 'bg-sky-500'],
        'broken' => ['label' => __('messages.retired'), 'class' => 'bg-rose-50 text-rose-700 ring-rose-200', 'dot' => 'bg-rose-500'],
        'retired' => ['label' => __('messages.retired'), 'class' => 'bg-rose-50 text-rose-700 ring-rose-200', 'dot' => 'bg-rose-500'],
    ];

    $conditionMeta = [
        'good' => ['label' => 'Good', 'class' => 'bg-emerald-50 text-emerald-700 ring-emerald-200'],
        'minor_issue' => ['label' => 'Minor Issue', 'class' => 'bg-amber-50 text-amber-700 ring-amber-200'],
        'damaged' => ['label' => 'Damaged', 'class' => 'bg-rose-50 text-rose-700 ring-rose-200'],
        'repair' => ['label' => 'In Repair', 'class' => 'bg-indigo-50 text-indigo-700 ring-indigo-200'],
        'disposed' => ['label' => 'Scrapped', 'class' => 'bg-slate-100 text-slate-700 ring-slate-200'],
        'lost' => ['label' => 'Lost', 'class' => 'bg-red-50 text-red-700 ring-red-200'],
    ];

    $isSoftwareLicense = $title === 'Software License';
    $categoryProfileKey = \App\Support\AssetCategoryProfile::key($title);
    $usesConnectionColumn = in_array($categoryProfileKey, ['monitor', 'peripheral'], true);
    $searchPlaceholder = match (true) {
        $isSoftwareLicense => 'Name, product key, asset code',
        $usesConnectionColumn => 'Name, connection, serial, asset code',
        default => 'Name, hostname, IP, asset code',
    };
    $technicalColumnLabel = $usesConnectionColumn ? 'Connection' : 'IP Address';
    $technicalValue = function ($asset) use ($usesConnectionColumn) {
        if (! $usesConnectionColumn) {
            return $asset->ip_address;
        }

        $specValues = [];
        foreach (explode('|', (string) $asset->specs) as $specPart) {
            [$key, $value] = array_pad(explode(':', $specPart, 2), 2, null);
            $normalizedKey = strtolower(trim((string) $key));
            $normalizedValue = trim((string) $value);

            if ($normalizedKey !== '' && filled($normalizedValue)) {
                $specValues[$normalizedKey] = $normalizedValue;
            }
        }

        return $specValues['connection'] ?? $specValues['interface'] ?? null;
    };
    $activeFilterCount = collect([$search, $factory, $departmentId, $location, $status, $lifecycleStatus, $brand])
        ->filter(fn ($value) => filled($value))
        ->count();
    $advancedFiltersOpen = filled($location) || filled($status) || filled($brand);
@endphp

<x-app-layout>
    <div
        x-data="{
            filtersOpen: @js($advancedFiltersOpen),
            mobileFiltersOpen: false
        }"
        class="min-h-screen pb-8"
    >
        <header class="border-b border-slate-200 pb-5 pt-1">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div class="min-w-0">
                    <div class="mb-2 flex items-center gap-2 text-xs font-medium text-slate-500">
                        <span>Asset Management</span>
                        <svg class="h-3.5 w-3.5 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="m9 18 6-6-6-6" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <span class="text-slate-700">{{ $title }}</span>
                    </div>
                    <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                        <h1 class="text-2xl font-semibold tracking-normal text-slate-950 sm:text-3xl">{{ $title }}</h1>
                        <span class="text-sm font-medium text-slate-500">
                            {{ number_format($assets->total()) }} {{ Str::plural('record', $assets->total()) }}
                        </span>
                    </div>
                </div>

                @if ($title !== 'PC' && $title !== 'Laptop')
                    <a
                        href="{{ route('admin.assets.manual.create', ['category' => $title]) }}"
                        class="inline-flex h-10 items-center justify-center gap-2 self-start rounded-lg bg-emerald-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 sm:self-auto"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M12 5v14M5 12h14" stroke-linecap="round" />
                        </svg>
                        <span>Add {{ $title }}</span>
                    </a>
                @endif
            </div>
        </header>

        <section class="border-b border-slate-200 py-4" aria-label="Asset filters">
            <div class="mb-3 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="h-4 w-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="M4 5h16M7 12h10m-7 7h4" stroke-linecap="round" />
                    </svg>
                    <h2 class="text-sm font-semibold tracking-normal text-slate-800">Filters</h2>
                    @if ($activeFilterCount > 0)
                        <span class="inline-flex h-5 min-w-5 items-center justify-center rounded-md bg-emerald-100 px-1.5 text-xs font-semibold text-emerald-700">
                            {{ $activeFilterCount }}
                        </span>
                    @endif
                </div>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="filtersOpen = !filtersOpen"
                        class="hidden h-9 items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 sm:inline-flex"
                        :aria-expanded="filtersOpen"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path d="M4 7h10M18 7h2M4 17h2m4 0h10M14 4v6M6 14v6" stroke-linecap="round" />
                        </svg>
                        <span>More filters</span>
                        <svg class="h-3.5 w-3.5 transition-transform" :class="filtersOpen && 'rotate-180'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="m6 9 6 6 6-6" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </button>

                    <button
                        type="button"
                        @click="mobileFiltersOpen = !mobileFiltersOpen"
                        class="inline-flex h-9 items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 sm:hidden"
                        :aria-expanded="mobileFiltersOpen"
                    >
                        <span x-text="mobileFiltersOpen ? 'Hide filters' : 'Show filters'"></span>
                        <svg class="h-3.5 w-3.5 transition-transform" :class="mobileFiltersOpen && 'rotate-180'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="m6 9 6 6 6-6" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </button>
                </div>
            </div>

            <form
                method="GET"
                :class="mobileFiltersOpen ? 'grid' : 'hidden sm:grid'"
                class="grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-12"
            >
                <div class="sm:col-span-2 lg:col-span-4">
                    <label for="asset-search" class="mb-1.5 block text-xs font-medium text-slate-600">Search inventory</label>
                    <div class="relative">
                        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <circle cx="11" cy="11" r="7" />
                            <path d="m20 20-3.5-3.5" stroke-linecap="round" />
                        </svg>
                        <input
                            id="asset-search"
                            type="search"
                            name="search"
                            value="{{ $search }}"
                            placeholder="{{ $searchPlaceholder }}"
                            class="h-10 w-full rounded-lg border-slate-300 bg-white pl-9 pr-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-emerald-500 focus:ring-emerald-500"
                        >
                    </div>
                </div>

                <div class="lg:col-span-2">
                    <label for="factory-filter" class="mb-1.5 block text-xs font-medium text-slate-600">Factory</label>
                    <select id="factory-filter" name="factory" class="h-10 w-full rounded-lg border-slate-300 bg-white py-0 text-sm text-slate-800 focus:border-emerald-500 focus:ring-emerald-500">
                        <option value="">All factories</option>
                        @foreach ($factoriesList as $f)
                            <option value="{{ $f }}" @selected($factory === $f)>{{ $f }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="lg:col-span-2">
                    <label for="department-filter" class="mb-1.5 block text-xs font-medium text-slate-600">Department</label>
                    <select id="department-filter" name="department" class="h-10 w-full rounded-lg border-slate-300 bg-white py-0 text-sm text-slate-800 focus:border-emerald-500 focus:ring-emerald-500">
                        <option value="">All departments</option>
                        @foreach ($departmentsList as $d)
                            <option value="{{ $d->id }}" @selected($departmentId == $d->id)>{{ $d->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="lg:col-span-2">
                    <label for="lifecycle-filter" class="mb-1.5 block text-xs font-medium text-slate-600">Lifecycle</label>
                    <select id="lifecycle-filter" name="lifecycle_status" class="h-10 w-full rounded-lg border-slate-300 bg-white py-0 text-sm text-slate-800 focus:border-emerald-500 focus:ring-emerald-500">
                        <option value="">All lifecycles</option>
                        <option value="active" @selected($lifecycleStatus === 'active')>Active</option>
                        <option value="in_repair" @selected($lifecycleStatus === 'in_repair')>In Repair</option>
                        <option value="spare" @selected($lifecycleStatus === 'spare')>Spare Stock</option>
                        <option value="assigned" @selected($lifecycleStatus === 'assigned')>Assigned</option>
                        <option value="disposed" @selected($lifecycleStatus === 'disposed')>Scrapped</option>
                    </select>
                </div>

                <div class="flex items-end gap-2 sm:col-span-2 lg:col-span-2">
                    <button
                        type="submit"
                        class="inline-flex h-10 flex-1 items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path d="M4 5h16l-6 7v5l-4 2v-7L4 5Z" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <span>Apply</span>
                    </button>
                    @if ($activeFilterCount > 0)
                        <a
                            href="{{ url()->current() }}"
                            class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-slate-300 bg-white text-slate-600 transition hover:bg-slate-50 hover:text-slate-900"
                            title="Reset filters"
                            aria-label="Reset filters"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path d="M3 12a9 9 0 1 0 3-6.7L3 8" stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M3 3v5h5" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </a>
                    @endif
                </div>

                <button
                    type="button"
                    @click="filtersOpen = !filtersOpen"
                    class="inline-flex h-10 items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-3 text-sm font-semibold text-slate-700 sm:hidden"
                    :aria-expanded="filtersOpen"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="M4 7h10M18 7h2M4 17h2m4 0h10M14 4v6M6 14v6" stroke-linecap="round" />
                    </svg>
                    <span>More filters</span>
                </button>

                <div
                    x-cloak
                    x-show="filtersOpen"
                    x-transition
                    class="grid gap-3 border-t border-slate-200 pt-3 sm:col-span-2 sm:grid-cols-3 lg:col-span-12"
                >
                    <div>
                        <label for="brand-filter" class="mb-1.5 block text-xs font-medium text-slate-600">Brand</label>
                        <select id="brand-filter" name="brand" class="h-10 w-full rounded-lg border-slate-300 bg-white py-0 text-sm text-slate-800 focus:border-emerald-500 focus:ring-emerald-500">
                            <option value="">All brands</option>
                            @foreach ($brandsList as $brandOption)
                                <option value="{{ $brandOption }}" @selected($brand === $brandOption)>{{ $brandOption }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="location-filter" class="mb-1.5 block text-xs font-medium text-slate-600">Location</label>
                        <input
                            id="location-filter"
                            type="text"
                            name="location"
                            value="{{ $location }}"
                            placeholder="Search location"
                            class="h-10 w-full rounded-lg border-slate-300 bg-white text-sm text-slate-800 placeholder:text-slate-400 focus:border-emerald-500 focus:ring-emerald-500"
                        >
                    </div>
                    <div>
                        <label for="status-filter" class="mb-1.5 block text-xs font-medium text-slate-600">Operational status</label>
                        <select id="status-filter" name="status" class="h-10 w-full rounded-lg border-slate-300 bg-white py-0 text-sm text-slate-800 focus:border-emerald-500 focus:ring-emerald-500">
                            <option value="">All statuses</option>
                            <option value="available" @selected($status === 'available')>Available</option>
                            <option value="in_use" @selected($status === 'in_use')>In Use</option>
                            <option value="maintenance" @selected($status === 'maintenance')>Maintenance</option>
                            <option value="broken" @selected($status === 'broken')>Broken</option>
                        </select>
                    </div>
                </div>
            </form>
        </section>

        <section class="pt-5" aria-labelledby="inventory-heading">
            <div class="mb-3 flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 id="inventory-heading" class="text-base font-semibold tracking-normal text-slate-900">Inventory records</h2>
                    <p class="text-xs text-slate-500">
                        Showing {{ $assets->firstItem() ?? 0 }}-{{ $assets->lastItem() ?? 0 }} of {{ number_format($assets->total()) }}
                    </p>
                </div>
                @if ($activeFilterCount > 0)
                    <p class="text-xs font-medium text-emerald-700">{{ $activeFilterCount }} active {{ Str::plural('filter', $activeFilterCount) }}</p>
                @endif
            </div>

            <div class="hidden overflow-hidden rounded-lg border border-slate-200 bg-white lg:block">
                <div class="max-h-[68vh] overflow-auto">
                    <table class="w-full min-w-[1020px] table-fixed text-left text-sm">
                        <thead class="sticky top-0 z-10 bg-slate-50">
                            <tr class="border-b border-slate-200 text-xs font-semibold text-slate-500">
                                @if ($isSoftwareLicense)
                                    <th class="w-[22%] px-4 py-3">Software</th>
                                    <th class="w-[15%] px-4 py-3">Asset Code</th>
                                    <th class="w-[15%] px-4 py-3">Vendor</th>
                                    <th class="w-[18%] px-4 py-3">License / Product Key</th>
                                    <th class="w-[13%] px-4 py-3">Department</th>
                                    <th class="w-[10%] px-4 py-3">Expiry</th>
                                @else
                                    <th class="w-[17%] px-4 py-3">Device</th>
                                    <th class="w-[13%] px-4 py-3">Asset Code</th>
                                    <th class="w-[13%] px-4 py-3">Brand / Model</th>
                                    <th class="w-[13%] px-4 py-3">{{ $technicalColumnLabel }}</th>
                                    <th class="w-[10%] px-4 py-3">Location</th>
                                    <th class="w-[10%] px-4 py-3">Department</th>
                                    <th class="w-[8%] px-4 py-3">Condition</th>
                                @endif
                                <th class="w-[8%] px-4 py-3">Status</th>
                                <th class="w-[124px] px-2 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($assets as $asset)
                                @php
                                    $rawStatus = $asset->status ?? 'available';
                                    $statusKey = Str::of($rawStatus)->lower()->replace(' ', '_')->toString();
                                    $statusInfo = $statusMeta[$statusKey] ?? [
                                        'label' => Str::of($rawStatus)->replace('_', ' ')->title(),
                                        'class' => 'bg-slate-100 text-slate-700 ring-slate-200',
                                        'dot' => 'bg-slate-400',
                                    ];

                                    $rawCondition = $asset->condition ?? 'good';
                                    $condInfo = $conditionMeta[$rawCondition] ?? [
                                        'label' => Str::of($rawCondition)->title(),
                                        'class' => 'bg-slate-100 text-slate-700 ring-slate-200',
                                    ];

                                    $deviceName = filled($asset->name)
                                        ? $asset->name
                                        : ($asset->hostname ?: $asset->asset_code);
                                    $showHostname = filled($asset->hostname)
                                        && strcasecmp(trim((string) $asset->hostname), trim((string) $deviceName)) !== 0;
                                    $locationValue = $asset->location ?: ($asset->factory ?: '-');
                                    $technicalFieldValue = $technicalValue($asset);
                                @endphp
                                <tr class="group align-middle transition-colors hover:bg-emerald-50/30">
                                    @if ($isSoftwareLicense)
                                        <td class="px-4 py-3.5">
                                            <a href="{{ route('assets.show', $asset) }}" class="block truncate font-semibold text-slate-900 hover:text-emerald-700">
                                                {{ $asset->name }}
                                            </a>
                                        </td>
                                        <td class="px-4 py-3.5">
                                            <span class="inline-flex max-w-full truncate rounded-md bg-emerald-50 px-2 py-1 font-mono text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200">
                                                {{ $asset->asset_code }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3.5 text-slate-600">{{ $asset->brand ?? '-' }}</td>
                                        <td class="truncate px-4 py-3.5 font-mono text-xs text-slate-500" title="{{ $asset->serial_number }}">
                                            {{ $asset->serial_number ?? '-' }}
                                        </td>
                                        <td class="px-4 py-3.5 text-slate-600">{{ $asset->department->name ?? '-' }}</td>
                                        <td class="px-4 py-3.5 text-xs text-slate-600">
                                            {{ $asset->warranty_until ? \Carbon\Carbon::parse($asset->warranty_until)->format('d M Y') : ($asset->warranty_expired ? \Carbon\Carbon::parse($asset->warranty_expired)->format('d M Y') : '-') }}
                                        </td>
                                    @else
                                        <td class="px-4 py-3.5">
                                            <a href="{{ route('assets.show', $asset) }}" class="flex min-w-0 items-center gap-3">
                                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-500 transition group-hover:bg-emerald-100 group-hover:text-emerald-700">
                                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                                                        <rect x="3" y="4" width="18" height="13" rx="2" />
                                                        <path d="M8 21h8m-4-4v4" stroke-linecap="round" />
                                                    </svg>
                                                </span>
                                                <span class="min-w-0">
                                                    <span class="block truncate font-semibold text-slate-900 group-hover:text-emerald-700">{{ $deviceName }}</span>
                                                    @if ($showHostname)
                                                        <span class="mt-0.5 block truncate font-mono text-xs text-slate-500">{{ $asset->hostname }}</span>
                                                    @endif
                                                </span>
                                            </a>
                                        </td>
                                        <td class="px-4 py-3.5">
                                            <span class="inline-flex max-w-full truncate rounded-md bg-emerald-50 px-2 py-1 font-mono text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200" title="{{ $asset->asset_code }}">
                                                {{ $asset->asset_code }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3.5">
                                            <span class="block truncate text-slate-700">{{ $asset->brand ?? '-' }}</span>
                                            <span class="mt-0.5 block truncate text-xs text-slate-500">{{ $asset->model ?? '-' }}</span>
                                        </td>
                                        <td class="truncate px-4 py-3.5 font-mono text-xs text-slate-500" title="{{ $technicalFieldValue }}">
                                            {{ $technicalFieldValue ?? '-' }}
                                        </td>
                                        <td class="px-4 py-3.5 text-slate-600">{{ $locationValue }}</td>
                                        <td class="px-4 py-3.5 text-slate-600">{{ $asset->department->name ?? '-' }}</td>
                                        <td class="px-4 py-3.5">
                                            <span class="inline-flex rounded-md px-2 py-1 text-xs font-semibold ring-1 ring-inset {{ $condInfo['class'] }}">
                                                {{ $condInfo['label'] }}
                                            </span>
                                        </td>
                                    @endif
                                    <td class="px-4 py-3.5">
                                        <span class="inline-flex items-center gap-1.5 rounded-md px-2 py-1 text-xs font-semibold ring-1 ring-inset {{ $statusInfo['class'] }}">
                                            <span class="h-1.5 w-1.5 rounded-full {{ $statusInfo['dot'] }}"></span>
                                            {{ $statusInfo['label'] }}
                                        </span>
                                    </td>
                                    <td class="px-2 py-3.5">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <a
                                                href="{{ route('assets.show', $asset) }}"
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900"
                                                title="View detail"
                                                aria-label="View {{ $deviceName }}"
                                            >
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                    <path d="M2.1 12.3a1 1 0 0 1 0-.6 10.7 10.7 0 0 1 19.8 0 1 1 0 0 1 0 .6 10.7 10.7 0 0 1-19.8 0Z" />
                                                    <circle cx="12" cy="12" r="3" />
                                                </svg>
                                            </a>
                                            @if ($asset->source_type === 'manual')
                                                <a
                                                    href="{{ route('admin.assets.manual.edit', $asset) }}"
                                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 transition hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-700"
                                                    title="Edit asset"
                                                    aria-label="Edit {{ $deviceName }}"
                                                >
                                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                        <path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5Z" stroke-linecap="round" stroke-linejoin="round" />
                                                    </svg>
                                                </a>
                                                <form method="POST" action="{{ route('admin.assets.manual.destroy', $asset) }}" onsubmit="return confirm('Are you sure you want to delete this asset?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button
                                                        type="submit"
                                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-700"
                                                        title="Delete asset"
                                                        aria-label="Delete {{ $deviceName }}"
                                                    >
                                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                            <path d="M3 6h18M9 6V4.5A1.5 1.5 0 0 1 10.5 3h3A1.5 1.5 0 0 1 15 4.5V6m2 0v14a1 1 0 0 1-1 1H8a1 1 0 0 1-1-1V6h10Z" stroke-linecap="round" stroke-linejoin="round" />
                                                        </svg>
                                                    </button>
                                                </form>
                                            @else
                                                <a
                                                    href="{{ route('assets.edit', $asset) }}"
                                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 transition hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-700"
                                                    title="Edit asset"
                                                    aria-label="Edit {{ $deviceName }}"
                                                >
                                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                        <path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5Z" stroke-linecap="round" stroke-linejoin="round" />
                                                    </svg>
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-6 py-16 text-center">
                                        <svg class="mx-auto h-8 w-8 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                            <circle cx="11" cy="11" r="7" />
                                            <path d="m20 20-3.5-3.5" stroke-linecap="round" />
                                        </svg>
                                        <p class="mt-3 text-sm font-medium text-slate-700">No assets found</p>
                                        <p class="mt-1 text-xs text-slate-500">Try adjusting the active filters.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-col gap-3 border-t border-slate-200 bg-slate-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-xs text-slate-500">
                        Page {{ $assets->currentPage() }} of {{ $assets->lastPage() }}
                    </p>
                    <div>{{ $assets->links() }}</div>
                </div>
            </div>

            <div class="overflow-hidden rounded-lg border border-slate-200 bg-white lg:hidden">
                <div class="divide-y divide-slate-100">
                    @forelse ($assets as $asset)
                        @php
                            $rawStatus = $asset->status ?? 'available';
                            $statusKey = Str::of($rawStatus)->lower()->replace(' ', '_')->toString();
                            $statusInfo = $statusMeta[$statusKey] ?? [
                                'label' => Str::of($rawStatus)->replace('_', ' ')->title(),
                                'class' => 'bg-slate-100 text-slate-700 ring-slate-200',
                                'dot' => 'bg-slate-400',
                            ];
                            $rawCondition = $asset->condition ?? 'good';
                            $condInfo = $conditionMeta[$rawCondition] ?? [
                                'label' => Str::of($rawCondition)->title(),
                                'class' => 'bg-slate-100 text-slate-700 ring-slate-200',
                            ];
                            $deviceName = filled($asset->name)
                                ? $asset->name
                                : ($asset->hostname ?: $asset->asset_code);
                            $showHostname = filled($asset->hostname)
                                && strcasecmp(trim((string) $asset->hostname), trim((string) $deviceName)) !== 0;
                            $locationValue = $asset->location ?: ($asset->factory ?: '-');
                            $technicalFieldValue = $technicalValue($asset);
                        @endphp
                        <article class="p-4">
                            <div class="flex items-start justify-between gap-3">
                                <a href="{{ route('assets.show', $asset) }}" class="min-w-0">
                                    <h3 class="truncate text-sm font-semibold tracking-normal text-slate-900">{{ $deviceName }}</h3>
                                    @if ($showHostname)
                                        <p class="mt-0.5 truncate font-mono text-xs text-slate-500">{{ $asset->hostname }}</p>
                                    @endif
                                </a>
                                <span class="inline-flex shrink-0 items-center gap-1.5 rounded-md px-2 py-1 text-xs font-semibold ring-1 ring-inset {{ $statusInfo['class'] }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $statusInfo['dot'] }}"></span>
                                    {{ $statusInfo['label'] }}
                                </span>
                            </div>

                            <div class="mt-3">
                                <span class="inline-flex max-w-full truncate rounded-md bg-emerald-50 px-2 py-1 font-mono text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200">
                                    {{ $asset->asset_code }}
                                </span>
                            </div>

                            <dl class="mt-4 grid grid-cols-2 gap-x-4 gap-y-3 text-xs">
                                @if ($isSoftwareLicense)
                                    <div>
                                        <dt class="text-slate-400">Vendor</dt>
                                        <dd class="mt-1 font-medium text-slate-700">{{ $asset->brand ?? '-' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-slate-400">Department</dt>
                                        <dd class="mt-1 font-medium text-slate-700">{{ $asset->department->name ?? '-' }}</dd>
                                    </div>
                                    <div class="col-span-2">
                                        <dt class="text-slate-400">License / Product Key</dt>
                                        <dd class="mt-1 break-all font-mono text-slate-600">{{ $asset->serial_number ?? '-' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-slate-400">Expiry</dt>
                                        <dd class="mt-1 font-medium text-slate-700">
                                            {{ $asset->warranty_until ? \Carbon\Carbon::parse($asset->warranty_until)->format('d M Y') : ($asset->warranty_expired ? \Carbon\Carbon::parse($asset->warranty_expired)->format('d M Y') : '-') }}
                                        </dd>
                                    </div>
                                @else
                                    <div>
                                        <dt class="text-slate-400">Brand / Model</dt>
                                        <dd class="mt-1 font-medium text-slate-700">{{ $asset->brand ?? '-' }} / {{ $asset->model ?? '-' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-slate-400">{{ $technicalColumnLabel }}</dt>
                                        <dd class="mt-1 truncate font-mono text-slate-600">{{ $technicalFieldValue ?? '-' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-slate-400">Location</dt>
                                        <dd class="mt-1 font-medium text-slate-700">{{ $locationValue }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-slate-400">Department</dt>
                                        <dd class="mt-1 font-medium text-slate-700">{{ $asset->department->name ?? '-' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-slate-400">Condition</dt>
                                        <dd class="mt-1">
                                            <span class="inline-flex rounded-md px-2 py-1 font-semibold ring-1 ring-inset {{ $condInfo['class'] }}">{{ $condInfo['label'] }}</span>
                                        </dd>
                                    </div>
                                @endif
                            </dl>

                            <div class="mt-4 flex items-center justify-end gap-2 border-t border-slate-100 pt-3">
                                <a
                                    href="{{ route('assets.show', $asset) }}"
                                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-600"
                                    title="View detail"
                                    aria-label="View {{ $deviceName }}"
                                >
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <path d="M2.1 12.3a1 1 0 0 1 0-.6 10.7 10.7 0 0 1 19.8 0 1 1 0 0 1 0 .6 10.7 10.7 0 0 1-19.8 0Z" />
                                        <circle cx="12" cy="12" r="3" />
                                    </svg>
                                </a>
                                <a
                                    href="{{ $asset->source_type === 'manual' ? route('admin.assets.manual.edit', $asset) : route('assets.edit', $asset) }}"
                                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-600"
                                    title="Edit asset"
                                    aria-label="Edit {{ $deviceName }}"
                                >
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5Z" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </a>
                                @if ($asset->source_type === 'manual')
                                    <form method="POST" action="{{ route('admin.assets.manual.destroy', $asset) }}" onsubmit="return confirm('Are you sure you want to delete this asset?')">
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            type="submit"
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-rose-600"
                                            title="Delete asset"
                                            aria-label="Delete {{ $deviceName }}"
                                        >
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                <path d="M3 6h18M9 6V4.5A1.5 1.5 0 0 1 10.5 3h3A1.5 1.5 0 0 1 15 4.5V6m2 0v14a1 1 0 0 1-1 1H8a1 1 0 0 1-1-1V6h10Z" stroke-linecap="round" stroke-linejoin="round" />
                                            </svg>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </article>
                    @empty
                        <div class="px-6 py-12 text-center">
                            <p class="text-sm font-medium text-slate-700">No assets found</p>
                            <p class="mt-1 text-xs text-slate-500">Try adjusting the active filters.</p>
                        </div>
                    @endforelse
                </div>

                <div class="border-t border-slate-200 bg-slate-50 px-4 py-3">
                    {{ $assets->links() }}
                </div>
            </div>
        </section>
    </div>
</x-app-layout>
