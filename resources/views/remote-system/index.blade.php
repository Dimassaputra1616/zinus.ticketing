<x-app-layout>
    <div class="w-full pt-0 pb-8 space-y-6">

        {{-- Search Bar --}}
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold text-slate-800 tracking-tight">{{ __('messages.remote_system') }}</h2>
                <p class="text-sm text-slate-500 mt-0.5">{{ __('messages.desc_remote_system') }}</p>
            </div>
            <div class="relative w-full sm:w-80">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                <input
                    type="search"
                    id="remote-search"
                    placeholder="Cari perangkat..."
                    class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-4 text-sm text-slate-700 shadow-sm placeholder:text-slate-400 focus:border-emerald-300 focus:ring-2 focus:ring-emerald-100 transition"
                    oninput="filterDevices(this.value)"
                />
            </div>
        </div>

        {{-- Device Cards Grid --}}
        <div id="device-grid" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
            @forelse($assets as $asset)
                @php
                    $isOnline = $asset->status === 'in_use';
                    $statusColor = $isOnline ? 'emerald' : 'slate';
                    $statusLabel = $isOnline ? __('messages.online') : __('messages.offline');
                    $hasRustdesk = !empty($asset->rustdesk_id);
                @endphp
                <div class="device-card rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm hover:shadow-md transition-all duration-200 hover:-translate-y-0.5 group"
                     data-name="{{ strtolower($asset->name . ' ' . ($asset->hostname ?? '') . ' ' . (optional($asset->user)->name ?? '')) }}">
                    
                    {{-- Header --}}
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-slate-600 group-hover:bg-indigo-50 group-hover:text-indigo-600 transition-colors">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>
                                    <line x1="8" y1="21" x2="16" y2="21"/>
                                    <line x1="12" y1="17" x2="12" y2="21"/>
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <h3 class="text-sm font-bold text-slate-800 truncate group-hover:text-indigo-700 transition-colors">{{ $asset->name }}</h3>
                                @if($asset->hostname)
                                    <p class="text-[11px] text-slate-400 font-mono truncate">{{ $asset->hostname }}</p>
                                @endif
                            </div>
                        </div>
                        {{-- Online/Offline Badge --}}
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-{{ $statusColor }}-50 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-{{ $statusColor }}-600 border border-{{ $statusColor }}-200/60 shrink-0">
                            <span class="h-1.5 w-1.5 rounded-full bg-{{ $statusColor }}-500 {{ $isOnline ? 'animate-pulse' : '' }}"></span>
                            {{ $statusLabel }}
                        </span>
                    </div>

                    {{-- Device Details --}}
                    <div class="space-y-2.5 mb-4">
                        {{-- Assigned User --}}
                        <div class="flex items-center gap-2.5 text-sm">
                            <svg class="h-4 w-4 text-slate-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            <span class="text-slate-600 truncate">{{ optional($asset->user)->name ?? '—' }}</span>
                        </div>
                        {{-- Department --}}
                        <div class="flex items-center gap-2.5 text-sm">
                            <svg class="h-4 w-4 text-slate-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                            <span class="text-slate-600 truncate">{{ optional($asset->department)->name ?? '—' }}</span>
                        </div>
                        {{-- IP Address --}}
                        @if($asset->ip_address)
                        <div class="flex items-center gap-2.5 text-sm">
                            <svg class="h-4 w-4 text-slate-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                            <span class="text-slate-600 font-mono text-xs">{{ $asset->ip_address }}</span>
                        </div>
                        @endif
                        {{-- RustDesk ID --}}
                        <div class="flex items-center gap-2.5 text-sm">
                            <svg class="h-4 w-4 text-slate-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                            @if($hasRustdesk)
                                <span class="text-slate-700 font-mono text-xs font-semibold bg-slate-100 px-2 py-0.5 rounded">{{ $asset->rustdesk_id }}</span>
                            @else
                                <span class="text-slate-400 text-xs italic">{{ __('messages.no_rustdesk_id') }}</span>
                            @endif
                        </div>
                    </div>

                    {{-- Connect Remote Button --}}
                    @if($hasRustdesk)
                        <a
                            href="rustdesk://connection/new/{{ $asset->rustdesk_id }}"
                            class="flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-indigo-600 to-indigo-500 px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-indigo-500/20 transition hover:from-indigo-700 hover:to-indigo-600 hover:shadow-lg hover:-translate-y-0.5 active:translate-y-0"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                            {{ __('messages.connect_remote') }}
                        </a>
                    @else
                        <button
                            type="button"
                            disabled
                            class="flex w-full items-center justify-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-semibold text-slate-400 cursor-not-allowed"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                            {{ __('messages.connect_remote') }}
                        </button>
                    @endif
                </div>
            @empty
                <div class="col-span-full">
                    <div class="mx-auto flex max-w-md flex-col items-center gap-3 rounded-2xl bg-white px-8 py-12 text-center text-sm text-slate-500 shadow-sm border border-slate-200/80">
                        <div class="h-16 w-16 rounded-full bg-slate-100 flex items-center justify-center mb-2">
                            <svg class="h-8 w-8 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                        </div>
                        <p class="text-sm font-semibold text-slate-700">Belum ada perangkat terdaftar</p>
                        <p class="text-xs text-slate-500">Tambahkan asset terlebih dahulu melalui menu Manage Assets.</p>
                    </div>
                </div>
            @endforelse
        </div>
    </div>

    <script>
        function filterDevices(query) {
            const cards = document.querySelectorAll('.device-card');
            const q = query.toLowerCase().trim();
            cards.forEach(card => {
                const name = card.getAttribute('data-name') || '';
                card.style.display = name.includes(q) ? '' : 'none';
            });
        }
    </script>
</x-app-layout>
