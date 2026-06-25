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
        'disposed' => ['label' => 'Scrapped', 'class' => 'bg-slate-100 text-slate-700 ring-1 ring-slate-200'],
        'lost' => ['label' => 'Lost', 'class' => 'bg-red-50 text-red-700 ring-1 ring-red-200/70'],
    ];
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
                <p class="text-sm font-semibold">Success</p>
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
                                <h1 class="text-3xl font-semibold text-slate-900">{{ $title }}</h1>
                                <span class="inline-flex items-center rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200/80">
                                    {{ number_format($assets->total()) }} assets
                                </span>
                            </div>
                            @if ($title === 'Software License')
                                <p class="text-sm text-slate-600">Track and manage inventory records for software licenses.</p>
                            @else
                                <p class="text-sm text-slate-600">Track and manage inventory records for {{ strtolower($title) }} hardware assets.</p>
                            @endif
                        </div>
                        @if ($title !== 'PC' && $title !== 'Laptop')
                            <a
                                href="{{ route('admin.assets.manual.create', ['category' => $title]) }}"
                                class="inline-flex items-center gap-2 rounded-full bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-emerald-500/30 transition hover:-translate-y-0.5 hover:bg-emerald-700 focus:outline-none"
                                >
                                <span class="flex h-9 w-9 items-center justify-center rounded-full bg-white/20 text-lg leading-none">+</span>
                                Add {{ $title }}
                            </a>
                        @endif
                    </div>
                </div>
            </section>

            <!-- Filters Section -->
            <div class="rounded-3xl border border-slate-200/80 bg-white p-4 shadow-sm">
                <form method="GET" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6 items-end">
                    <div class="sm:col-span-2 space-y-1">
                        <label class="text-xs font-semibold tracking-wider text-slate-500 uppercase">Search</label>
                        <input
                            type="search"
                            name="search"
                            value="{{ $search }}"
                            placeholder="Name, brand, serial, asset code..."
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:bg-white focus:outline-none"
                        >
                    </div>

                    <div class="space-y-1">
                        <label class="text-xs font-semibold tracking-wider text-slate-500 uppercase">Factory</label>
                        <select name="factory" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:outline-none">
                            <option value="">All Factories</option>
                            @foreach ($factoriesList as $f)
                                <option value="{{ $f }}" @selected($factory === $f)>{{ $f }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-1">
                        <label class="text-xs font-semibold tracking-wider text-slate-500 uppercase">Department</label>
                        <select name="department" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:outline-none">
                            <option value="">All Departments</option>
                            @foreach ($departmentsList as $d)
                                <option value="{{ $d->id }}" @selected($departmentId == $d->id)>{{ $d->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-1">
                        <label class="text-xs font-semibold tracking-wider text-slate-500 uppercase">Lifecycle Status</label>
                        <select name="lifecycle_status" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:outline-none">
                            <option value="">All Lifecycles</option>
                            <option value="active" @selected($lifecycleStatus === 'active')>Active</option>
                            <option value="in_repair" @selected($lifecycleStatus === 'in_repair')>In Repair</option>
                            <option value="spare" @selected($lifecycleStatus === 'spare')>Spare Stock</option>
                            <option value="assigned" @selected($lifecycleStatus === 'assigned')>Assigned</option>
                            <option value="disposed" @selected($lifecycleStatus === 'disposed')>Scrapped</option>
                        </select>
                    </div>

                    <div class="flex gap-2">
                        <button type="submit" class="w-full inline-flex h-10 items-center justify-center rounded-xl bg-emerald-600 text-sm font-semibold text-white shadow-md hover:bg-emerald-700 transition">
                            Apply
                        </button>
                        <a href="{{ url()->current() }}" class="inline-flex h-10 items-center justify-center rounded-xl border border-slate-200 px-3 text-sm font-semibold text-slate-600 hover:bg-slate-50">
                            Reset
                        </a>
                    </div>
                </form>
            </div>

            <!-- Table section -->
            <section class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-md">
                <div class="w-full overflow-x-auto p-4">
                    <table class="w-full table-auto text-sm border-separate border-spacing-y-2">
                        <thead>
                            <tr class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                <th class="px-3 py-3">Asset Code</th>
                                @if ($title === 'Software License')
                                    <th class="px-3 py-3">Software Name</th>
                                    <th class="px-3 py-3">Vendor / Publisher</th>
                                    <th class="px-3 py-3">License / Product Key</th>
                                    <th class="px-3 py-3">Department</th>
                                    <th class="px-3 py-3">Expiry Date</th>
                                @else
                                    <th class="px-3 py-3">Device Name</th>
                                    <th class="px-3 py-3">Brand / Model</th>
                                    <th class="px-3 py-3">Serial Number</th>
                                    <th class="px-3 py-3">Location</th>
                                    <th class="px-3 py-3">Department</th>
                                    <th class="px-3 py-3">Condition</th>
                                @endif
                                <th class="px-3 py-3">Status</th>
                                <th class="px-3 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($assets as $asset)
                                @php
                                    $rawStatus = $asset->status ?? 'available';
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
                                        <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold uppercase tracking-wider text-emerald-700 ring-1 ring-emerald-200">{{ $asset->asset_code }}</span>
                                    </td>
                                    @if ($title === 'Software License')
                                        <td class="px-3 py-3 font-medium text-slate-900">
                                            <div>{{ $asset->name }}</div>
                                        </td>
                                        <td class="px-3 py-3 text-slate-600">{{ $asset->brand ?? '-' }}</td>
                                        <td class="px-3 py-3 text-slate-500 font-mono text-xs">{{ $asset->serial_number ?? '-' }}</td>
                                        <td class="px-3 py-3 text-slate-600">{{ $asset->department->name ?? '-' }}</td>
                                        <td class="px-3 py-3 text-slate-600 font-mono text-xs">
                                            {{ $asset->warranty_until ? \Carbon\Carbon::parse($asset->warranty_until)->format('d M Y') : ($asset->warranty_expired ? \Carbon\Carbon::parse($asset->warranty_expired)->format('d M Y') : '-') }}
                                        </td>
                                    @else
                                        <td class="px-3 py-3 font-medium text-slate-900">
                                            <div>{{ $asset->name }}</div>
                                            @if($asset->hostname)
                                                <div class="text-xs text-slate-400 mt-0.5 font-mono">{{ $asset->hostname }}</div>
                                            @endif
                                        </td>
                                        <td class="px-3 py-3 text-slate-600">{{ $asset->brand ?? '-' }} / {{ $asset->model ?? '-' }}</td>
                                        <td class="px-3 py-3 text-slate-500 font-mono text-xs">{{ $asset->serial_number ?? '-' }}</td>
                                        <td class="px-3 py-3 text-slate-600">{{ $asset->location ?: ($asset->factory ?: '-') }}</td>
                                        <td class="px-3 py-3 text-slate-600">{{ $asset->department->name ?? '-' }}</td>
                                        <td class="px-3 py-3">
                                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $condInfo['class'] }}">
                                                {{ $condInfo['label'] }}
                                            </span>
                                        </td>
                                    @endif
                                    <td class="px-3 py-3">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $statusInfo['class'] }}">
                                            {{ $statusInfo['label'] }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-3 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <a
                                                href="{{ route('assets.show', $asset) }}"
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-slate-700 hover:bg-slate-200 transition"
                                                title="View Detail"
                                            >
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0z" />
                                                    <circle cx="12" cy="12" r="3" />
                                                </svg>
                                            </a>
                                            @if ($asset->source_type === 'manual')
                                                <a
                                                    href="{{ route('admin.assets.manual.edit', $asset) }}"
                                                    class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-emerald-600 text-white shadow-sm hover:bg-emerald-700 transition"
                                                    title="Edit manual asset"
                                                >
                                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M12 20h9" />
                                                        <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5Z" />
                                                    </svg>
                                                </a>
                                                <form
                                                    method="POST"
                                                    action="{{ route('admin.assets.manual.destroy', $asset) }}"
                                                    onsubmit="return confirm('Are you sure you want to delete this asset?')"
                                                    class="inline"
                                                >
                                                    @csrf
                                                    @method('DELETE')
                                                    <button
                                                        type="submit"
                                                        class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-slate-200 text-red-600 hover:bg-red-50 transition"
                                                        title="Delete asset"
                                                    >
                                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                                            <path d="M3 6h18" />
                                                            <path d="M9 6V4.5A1.5 1.5 0 0 1 10.5 3h3A1.5 1.5 0 0 1 15 4.5V6m2 0v14a1 1 0 0 1-1 1H8a1 1 0 0 1-1-1V6h10Z" />
                                                        </svg>
                                                    </button>
                                                </form>
                                            @else
                                                <a
                                                    href="{{ route('assets.edit', $asset) }}"
                                                    class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-indigo-600 text-white shadow-sm hover:bg-indigo-700 transition"
                                                    title="Edit PC"
                                                >
                                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M12 20h9" />
                                                        <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5Z" />
                                                    </svg>
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-4 py-12 text-center text-slate-400">
                                        No assets found matching the filters.
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
