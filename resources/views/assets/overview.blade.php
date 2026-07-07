<x-app-layout>
    <div class="min-h-screen bg-slate-50/50 pb-8 pt-2">
        <div class="mx-auto w-full space-y-5 lg:space-y-6">
            @php
                $mobileCategories = [
                    ['route' => 'admin.assets.pc', 'label' => 'PC', 'count' => $totalPc],
                    ['route' => 'admin.assets.laptop', 'label' => 'Laptop', 'count' => $totalLaptop],
                    ['route' => 'admin.assets.monitor', 'label' => 'Monitor', 'count' => $totalMonitor],
                    ['route' => 'admin.assets.printer-scanner', 'label' => 'Printer', 'count' => $totalPrinterScanner],
                    ['route' => 'admin.assets.network-device', 'label' => 'Network', 'count' => $totalNetwork],
                    ['route' => 'admin.assets.cctv', 'label' => 'CCTV', 'count' => $totalCctv],
                    ['route' => 'admin.assets.peripheral', 'label' => 'Peripheral', 'count' => $totalPeripheral],
                    ['route' => 'admin.assets.software-license', 'label' => 'License', 'count' => $totalLicense],
                ];
            @endphp
            <details class="group rounded-2xl border border-slate-200 bg-white shadow-sm md:hidden">
                <summary class="flex cursor-pointer list-none items-center justify-between px-4 py-3.5">
                    <div>
                        <p class="text-sm font-bold text-slate-900">Asset Categories</p>
                        <p class="text-xs text-slate-500">Quick access by device type</p>
                    </div>
                    <svg class="h-4 w-4 text-slate-400 transition group-open:rotate-180" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 10.94l3.71-3.71a.75.75 0 1 1 1.06 1.06l-4.24 4.24a.75.75 0 0 1-1.06 0L5.25 8.29a.75.75 0 0 1-.02-1.06Z" clip-rule="evenodd" />
                    </svg>
                </summary>
                <div class="grid grid-cols-2 gap-2 border-t border-slate-100 p-3">
                    @foreach ($mobileCategories as $category)
                        <a href="{{ route($category['route']) }}" class="flex items-center justify-between rounded-xl bg-slate-50 px-3 py-2.5 text-xs font-semibold text-slate-700 ring-1 ring-slate-100">
                            <span>{{ $category['label'] }}</span>
                            <span class="rounded-full bg-white px-2 py-0.5 text-emerald-700 ring-1 ring-slate-200">{{ number_format($category['count']) }}</span>
                        </a>
                    @endforeach
                </div>
            </details>

            <!-- CMDB Categories Section -->
            <section class="hidden rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm md:block lg:p-8">
                <div class="mb-8 space-y-1">
                    <h2 class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">CMDB Asset Categories</h2>
                    <p class="text-sm text-slate-500">Manage specific category inventories and build associations.</p>
                </div>

                <div class="grid gap-6 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4">
                    <!-- PC -->
                    <a href="{{ route('admin.assets.pc') }}" class="group relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-emerald-300 hover:shadow-md hover:shadow-emerald-500/5">
                        <div class="flex items-start justify-between">
                            <div class="space-y-1">
                                <span class="text-[10px] uppercase font-bold tracking-wider text-slate-400 group-hover:text-emerald-600 transition-colors">Category</span>
                                <h3 class="font-bold text-slate-800 text-lg group-hover:text-slate-950 transition-colors">PC</h3>
                            </div>
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-50 text-slate-500 transition-all duration-300 group-hover:bg-emerald-50 group-hover:text-emerald-600">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>
                                    <line x1="8" y1="21" x2="16" y2="21"/>
                                    <line x1="12" y1="17" x2="12" y2="21"/>
                                </svg>
                            </div>
                        </div>
                        <div class="flex items-baseline justify-between mt-6 pt-4 border-t border-slate-100">
                            <span class="text-2xl font-black text-slate-800">{{ number_format($totalPc) }}</span>
                            <span class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-600 opacity-0 group-hover:opacity-100 transition-opacity">
                                View Index
                                <svg class="h-3 w-3 transform transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/>
                                </svg>
                            </span>
                        </div>
                    </a>

                    <!-- Laptop -->
                    <a href="{{ route('admin.assets.laptop') }}" class="group relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-emerald-300 hover:shadow-md hover:shadow-emerald-500/5">
                        <div class="flex items-start justify-between">
                            <div class="space-y-1">
                                <span class="text-[10px] uppercase font-bold tracking-wider text-slate-400 group-hover:text-emerald-600 transition-colors">Category</span>
                                <h3 class="font-bold text-slate-800 text-lg group-hover:text-slate-950 transition-colors">Laptop</h3>
                            </div>
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-50 text-slate-500 transition-all duration-300 group-hover:bg-emerald-50 group-hover:text-emerald-600">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <rect x="3" y="4" width="18" height="12" rx="1.5" />
                                    <line x1="1" y1="20" x2="23" y2="20" />
                                    <path d="M5 16l-2 4h18l-2-4" />
                                </svg>
                            </div>
                        </div>
                        <div class="flex items-baseline justify-between mt-6 pt-4 border-t border-slate-100">
                            <span class="text-2xl font-black text-slate-800">{{ number_format($totalLaptop) }}</span>
                            <span class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-600 opacity-0 group-hover:opacity-100 transition-opacity">
                                View Index
                                <svg class="h-3 w-3 transform transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/>
                                </svg>
                            </span>
                        </div>
                    </a>

                    <!-- Monitor -->
                    <a href="{{ route('admin.assets.monitor') }}" class="group relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-emerald-300 hover:shadow-md hover:shadow-emerald-500/5">
                        <div class="flex items-start justify-between">
                            <div class="space-y-1">
                                <span class="text-[10px] uppercase font-bold tracking-wider text-slate-400 group-hover:text-emerald-600 transition-colors">Category</span>
                                <h3 class="font-bold text-slate-800 text-lg group-hover:text-slate-950 transition-colors">Monitor</h3>
                            </div>
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-50 text-slate-500 transition-all duration-300 group-hover:bg-emerald-50 group-hover:text-emerald-600">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <rect x="3" y="3" width="18" height="12" rx="2" ry="2"/>
                                    <line x1="9" y1="21" x2="15" y2="21"/>
                                    <line x1="12" y1="15" x2="12" y2="21"/>
                                </svg>
                            </div>
                        </div>
                        <div class="flex items-baseline justify-between mt-6 pt-4 border-t border-slate-100">
                            <span class="text-2xl font-black text-slate-800">{{ number_format($totalMonitor) }}</span>
                            <span class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-600 opacity-0 group-hover:opacity-100 transition-opacity">
                                View Index
                                <svg class="h-3 w-3 transform transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/>
                                </svg>
                            </span>
                        </div>
                    </a>

                    <!-- Printer & Scanner -->
                    <a href="{{ route('admin.assets.printer-scanner') }}" class="group relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-emerald-300 hover:shadow-md hover:shadow-emerald-500/5">
                        <div class="flex items-start justify-between">
                            <div class="space-y-1">
                                <span class="text-[10px] uppercase font-bold tracking-wider text-slate-400 group-hover:text-emerald-600 transition-colors">Category</span>
                                <h3 class="font-bold text-slate-800 text-lg group-hover:text-slate-950 transition-colors">Printer & Scanner</h3>
                            </div>
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-50 text-slate-500 transition-all duration-300 group-hover:bg-emerald-50 group-hover:text-emerald-600">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2m-12 0h12v8H6z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="flex items-baseline justify-between mt-6 pt-4 border-t border-slate-100">
                            <span class="text-2xl font-black text-slate-800">{{ number_format($totalPrinterScanner) }}</span>
                            <span class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-600 opacity-0 group-hover:opacity-100 transition-opacity">
                                View Index
                                <svg class="h-3 w-3 transform transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/>
                                </svg>
                            </span>
                        </div>
                    </a>

                    <!-- Network Device -->
                    <a href="{{ route('admin.assets.network-device') }}" class="group relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-emerald-300 hover:shadow-md hover:shadow-emerald-500/5">
                        <div class="flex items-start justify-between">
                            <div class="space-y-1">
                                <span class="text-[10px] uppercase font-bold tracking-wider text-slate-400 group-hover:text-emerald-600 transition-colors">Category</span>
                                <h3 class="font-bold text-slate-800 text-lg group-hover:text-slate-950 transition-colors">Network Device</h3>
                            </div>
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-50 text-slate-500 transition-all duration-300 group-hover:bg-emerald-50 group-hover:text-emerald-600">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <rect x="2" y="2" width="20" height="8" rx="2" ry="2"/>
                                    <rect x="2" y="14" width="20" height="8" rx="2" ry="2"/>
                                    <line x1="6" y1="6" x2="6.01" y2="6"/>
                                    <line x1="6" y1="18" x2="6.01" y2="18"/>
                                </svg>
                            </div>
                        </div>
                        <div class="flex items-baseline justify-between mt-6 pt-4 border-t border-slate-100">
                            <span class="text-2xl font-black text-slate-800">{{ number_format($totalNetwork) }}</span>
                            <span class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-600 opacity-0 group-hover:opacity-100 transition-opacity">
                                View Index
                                <svg class="h-3 w-3 transform transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/>
                                </svg>
                            </span>
                        </div>
                    </a>

                    <!-- CCTV -->
                    <a href="{{ route('admin.assets.cctv') }}" class="group relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-emerald-300 hover:shadow-md hover:shadow-emerald-500/5">
                        <div class="flex items-start justify-between">
                            <div class="space-y-1">
                                <span class="text-[10px] uppercase font-bold tracking-wider text-slate-400 group-hover:text-emerald-600 transition-colors">Category</span>
                                <h3 class="font-bold text-slate-800 text-lg group-hover:text-slate-950 transition-colors">CCTV</h3>
                            </div>
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-50 text-slate-500 transition-all duration-300 group-hover:bg-emerald-50 group-hover:text-emerald-600">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="flex items-baseline justify-between mt-6 pt-4 border-t border-slate-100">
                            <span class="text-2xl font-black text-slate-800">{{ number_format($totalCctv) }}</span>
                            <span class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-600 opacity-0 group-hover:opacity-100 transition-opacity">
                                View Index
                                <svg class="h-3 w-3 transform transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/>
                                </svg>
                            </span>
                        </div>
                    </a>

                    <!-- Peripheral -->
                    <a href="{{ route('admin.assets.peripheral') }}" class="group relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-emerald-300 hover:shadow-md hover:shadow-emerald-500/5">
                        <div class="flex items-start justify-between">
                            <div class="space-y-1">
                                <span class="text-[10px] uppercase font-bold tracking-wider text-slate-400 group-hover:text-emerald-600 transition-colors">Category</span>
                                <h3 class="font-bold text-slate-800 text-lg group-hover:text-slate-950 transition-colors">Peripheral</h3>
                            </div>
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-50 text-slate-500 transition-all duration-300 group-hover:bg-emerald-50 group-hover:text-emerald-600">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <rect x="5" y="2" width="14" height="20" rx="7" ry="7"/>
                                    <line x1="12" y1="2" x2="12" y2="12"/>
                                </svg>
                            </div>
                        </div>
                        <div class="flex items-baseline justify-between mt-6 pt-4 border-t border-slate-100">
                            <span class="text-2xl font-black text-slate-800">{{ number_format($totalPeripheral) }}</span>
                            <span class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-600 opacity-0 group-hover:opacity-100 transition-opacity">
                                View Index
                                <svg class="h-3 w-3 transform transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/>
                                </svg>
                            </span>
                        </div>
                    </a>

                    <!-- Software License -->
                    <a href="{{ route('admin.assets.software-license') }}" class="group relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-emerald-300 hover:shadow-md hover:shadow-emerald-500/5">
                        <div class="flex items-start justify-between">
                            <div class="space-y-1">
                                <span class="text-[10px] uppercase font-bold tracking-wider text-slate-400 group-hover:text-emerald-600 transition-colors">Category</span>
                                <h3 class="font-bold text-slate-800 text-lg group-hover:text-slate-950 transition-colors">Software License</h3>
                            </div>
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-50 text-slate-500 transition-all duration-300 group-hover:bg-emerald-50 group-hover:text-emerald-600">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 11-7.778 7.778 5.5 5.5 0 017.777-7.777zm0 0L15.5 7.5m0 0l3 3M15.5 7.5L14 9"/>
                                </svg>
                            </div>
                        </div>
                        <div class="flex items-baseline justify-between mt-6 pt-4 border-t border-slate-100">
                            <span class="text-2xl font-black text-slate-800">{{ number_format($totalLicense) }}</span>
                            <span class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-600 opacity-0 group-hover:opacity-100 transition-opacity">
                                View Index
                                <svg class="h-3 w-3 transform transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/>
                                </svg>
                            </span>
                        </div>
                    </a>
                </div>
            </section>

            <!-- Relationships & Recent Activities -->
            <div class="hidden gap-8 md:grid lg:grid-cols-2">
                <!-- Recently Connected CMDB Relations -->
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm lg:p-8">
                    <h3 class="text-lg font-bold text-slate-900 mb-6 flex items-center gap-2.5">
                        <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 shadow-sm shadow-emerald-500/5 ring-1 ring-emerald-100">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48" />
                            </svg>
                        </span>
                        Recent Connected CMDB Relations
                    </h3>
                    <div class="divide-y divide-slate-100">
                        @forelse($recentlyAttached as $rel)
                            <div class="py-4 flex items-center justify-between text-sm transition hover:bg-slate-50/50 px-2 rounded-xl">
                                <div class="space-y-1">
                                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600">{{ $rel->childAsset->name ?? 'Device' }}</span>
                                    <span class="text-xs text-slate-400">connected to</span>
                                    <a href="{{ route('assets.show', $rel->parentAsset) }}" class="font-semibold text-emerald-600 hover:text-emerald-700 hover:underline">{{ $rel->parentAsset->name ?? 'PC' }}</a>
                                </div>
                                <span class="text-xs text-slate-400 font-mono">{{ $rel->started_at ? $rel->started_at->diffForHumans() : '-' }}</span>
                            </div>
                        @empty
                            <div class="flex flex-col items-center justify-center py-10 text-slate-400 space-y-2">
                                <svg class="h-10 w-10 text-slate-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244"/>
                                </svg>
                                <span class="text-xs">No active relationship links.</span>
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Recent Activities Timeline -->
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm lg:p-8">
                    <h3 class="text-lg font-bold text-slate-900 mb-6 flex items-center gap-2.5">
                        <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 shadow-sm shadow-indigo-500/5 ring-1 ring-indigo-100">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </span>
                        Recent System Activities
                    </h3>
                    <div class="flow-root">
                        <ul role="list" class="-mb-8">
                            @forelse($recentLogs as $logIndex => $log)
                                <li>
                                    <div class="relative pb-8">
                                        @if($logIndex !== count($recentLogs) - 1)
                                            <span class="absolute left-4 top-4 -ml-px h-full w-0.5 bg-slate-100" aria-hidden="true"></span>
                                        @endif
                                        <div class="relative flex space-x-3.5">
                                            <div>
                                                @php
                                                    $logBg = match($log->action) {
                                                        'created' => 'bg-emerald-50 text-emerald-600 ring-1 ring-emerald-100',
                                                        'updated' => 'bg-amber-50 text-amber-600 ring-1 ring-amber-100',
                                                        'deleted' => 'bg-rose-50 text-rose-600 ring-1 ring-rose-100',
                                                        'synced' => 'bg-indigo-50 text-indigo-600 ring-1 ring-indigo-100',
                                                        default => 'bg-slate-50 text-slate-600 ring-1 ring-slate-100',
                                                    };
                                                    $iconPath = match($log->action) {
                                                        'created' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />',
                                                        'deleted' => '<path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.34 9m-4.78 0L9.3 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />',
                                                        default => '<path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />',
                                                    };
                                                @endphp
                                                <span class="flex h-8 w-8 items-center justify-center rounded-full ring-8 ring-white {{ $logBg }}">
                                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                        {!! $iconPath !!}
                                                    </svg>
                                                </span>
                                            </div>
                                            <div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
                                                <div>
                                                    <p class="text-xs text-slate-600">
                                                        <span class="font-bold text-slate-800">{{ $log->actor->name ?? 'System' }}</span>
                                                        <span class="text-slate-500">{{ $log->notes }}</span>
                                                        @if($log->asset)
                                                            <a href="{{ route('assets.show', $log->asset) }}" class="font-bold text-emerald-600 hover:text-emerald-700 font-mono bg-emerald-50/50 px-1.5 py-0.5 rounded border border-emerald-100/50">
                                                                {{ $log->asset->asset_code }}
                                                            </a>
                                                        @endif
                                                    </p>
                                                </div>
                                                <div class="whitespace-nowrap text-right text-[10px] text-slate-400 font-medium">
                                                    <time>{{ $log->created_at->diffForHumans() }}</time>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            @empty
                                <div class="flex flex-col items-center justify-center py-10 text-slate-400 space-y-2">
                                    <svg class="h-10 w-10 text-slate-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                                    </svg>
                                    <span class="text-xs">No activity logs recorded.</span>
                                </div>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>

            @include('assets.partials.dashboard-inventory')
        </div>
    </div>
</x-app-layout>
