<x-app-layout>
    <div class="min-h-screen bg-slate-50 pb-10 pt-5">
        <div class="mx-auto w-full space-y-6">
            <!-- Header -->
            <section class="rounded-3xl border border-slate-200/80 bg-gradient-to-br from-white via-white to-emerald-50/60 p-4 shadow-md shadow-emerald-500/10 lg:p-6">
                <div class="flex flex-col gap-4">
                    <div class="space-y-2">
                        <p class="text-xs uppercase tracking-[0.35em] text-emerald-600/80">Asset Management Center</p>
                        <h1 class="text-3xl font-semibold text-slate-900">Asset Audit Log</h1>
                        <p class="text-sm text-slate-600">Track and review all system asset modifications, syncing history, and creation logs.</p>
                    </div>
                </div>
            </section>

            <!-- Filters & Search -->
            <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
                <form method="GET" action="{{ route('admin.assets.audit-log') }}" class="grid gap-4 sm:grid-cols-4 items-end">
                    <div class="sm:col-span-2 space-y-1">
                        <label class="text-xs font-semibold tracking-wider text-slate-500 uppercase">Search logs</label>
                        <input
                            type="search"
                            name="search"
                            value="{{ $search ?? '' }}"
                            placeholder="Search by asset code, name, action, or actor..."
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-800 shadow-inner focus:border-emerald-400 focus:bg-white focus:outline-none"
                        >
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs font-semibold tracking-wider text-slate-500 uppercase">Action Type</label>
                        <select name="action" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-800 focus:outline-none">
                            <option value="">All Actions</option>
                            @foreach($actions as $act)
                                <option value="{{ $act }}" @selected(($action ?? '') === $act)>{{ ucfirst($act) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="w-full inline-flex h-11 items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-5 text-sm font-semibold text-white shadow-md hover:bg-emerald-700 transition">
                            Apply Filter
                        </button>
                        @if($search || $action)
                            <a href="{{ route('admin.assets.audit-log') }}" class="inline-flex h-11 items-center justify-center rounded-2xl border border-slate-200 px-4 text-sm font-semibold text-slate-600 hover:bg-slate-50">
                                Reset
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            <!-- Audit Table -->
            <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto p-4">
                    <table class="w-full table-auto text-sm border-separate border-spacing-y-2">
                        <thead>
                            <tr class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                <th class="px-4 py-3">Timestamp</th>
                                <th class="px-4 py-3">Action</th>
                                <th class="px-4 py-3">Actor</th>
                                <th class="px-4 py-3">Asset</th>
                                <th class="px-4 py-3">Details / Changes</th>
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
                                                'restored' => 'bg-sky-50 text-sky-700 ring-1 ring-sky-200',
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
                                    <td class="px-4 py-3">
                                        @if($alog->asset)
                                            <div class="font-medium text-slate-900">{{ $alog->asset->name }}</div>
                                            <div class="text-xs text-slate-400 font-mono mt-0.5">{{ $alog->asset->asset_code }}</div>
                                        @else
                                            <div class="text-slate-400 italic">Deleted Asset</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-slate-600">
                                        @if($alog->notes)
                                            <div class="text-sm font-medium text-slate-800 mb-1">{{ $alog->notes }}</div>
                                        @endif

                                        @if(!empty($alog->metadata) && is_array($alog->metadata))
                                            @if(isset($alog->metadata['changes']) || isset($alog->metadata['previous']))
                                                <div class="mt-1 text-xs text-slate-500 bg-slate-50 p-2 rounded-xl border border-slate-100 max-w-lg">
                                                    @foreach(($alog->metadata['changes'] ?? []) as $field => $newValue)
                                                        @php
                                                            $oldValue = $alog->metadata['previous'][$field] ?? 'null';
                                                        @endphp
                                                        <div class="flex flex-wrap items-center gap-1 py-0.5">
                                                            <span class="font-semibold text-slate-600 font-mono">{{ $field }}:</span>
                                                            <span class="line-through text-slate-400 font-mono">{{ is_array($oldValue) ? json_encode($oldValue) : $oldValue }}</span>
                                                            <svg class="h-2.5 w-2.5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                                                            <span class="font-bold text-slate-700 font-mono">{{ is_array($newValue) ? json_encode($newValue) : $newValue }}</span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @else
                                                <div class="mt-1 text-xs text-slate-400 font-mono">
                                                    {{ json_encode($alog->metadata) }}
                                                </div>
                                            @endif
                                        @endif
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
</x-app-layout>
