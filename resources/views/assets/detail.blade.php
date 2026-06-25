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
            x-init="setTimeout(() => open = false, 3000)"
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

    @if (session('error'))
        <div
            x-data="{ open: true }"
            x-init="setTimeout(() => open = false, 3000)"
            x-show="open"
            x-transition
            class="fixed right-4 top-4 z-50 flex items-start gap-3 rounded-2xl bg-rose-600 px-4 py-3 text-white shadow-xl shadow-rose-500/30"
        >
            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-white/15">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 6 6 18M6 6l12 12" />
                </svg>
            </div>
            <div>
                <p class="text-sm font-semibold">Error</p>
                <p class="text-xs text-rose-50">{{ session('error') }}</p>
            </div>
        </div>
    @endif

    <div class="min-h-screen bg-slate-50 pb-10 pt-5">
        <div class="max-w-7xl mx-auto space-y-6">
            <!-- Header/Navigation Breadcrumbs -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2 text-xs font-semibold text-slate-500">
                    <a href="{{ route('admin.assets.overview') }}" class="hover:text-emerald-600">Asset Center</a>
                    <span>/</span>
                    <span class="text-slate-900">{{ $asset->asset_code }}</span>
                </div>
                <div class="flex items-center gap-2">
                    @if ($asset->source_type === 'manual')
                        <a
                            href="{{ route('admin.assets.manual.edit', $asset) }}"
                            class="inline-flex h-9 items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700 transition"
                        >
                            Edit Asset Details
                        </a>
                    @else
                        <a
                            href="{{ route('assets.edit', $asset) }}"
                            class="inline-flex h-9 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 text-xs font-semibold text-white shadow-sm hover:bg-indigo-700 transition"
                        >
                            Edit PC Details
                        </a>
                    @endif
                </div>
            </div>

            <!-- Asset Identity Card -->
            <section class="rounded-3xl border border-slate-200/80 bg-gradient-to-br from-white via-white to-emerald-50/60 p-6 shadow-md shadow-emerald-500/5">
                <div class="flex flex-col md:flex-row justify-between gap-6">
                    <div class="space-y-4">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full bg-emerald-100 px-3 py-0.5 text-xs font-bold uppercase tracking-wider text-emerald-800">{{ $asset->category }}</span>
                            @if($asset->sub_category)
                                <span class="rounded-full bg-slate-100 px-3 py-0.5 text-xs font-semibold text-slate-700">{{ $asset->sub_category }}</span>
                            @endif
                            <span class="text-xs text-slate-400 font-mono">Source: {{ ucfirst($asset->source_type ?? 'agent') }}</span>
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-slate-950">{{ $asset->name }}</h1>
                            @if($asset->hostname)
                                <p class="text-sm font-mono text-slate-500 mt-1">Hostname: {{ $asset->hostname }}</p>
                            @endif
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 pt-2">
                            <div>
                                <span class="block text-[10px] uppercase font-bold tracking-wider text-slate-400">Asset Code</span>
                                <span class="text-sm font-semibold text-slate-800">{{ $asset->asset_code }}</span>
                            </div>
                            <div>
                                <span class="block text-[10px] uppercase font-bold tracking-wider text-slate-400">Serial Number</span>
                                <span class="text-sm font-mono text-slate-700">{{ $asset->serial_number ?: '-' }}</span>
                            </div>
                            <div>
                                <span class="block text-[10px] uppercase font-bold tracking-wider text-slate-400">Status</span>
                                <span class="inline-flex mt-0.5 items-center rounded-full px-2 py-0.5 text-xs font-semibold {{ $statusMeta[strtolower($asset->status)]['class'] ?? 'bg-slate-100' }}">
                                    {{ $statusMeta[strtolower($asset->status)]['label'] ?? ucfirst($asset->status) }}
                                </span>
                            </div>
                            <div>
                                <span class="block text-[10px] uppercase font-bold tracking-wider text-slate-400">Condition</span>
                                <span class="inline-flex mt-0.5 items-center rounded-full px-2 py-0.5 text-xs font-semibold {{ $conditionMeta[$asset->condition]['class'] ?? 'bg-slate-100' }}">
                                    {{ $conditionMeta[$asset->condition]['label'] ?? ucfirst($asset->condition) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Side Stats / Quick Info -->
                    <div class="border-t md:border-t-0 md:border-l border-slate-200/80 pt-4 md:pt-0 md:pl-6 flex flex-col justify-center gap-3 min-w-[220px]">
                        <div>
                            <span class="block text-[10px] uppercase font-bold tracking-wider text-slate-400">Department Assignment</span>
                            <span class="text-sm font-semibold text-slate-900">{{ $asset->department->name ?? 'No Department' }}</span>
                        </div>
                        <div>
                            <span class="block text-[10px] uppercase font-bold tracking-wider text-slate-400">Assigned User</span>
                            <span class="text-sm font-semibold text-slate-900">{{ $asset->user->name ?? 'Unassigned' }}</span>
                        </div>
                        <div>
                            <span class="block text-[10px] uppercase font-bold tracking-wider text-slate-400">Location</span>
                            <span class="text-sm font-semibold text-slate-900">{{ $asset->location ?: ($asset->factory ?: '-') }}</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Relationships & Attachments Panel -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Attachment actions / Active relations status -->
                <div class="lg:col-span-2 space-y-6">
                    <section class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm">
                        <h2 class="text-lg font-bold text-slate-900 mb-4 flex items-center gap-2">
                            <svg class="h-5 w-5 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48" />
                            </svg>
                            Relationship & Attachments
                        </h2>

                        @if ($isParentCategory)
                            <!-- This asset is a parent (PC/Laptop) -->
                            <div class="space-y-6">
                                <div>
                                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">Active Attached Child Assets</h3>
                                    @if ($attachedAssets->isEmpty())
                                        <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50/50 p-6 text-center text-sm text-slate-500">
                                            No devices attached to this PC.
                                        </div>
                                    @else
                                        <div class="divide-y divide-slate-100">
                                            @foreach ($attachedAssets as $child)
                                                <div class="py-3 flex items-center justify-between gap-4">
                                                    <div>
                                                        <a href="{{ route('assets.show', $child) }}" class="font-semibold text-slate-900 hover:text-emerald-600 hover:underline">
                                                            {{ $child->name }}
                                                        </a>
                                                        <div class="text-xs text-slate-500 font-mono mt-0.5">
                                                            {{ $child->asset_code }} • Serial: {{ $child->serial_number ?: '-' }} • Category: {{ $child->category }}
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <form
                                                            method="POST"
                                                            action="{{ route('admin.assets.relations.detach', $child->pivot->id) }}"
                                                            class="inline"
                                                        >
                                                            @csrf
                                                            @method('PATCH')
                                                            <button
                                                                type="submit"
                                                                class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-red-600 shadow-sm hover:border-red-200 hover:bg-red-50 transition"
                                                            >
                                                                Detach Device
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>

                                <!-- Form to Attach new device -->
                                <div class="border-t border-slate-100 pt-6">
                                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">Attach a Device / License</h3>
                                    <form method="POST" action="{{ route('admin.assets.relations.attach', $asset) }}" class="flex flex-col sm:flex-row gap-3">
                                        @csrf
                                        <div class="flex-1">
                                            <select
                                                name="child_asset_id"
                                                required
                                                class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:bg-white focus:outline-none"
                                            >
                                                <option value="" disabled selected>Select available Monitor, License, or Peripheral...</option>
                                                @foreach ($attachableAssets as $avail)
                                                    <option value="{{ $avail->id }}">
                                                        [{{ $avail->category }}] {{ $avail->name }} ({{ $avail->asset_code }} - S/N: {{ $avail->serial_number ?: 'N/A' }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="sm:w-1/3">
                                            <input
                                                type="text"
                                                name="notes"
                                                placeholder="Relation notes (optional)..."
                                                class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:bg-white focus:outline-none"
                                            >
                                        </div>
                                        <button
                                            type="submit"
                                            class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-md hover:bg-emerald-700 transition"
                                        >
                                            Attach
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @else
                            <!-- This asset is a child device (Monitor, License, Peripheral, etc.) -->
                            <div>
                                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">Host PC / Laptop Association</h3>
                                @if ($parentAsset)
                                    <div class="rounded-2xl border border-emerald-100 bg-emerald-50/45 p-4 flex items-center justify-between gap-4">
                                        <div>
                                            <p class="text-xs text-emerald-700/80 uppercase font-bold tracking-wider">Currently Attached To:</p>
                                            <a href="{{ route('assets.show', $parentAsset) }}" class="font-bold text-slate-900 hover:text-emerald-700 hover:underline text-lg">
                                                {{ $parentAsset->name }}
                                            </a>
                                            <div class="text-xs text-slate-500 font-mono mt-1">
                                                Code: {{ $parentAsset->asset_code }} • Serial: {{ $parentAsset->serial_number ?: '-' }} • Assigned: {{ $parentAsset->user->name ?? 'Unassigned' }}
                                            </div>
                                        </div>
                                        <div>
                                            <form
                                                method="POST"
                                                action="{{ route('admin.assets.relations.detach', $activeParentRelation->id) }}"
                                                class="inline"
                                            >
                                                @csrf
                                                @method('PATCH')
                                                <button
                                                    type="submit"
                                                    class="rounded-xl border border-red-200 bg-white px-3 py-1.5 text-xs font-semibold text-red-600 shadow-sm hover:bg-red-50 transition"
                                                >
                                                    Detach From PC
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                @else
                                    <div class="space-y-4">
                                        <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50/50 p-6 text-center text-sm text-slate-500">
                                            This device is not currently attached to any host PC/Laptop. It acts as spare stock.
                                        </div>

                                        <div class="border-t border-slate-100 pt-4">
                                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">Attach to a Host PC / Laptop</h3>
                                            <form method="POST" action="{{ route('admin.assets.relations.attach-parent', $asset) }}" class="flex flex-col sm:flex-row gap-3">
                                                @csrf
                                                <div class="flex-1">
                                                    <select
                                                        name="parent_asset_id"
                                                        required
                                                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:bg-white focus:outline-none"
                                                    >
                                                        <option value="" disabled selected>Select active PC or Laptop...</option>
                                                        @foreach ($attachableParents as $parent)
                                                            <option value="{{ $parent->id }}">
                                                                [{{ $parent->category }}] {{ $parent->name }} ({{ $parent->asset_code }} - S/N: {{ $parent->serial_number ?: 'N/A' }})
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="sm:w-1/3">
                                                    <input
                                                        type="text"
                                                        name="notes"
                                                        placeholder="Relation notes (optional)..."
                                                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:bg-white focus:outline-none"
                                                    >
                                                </div>
                                                <button
                                                    type="submit"
                                                    class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-md hover:bg-emerald-700 transition"
                                                >
                                                    Attach
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </section>

                    <!-- Relation History Log -->
                    <section class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm">
                        <h2 class="text-lg font-bold text-slate-900 mb-4 flex items-center gap-2">
                            <svg class="h-5 w-5 text-indigo-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Relationship Movement History
                        </h2>
                        @if ($relationHistory->isEmpty())
                            <div class="text-center py-6 text-sm text-slate-500">No relationship history records.</div>
                        @else
                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-sm">
                                    <thead>
                                        <tr class="border-b border-slate-100 text-xs font-bold uppercase tracking-wider text-slate-400">
                                            <th class="py-2">Relationship Detail</th>
                                            <th class="py-2">Attached At</th>
                                            <th class="py-2">Detached At</th>
                                            <th class="py-2">Notes</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @foreach ($relationHistory as $hist)
                                            <tr>
                                                <td class="py-3">
                                                    @if ($asset->id === $hist->parent_asset_id)
                                                        <span class="text-xs font-semibold text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded">Child:</span>
                                                        <a href="{{ route('assets.show', $hist->childAsset) }}" class="font-medium text-slate-900 hover:underline">
                                                            {{ $hist->childAsset->name }}
                                                        </a>
                                                    @else
                                                        <span class="text-xs font-semibold text-indigo-700 bg-indigo-50 px-1.5 py-0.5 rounded">Host:</span>
                                                        <a href="{{ route('assets.show', $hist->parentAsset) }}" class="font-medium text-slate-900 hover:underline">
                                                            {{ $hist->parentAsset->name }}
                                                        </a>
                                                    @endif
                                                </td>
                                                <td class="py-3 text-xs text-slate-500">{{ $hist->started_at ? $hist->started_at->toDateTimeString() : '-' }}</td>
                                                <td class="py-3 text-xs text-slate-500">
                                                    @if ($hist->ended_at)
                                                        <span class="text-slate-700 font-semibold">{{ $hist->ended_at->toDateTimeString() }}</span>
                                                    @else
                                                        <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-800">Active</span>
                                                    @endif
                                                </td>
                                                <td class="py-3 text-xs text-slate-600">{{ $hist->notes ?: '-' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </section>
                </div>

                <!-- Mutation/Audit Log Sidepanel -->
                <div class="space-y-6">
                    <section class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm h-full">
                        <h2 class="text-lg font-bold text-slate-900 mb-4 flex items-center gap-2">
                            <svg class="h-5 w-5 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            Asset Mutation History
                        </h2>
                        @if ($mutationHistory->isEmpty())
                            <div class="text-center py-10 text-sm text-slate-500">No mutation logs found for this asset.</div>
                        @else
                            <div class="flow-root">
                                <ul class="-mb-8">
                                    @foreach ($mutationHistory as $idx => $log)
                                        <li>
                                            <div class="relative pb-8">
                                                @if ($idx !== count($mutationHistory) - 1)
                                                    <span class="absolute left-4 top-4 -ml-px h-full w-0.5 bg-slate-200" aria-hidden="true"></span>
                                                @endif
                                                <div class="relative flex space-x-3">
                                                    <div>
                                                        <span class="h-8 w-8 rounded-full bg-emerald-50 border border-emerald-200 flex items-center justify-center ring-8 ring-white text-emerald-700 font-bold text-xs uppercase">
                                                            {{ substr($log->action, 0, 1) }}
                                                        </span>
                                                    </div>
                                                    <div class="flex-1 min-w-0 pt-1.5">
                                                        <p class="text-xs font-semibold text-slate-800">
                                                            {{ ucfirst($log->action) }} <span class="font-normal text-slate-500">by {{ $log->actor->name ?? 'System' }}</span>
                                                        </p>
                                                        <p class="text-xs text-slate-600 mt-0.5 leading-relaxed">{{ $log->notes }}</p>
                                                        <p class="text-[10px] text-slate-400 mt-1">{{ $log->created_at->diffForHumans() }} ({{ $log->created_at->toDateTimeString() }})</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </section>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
