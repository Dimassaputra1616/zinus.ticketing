@php
    $statusMeta = [
        'in_use' => ['label' => __('messages.active'), 'class' => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200/70'],
        'active' => ['label' => __('messages.active'), 'class' => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200/70'],
        'maintenance' => ['label' => __('messages.in_repair'), 'class' => 'bg-amber-50 text-amber-700 ring-1 ring-amber-200/70'],
        'in_repair' => ['label' => __('messages.in_repair'), 'class' => 'bg-amber-50 text-amber-700 ring-1 ring-amber-200/70'],
        'available' => ['label' => __('messages.spare'), 'class' => 'bg-sky-50 text-sky-700 ring-1 ring-sky-200/70'],
        'spare' => ['label' => __('messages.spare'), 'class' => 'bg-sky-50 text-sky-700 ring-1 ring-sky-200/70'],
        'broken' => ['label' => __('messages.retired'), 'class' => 'bg-rose-50 text-rose-700 ring-1 ring-rose-200/70'],
        'retired' => ['label' => __('messages.retired'), 'class' => 'bg-rose-50 text-rose-700 ring-1 ring-rose-200/70'],
    ];

    $conditionMeta = [
        'good' => ['label' => 'Good', 'class' => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200/70'],
        'minor_issue' => ['label' => 'Minor Issue', 'class' => 'bg-amber-50 text-amber-700 ring-1 ring-amber-200/70'],
        'damaged' => ['label' => 'Damaged', 'class' => 'bg-rose-50 text-rose-700 ring-1 ring-rose-200/70'],
        'repair' => ['label' => 'In Repair', 'class' => 'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-200/70'],
        'disposed' => ['label' => 'Disposed', 'class' => 'bg-slate-100 text-slate-700 ring-1 ring-slate-200'],
        'lost' => ['label' => 'Lost', 'class' => 'bg-red-50 text-red-700 ring-1 ring-red-200/70'],
    ];

    $filterKeys = ['search', 'factory', 'department', 'category', 'status'];
    $activeFilters = collect($filters ?? [])->only($filterKeys)->filter(fn ($value) => filled($value));
    $activeFilterCount = $activeFilters->count();
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
                <p class="text-sm font-semibold">{{ __('messages.success') }}</p>
                <p class="text-xs text-emerald-50">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    <div class="min-h-screen bg-slate-50 pb-10 pt-5">
        <div class="w-full space-y-4">
            <!-- Header -->
            <section class="rounded-3xl border border-slate-200/80 bg-gradient-to-br from-white via-white to-emerald-50/60 p-4 shadow-md shadow-emerald-500/10 lg:p-6">
                <div class="flex flex-col gap-4">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div class="space-y-2">
                            <p class="text-xs uppercase tracking-[0.35em] text-emerald-600/80">Asset Management Center</p>
                            <div class="flex flex-wrap items-center gap-3">
                                <h1 class="text-3xl font-semibold text-slate-900">Manual Inventory</h1>
                                <span class="inline-flex items-center rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200/80">{{ number_format($assets->total()) }} manual records</span>
                            </div>
                            <p class="text-sm text-slate-600">Track and manage non-agent devices like printers, network equipment, accessories, and office devices.</p>
                        </div>
                        <a
                            href="{{ route('admin.assets.manual.create') }}"
                            class="inline-flex items-center gap-2 rounded-full bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-emerald-500/30 transition hover:-translate-y-0.5 hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-200"
                        >
                            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-white/20 text-lg leading-none">+</span>
                            Add Manual Asset
                        </a>
                    </div>

                    <!-- Metrics cards -->
                    <div class="grid items-stretch gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        @php
                            $cards = [
                                [
                                    'label' => 'Total Manual Assets',
                                    'key' => 'total',
                                    'sublabel' => 'All manual items',
                                    'icon' => 'collection',
                                    'emphasis' => true,
                                    'status' => '',
                                ],
                                [
                                    'label' => __('messages.active'),
                                    'key' => 'active',
                                    'sublabel' => __('messages.in_use'),
                                    'icon' => 'bolt',
                                    'status' => 'active',
                                ],
                                [
                                    'label' => __('messages.in_repair'),
                                    'key' => 'in_repair',
                                    'sublabel' => __('messages.under_maintenance'),
                                    'icon' => 'wrench',
                                    'status' => 'in_repair',
                                ],
                                [
                                    'label' => __('messages.spare'),
                                    'key' => 'spare',
                                    'sublabel' => __('messages.standby_stock'),
                                    'icon' => 'stack',
                                    'status' => 'spare',
                                ],
                                [
                                    'label' => __('messages.retired'),
                                    'key' => 'retired',
                                    'sublabel' => __('messages.no_longer_service'),
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
                                $showFiltered = $card['status'] !== '' && filled($statusFilter) && $statusFilter === $card['status'];
                                if ($isTotal) {
                                    $cardClasses = $isActive
                                        ? 'bg-gradient-to-br from-emerald-800 to-emerald-600 text-white ring-2 ring-emerald-200/90 shadow-xl shadow-emerald-500/45 border border-transparent'
                                        : 'bg-white ring-1 ring-slate-200/80 text-slate-900 shadow-sm shadow-slate-200/50';
                                } else {
                                    $cardClasses = $isActive
                                        ? 'bg-emerald-600 text-white ring-2 ring-emerald-200/90 shadow-xl shadow-emerald-500/45 border border-transparent'
                                        : 'bg-white ring-1 ring-slate-200/80 text-slate-900 shadow-sm shadow-slate-200/50';
                                }
                                $queryParams = request()->except('page');
                                if ($card['status'] === '') {
                                    unset($queryParams['status']);
                                } else {
                                    $queryParams['status'] = $card['status'];
                                }
                            @endphp
                            <a
                                href="{{ route('admin.assets.manual.index', $queryParams) }}"
                                class="{{ $cardClasses }} group relative flex h-full min-h-[140px] flex-col rounded-2xl p-4 transition hover:-translate-y-0.5 hover:shadow-md hover:ring-emerald-200/80 focus:outline-none focus:ring-2 focus:ring-emerald-200 cursor-pointer"
                            >
                                <div class="flex w-full items-center gap-2 text-[11px] uppercase tracking-[0.3em] {{ ($isActive) ? 'text-emerald-50' : 'text-slate-500' }}">
                                    <span class="flex h-8 w-8 items-center justify-center rounded-xl {{ ($isActive) ? 'bg-white/10 text-white shadow-inner shadow-emerald-900/20' : 'bg-slate-100 text-slate-600 shadow-inner shadow-slate-100' }}">
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
                                <p class="{{ ($isActive) ? 'mt-3 text-3xl font-semibold text-white' : 'mt-2 text-2xl font-semibold text-slate-900' }}">{{ number_format($stats[$card['key']] ?? 0) }}</p>
                                <p class="{{ ($isActive) ? 'mt-1 text-xs text-emerald-100/90' : 'mt-1 text-xs text-slate-500' }}">{{ $card['sublabel'] }}</p>
                            </a>
                        @endforeach
                    </div>
                </div>
            </section>

            <!-- Filters Section -->
            <div class="rounded-3xl border border-slate-200/80 bg-slate-50/70 p-2 shadow-sm shadow-slate-200/60 sm:p-3">
                <form
                    method="GET"
                    x-data="{ advancedOpen: false }"
                    class="rounded-2xl border border-slate-200/80 bg-white p-3 shadow-sm shadow-slate-200/60 sm:p-4"
                    id="asset-filter-form"
                >
                    <div class="space-y-2">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                            <div class="flex-1 space-y-1">
                                <label class="text-xs font-semibold tracking-[0.25em] text-slate-500">{{ __('messages.search') }}</label>
                                <div class="relative">
                                    <input
                                        type="search"
                                        name="search"
                                        value="{{ $filters['search'] ?? '' }}"
                                        placeholder="Search by asset name, brand, code, serial..."
                                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 pl-11 text-sm text-slate-800 shadow-inner shadow-slate-100 placeholder:text-slate-400 focus:border-emerald-300 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-100"
                                    >
                                    <svg class="absolute left-3 top-2.5 h-5 w-5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="11" cy="11" r="8" />
                                        <path d="m21 21-4.35-4.35" />
                                    </svg>
                                </div>
                            </div>
                            <button
                                type="submit"
                                class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-5 text-sm font-semibold text-white shadow-md shadow-emerald-500/30 transition hover:-translate-y-0.5 hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-200 sm:w-auto"
                            >
                                <span>{{ __('messages.apply_filters') }}</span>
                            </button>
                        </div>

                        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                            <div class="space-y-1">
                                <label class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-500">{{ __('messages.factory') }}</label>
                                <select
                                    name="factory"
                                    class="block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-800 shadow-inner shadow-slate-100 focus:border-emerald-300 focus:bg-white focus:outline-none"
                                >
                                    <option value="">{{ __('messages.all_factories') }}</option>
                                    @foreach ($filterOptions['factories'] as $factory)
                                        <option value="{{ $factory }}" @selected(($filters['factory'] ?? null) === $factory)>{{ $factory }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="space-y-1">
                                <label class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-500">{{ __('messages.department') }}</label>
                                <select
                                    name="department"
                                    class="block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-800 shadow-inner shadow-slate-100 focus:border-emerald-300 focus:bg-white focus:outline-none"
                                >
                                    <option value="">{{ __('messages.all_departments') }}</option>
                                    @foreach ($departments as $department)
                                        <option value="{{ $department->id }}" @selected(($filters['department'] ?? null) == $department->id)>{{ $department->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="space-y-1">
                                <label class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-500">{{ __('messages.category') }}</label>
                                <select
                                    name="category"
                                    class="block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-800 shadow-inner shadow-slate-100 focus:border-emerald-300 focus:bg-white focus:outline-none"
                                >
                                    <option value="">{{ __('messages.all_categories') }}</option>
                                    @foreach ($filterOptions['categories'] as $category)
                                        <option value="{{ $category }}" @selected(($filters['category'] ?? null) === $category)>{{ $category }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="space-y-1">
                                <label class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-500">{{ __('messages.status') }}</label>
                                <select
                                    name="status"
                                    class="block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-800 shadow-inner shadow-slate-100 focus:border-emerald-300 focus:bg-white focus:outline-none"
                                >
                                    <option value="">{{ __('messages.all_status') }}</option>
                                    @foreach ($filterOptions['statuses'] as $status)
                                        <option value="{{ $status }}" @selected(($filters['status'] ?? null) === $status)>{{ Str::of($status)->replace('_', ' ')->title() }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center justify-between gap-2 pt-2">
                            <div class="flex flex-wrap items-center gap-2 text-[11px] font-semibold text-slate-600">
                                <span>{{ __('messages.active_filters', ['count' => $activeFilterCount]) }}</span>
                                <span class="text-slate-300">|</span>
                                <a href="{{ route('admin.assets.manual.index') }}" class="text-slate-500 hover:text-emerald-700">{{ __('messages.clear') }}</a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Table section -->
            <section class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-md shadow-slate-200/80">
                <div class="border-b border-slate-100 px-4 py-3 sm:px-5 sm:py-4">
                    <h2 class="text-xl font-semibold text-slate-900">Manual Asset Records</h2>
                    <p class="text-sm text-slate-600">Overview of manual assets and current lifecycle condition.</p>
                </div>

                <div class="w-full overflow-x-auto px-4 pb-4">
                    <table class="w-full table-auto text-sm border-separate border-spacing-y-2">
                        <thead>
                            <tr class="text-left text-xs font-semibold tracking-[0.15em] text-slate-500">
                                <th class="px-3 py-3">Asset Code</th>
                                <th class="px-3 py-3">Name</th>
                                <th class="px-3 py-3">Category</th>
                                <th class="px-3 py-3">Brand/Model</th>
                                <th class="px-3 py-3">Serial Number</th>
                                <th class="px-3 py-3">Department</th>
                                <th class="px-3 py-3">Condition</th>
                                <th class="px-3 py-3">Status</th>
                                <th class="px-3 py-3 text-right">Actions</th>
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

                                    $rawCondition = $asset->condition ?? 'good';
                                    $condInfo = $conditionMeta[$rawCondition] ?? [
                                        'label' => Str::of($rawCondition)->title(),
                                        'class' => 'bg-slate-100 text-slate-600 ring-1 ring-slate-200',
                                    ];
                                @endphp
                                <tr class="bg-white hover:bg-slate-50/50 rounded-2xl border shadow-sm transition duration-150">
                                    <td class="whitespace-nowrap px-3 py-3 font-semibold text-slate-900">
                                        <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold uppercase tracking-[0.3em] text-emerald-700 ring-1 ring-emerald-200/70">{{ $asset->asset_code }}</span>
                                    </td>
                                    <td class="px-3 py-3 font-medium text-slate-900">{{ $asset->name }}</td>
                                    <td class="px-3 py-3 text-slate-600">{{ $asset->category }}</td>
                                    <td class="px-3 py-3 text-slate-600">{{ $asset->brand ?? '-' }} • {{ $asset->model ?? '-' }}</td>
                                    <td class="px-3 py-3 text-slate-500 font-mono text-xs">{{ $asset->serial_number ?? '-' }}</td>
                                    <td class="px-3 py-3 text-slate-600">{{ $asset->department->name ?? '-' }}</td>
                                    <td class="px-3 py-3">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $condInfo['class'] }}">
                                            {{ $condInfo['label'] }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-3">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $statusInfo['class'] }}">
                                            {{ $statusInfo['label'] }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-3 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <a
                                                href="{{ route('admin.assets.manual.edit', $asset) }}"
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-emerald-600 text-white shadow-sm shadow-emerald-400/30 transition hover:bg-emerald-700"
                                                title="Edit asset"
                                            >
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M12 20h9" />
                                                    <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5Z" />
                                                </svg>
                                            </a>
                                            <form
                                                method="POST"
                                                action="{{ route('admin.assets.manual.destroy', $asset) }}"
                                                onsubmit="return confirm('Are you sure you want to delete this manual asset?')"
                                                class="inline"
                                            >
                                                @csrf
                                                @method('DELETE')
                                                <button
                                                    type="submit"
                                                    class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-slate-200 text-red-600 shadow-sm shadow-slate-200/60 transition hover:border-red-200 hover:bg-red-50"
                                                    title="Delete asset"
                                                >
                                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M3 6h18" />
                                                        <path d="M9 6V4.5A1.5 1.5 0 0 1 10.5 3h3A1.5 1.5 0 0 1 15 4.5V6m2 0v14a1 1 0 0 1-1 1H8a1 1 0 0 1-1-1V6h10Z" />
                                                        <path d="M10 11v6" />
                                                        <path d="M14 11v6" />
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-4 py-8">
                                        <div class="mx-auto flex max-w-lg flex-col items-center rounded-2xl border border-dashed border-slate-200 bg-slate-50/80 p-6 text-center">
                                            <p class="text-sm font-semibold text-slate-800">No manual assets found.</p>
                                            <p class="text-sm text-slate-600 mt-1">Start by adding a printer, network switch, or office asset.</p>
                                            <a
                                                href="{{ route('admin.assets.manual.create') }}"
                                                class="mt-4 inline-flex items-center gap-2 rounded-full bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-md"
                                            >
                                                Add Manual Asset
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="flex flex-col gap-4 border-t border-slate-100 bg-slate-50/70 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="text-sm text-slate-600">
                        Showing <span class="font-semibold text-slate-800">{{ $assets->firstItem() ?? 0 }}</span> to <span class="font-semibold text-slate-800">{{ $assets->lastItem() ?? 0 }}</span> of <span class="font-semibold text-slate-800">{{ $assets->total() }}</span> records
                    </div>
                    <div class="rounded-xl bg-white px-3 py-2 ring-1 ring-slate-200">
                        {{ $assets->links() }}
                    </div>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
