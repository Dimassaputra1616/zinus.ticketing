<x-app-layout>
    <div class="min-h-screen bg-slate-50 pb-10 pt-5">
        <div class="mx-auto w-full space-y-6">
            <!-- Header -->
            <section class="rounded-3xl border border-slate-200/80 bg-gradient-to-br from-white via-white to-emerald-50/60 p-4 shadow-md shadow-emerald-500/10 lg:p-6">
                <div class="flex flex-col gap-4">
                    <div class="space-y-2">
                        <p class="text-xs uppercase tracking-[0.35em] text-emerald-600/80">Asset Management Center</p>
                        <h1 class="text-3xl font-semibold text-slate-900">Assignment Log & Audit Trail</h1>
                        <p class="text-sm text-slate-600">Track and review employee borrowings, assignments, and structural updates on all system hardware.</p>
                    </div>
                </div>
            </section>

            <!-- Search Form -->
            <div class="rounded-3xl border border-slate-200/80 bg-white p-4 shadow-sm">
                <form method="GET" action="{{ route('admin.assets.assignment') }}" class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <div class="relative flex-1">
                        <input
                            type="search"
                            name="search"
                            value="{{ $search ?? '' }}"
                            placeholder="Search logs by asset code, reason, employee, status or actor name..."
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 pl-11 text-sm text-slate-800 shadow-inner placeholder:text-slate-400 focus:border-emerald-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-100"
                        >
                        <svg class="absolute left-3 top-3 h-5 w-5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8" />
                            <path d="m21 21-4.35-4.35" />
                        </svg>
                    </div>
                    <button
                        type="submit"
                        class="inline-flex h-11 items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-5 text-sm font-semibold text-white shadow-md hover:bg-emerald-700 transition"
                    >
                        Search Log
                    </button>
                    @if($search)
                        <a href="{{ route('admin.assets.assignment') }}" class="inline-flex h-11 items-center justify-center rounded-2xl border border-slate-200 px-4 text-sm font-semibold text-slate-600 hover:bg-slate-50">
                            Clear
                        </a>
                    @endif
                </form>
            </div>

            <!-- Tabbed view using Alpine JS -->
            <div x-data="{ activeTab: 'borrow' }" class="space-y-4">
                <!-- Tab headers -->
                <div class="flex border-b border-slate-200">
                    <button
                        @click="activeTab = 'borrow'"
                        :class="activeTab === 'borrow' ? 'border-emerald-600 text-emerald-600 font-bold border-b-2' : 'border-transparent text-slate-500 hover:text-slate-700'"
                        class="px-6 py-3 text-sm transition focus:outline-none"
                    >
                        Borrowings & Assignments
                    </button>
                    <button
                        @click="activeTab = 'audit'"
                        :class="activeTab === 'audit' ? 'border-emerald-600 text-emerald-600 font-bold border-b-2' : 'border-transparent text-slate-500 hover:text-slate-700'"
                        class="px-6 py-3 text-sm transition focus:outline-none"
                    >
                        General Audit Trail (Asset Logs)
                    </button>
                </div>

                <!-- Tab content: Borrow logs -->
                <div x-show="activeTab === 'borrow'" class="space-y-4">
                    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                        <div class="overflow-x-auto p-4">
                            <table class="w-full table-auto text-sm border-separate border-spacing-y-2">
                                <thead>
                                    <tr class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                        <th class="px-4 py-3">Date</th>
                                        <th class="px-4 py-3">User</th>
                                        <th class="px-4 py-3">Department</th>
                                        <th class="px-4 py-3">Asset Code</th>
                                        <th class="px-4 py-3">Device / Reason</th>
                                        <th class="px-4 py-3">Processed By</th>
                                        <th class="px-4 py-3">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($borrowLogs as $blog)
                                        <tr class="bg-white hover:bg-slate-50/50 rounded-2xl border shadow-sm transition">
                                            <td class="whitespace-nowrap px-4 py-3 font-semibold text-slate-900">
                                                {{ $blog->created_at->format('d M Y H:i') }}
                                            </td>
                                            <td class="px-4 py-3 font-medium text-slate-900">
                                                {{ $blog->user->name ?? 'System' }}
                                            </td>
                                            <td class="px-4 py-3 text-slate-600">
                                                {{ $blog->department->name ?? '-' }}
                                            </td>
                                            <td class="px-4 py-3 font-mono font-bold text-emerald-600">
                                                {{ $blog->asset_code }}
                                            </td>
                                            <td class="px-4 py-3 text-slate-600">
                                                <div class="font-medium text-slate-800">{{ $blog->asset->name ?? $blog->device->name ?? 'Deleted hardware' }}</div>
                                                <div class="text-xs text-slate-400 mt-0.5">Reason: {{ $blog->reason ?? '-' }}</div>
                                            </td>
                                            <td class="px-4 py-3 text-slate-500">
                                                {{ $blog->processedBy->name ?? '-' }}
                                            </td>
                                            <td class="px-4 py-3">
                                                @php
                                                    $statuses = [
                                                        'waiting' => ['label' => 'Waiting', 'class' => 'bg-amber-50 text-amber-700 ring-1 ring-amber-200'],
                                                        'approved' => ['label' => 'Active Loan', 'class' => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200'],
                                                        'returned' => ['label' => 'Returned', 'class' => 'bg-slate-100 text-slate-600 ring-1 ring-slate-200'],
                                                        'rejected' => ['label' => 'Rejected', 'class' => 'bg-rose-50 text-rose-700 ring-1 ring-rose-200'],
                                                    ];
                                                    $st = $statuses[$blog->status] ?? ['label' => ucfirst($blog->status), 'class' => 'bg-slate-100 text-slate-600 ring-1 ring-slate-200'];
                                                @endphp
                                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $st['class'] }}">
                                                    {{ $st['label'] }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="px-4 py-8 text-center text-slate-400">
                                                No borrow logs recorded.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="bg-slate-50 px-6 py-4 border-t border-slate-100">
                            {{ $borrowLogs->links() }}
                        </div>
                    </section>
                </div>

                <!-- Tab content: Audit logs -->
                <div x-show="activeTab === 'audit'" class="space-y-4" style="display: none;">
                    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                        <div class="overflow-x-auto p-4">
                            <table class="w-full table-auto text-sm border-separate border-spacing-y-2">
                                <thead>
                                    <tr class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                        <th class="px-4 py-3">Timestamp</th>
                                        <th class="px-4 py-3">Action</th>
                                        <th class="px-4 py-3">Actor</th>
                                        <th class="px-4 py-3">Asset Code</th>
                                        <th class="px-4 py-3">Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($auditLogs as $alog)
                                        <tr class="bg-white hover:bg-slate-50/50 rounded-2xl border shadow-sm transition">
                                            <td class="whitespace-nowrap px-4 py-3 text-slate-500 font-mono text-xs">
                                                {{ $alog->created_at->format('d M Y H:i:s') }}
                                            </td>
                                            <td class="px-4 py-3">
                                                @php
                                                    $actColor = match($alog->action) {
                                                        'created' => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200',
                                                        'updated' => 'bg-amber-50 text-amber-700 ring-1 ring-amber-200',
                                                        'deleted' => 'bg-rose-50 text-rose-700 ring-1 ring-rose-200',
                                                        'synced' => 'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-200',
                                                        default => 'bg-slate-50 text-slate-600 ring-1 ring-slate-200',
                                                    };
                                                @endphp
                                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold uppercase {{ $actColor }}">
                                                    {{ $alog->action }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 font-semibold text-slate-900">
                                                {{ $alog->actor->name ?? 'System Process' }}
                                            </td>
                                            <td class="px-4 py-3 font-mono font-bold text-slate-700">
                                                {{ $alog->asset->asset_code ?? 'DELETED' }}
                                            </td>
                                            <td class="px-4 py-3 text-slate-600">
                                                {{ $alog->notes }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="px-4 py-8 text-center text-slate-400">
                                                No audit log history recorded.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="bg-slate-50 px-6 py-4 border-t border-slate-100">
                            {{ $auditLogs->links() }}
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
