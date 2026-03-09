<x-app-layout>
    <div
        class="w-full pt-0 pb-8 space-y-6"
        data-live-refresh="true"
        data-live-url="{{ request()->url() }}"
        data-live-query="{{ http_build_query(request()->except('refresh')) }}"
        data-live-interval="8000"
        data-live-checksum="{{ $checksum }}"
    >
        @if (session('success'))
            <div class="flex items-start gap-3 rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-emerald-800 shadow-sm">
                <span class="mt-0.5 inline-flex h-10 w-10 items-center justify-center rounded-lg bg-white text-emerald-700 shadow-inner">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5" /></svg>
                </span>
                <div class="leading-relaxed">
                    <p class="text-sm font-semibold">Berhasil</p>
                    <p class="text-sm text-emerald-700/80">{{ session('success') }}</p>
                </div>
            </div>
        @endif

        <!-- Enterprise Header -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between pb-4 border-b border-slate-200/60">
            <div>
                <h1 class="heading-font text-xl sm:text-2xl font-bold text-slate-800 tracking-tight flex items-center gap-2">
                    IT Operations Center
                    <span class="relative flex h-3 w-3 items-center justify-center">
                      <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                      <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                    </span>
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">Enterprise Service Management & Infrastructure Overview</p>
            </div>
            <div class="mt-4 sm:mt-0 flex shrink-0 items-center justify-end gap-2 sm:gap-3 w-full sm:w-auto overflow-x-auto pb-1 sm:pb-0 scrollbar-hide">

                <a href="{{ route('users.index') }}" class="inline-flex shrink-0 items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 whitespace-nowrap hover:text-indigo-600 transition-colors">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    Team
                </a>
                <a href="{{ route('assets.index') }}" class="inline-flex shrink-0 items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 whitespace-nowrap hover:text-indigo-600 transition-colors">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                    Assets
                </a>
            </div>
        </div>

        @php
            // Trend helper
            $trendPct = function($today, $yesterday) {
                if ($yesterday == 0) return $today > 0 ? 100 : 0;
                return round((($today - $yesterday) / $yesterday) * 100);
            };
            $ticketsTrend = $trendPct($totalTicketsToday, $totalTicketsYesterday);
            $resolvedTrend = $trendPct($resolvedToday, $resolvedYesterday);
        @endphp

        <!-- KPI Cards — Premium Enterprise -->
        <div class="grid grid-cols-2 lg:grid-cols-6 gap-3.5">

            <!-- ① Total Tickets (All-Time) — Hero Card -->
            <div class="col-span-2 lg:col-span-2 relative overflow-hidden rounded-2xl p-5 shadow-sm border border-violet-200/60 bg-gradient-to-br from-violet-50 via-white to-indigo-50/50 group hover:shadow-lg hover:border-violet-300/80 transition-all duration-300">
                <div class="absolute -right-6 -top-6 h-28 w-28 rounded-full bg-gradient-to-br from-violet-200/40 to-indigo-200/20 blur-xl group-hover:scale-125 transition-transform duration-500"></div>
                <div class="relative">
                    <div class="flex items-center gap-2.5 mb-3">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-violet-500 to-indigo-600 text-white shadow-md shadow-violet-300/40">
                            <svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><path d="M22 6l-10 7L2 6"/></svg>
                        </span>
                        <h3 class="text-[10.5px] font-bold text-violet-600/80 uppercase tracking-[0.18em]">Total Tickets</h3>
                    </div>
                    <div class="flex items-end justify-between">
                        <div>
                            <span class="text-4xl font-extrabold text-slate-900 tracking-tight">{{ number_format($totalTickets) }}</span>
                            <p class="text-[10.5px] text-slate-400 mt-1 font-medium">All-time records</p>
                        </div>
                        <div class="flex flex-col items-end gap-1">
                            <span class="inline-flex items-center gap-1 bg-violet-100/80 text-violet-700 text-[10px] font-bold px-2 py-0.5 rounded-md">
                                <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                                Lifetime
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ② Tickets Today -->
            <div class="relative overflow-hidden rounded-2xl p-5 shadow-sm border border-slate-200/70 bg-white group hover:shadow-lg hover:border-indigo-200/70 transition-all duration-300">
                <div class="absolute -right-4 -bottom-4 h-20 w-20 rounded-full bg-gradient-to-br from-indigo-100/50 to-blue-50/30 blur-lg opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                <div class="relative">
                    <div class="flex items-center gap-2.5 mb-3">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 ring-1 ring-indigo-100 group-hover:bg-indigo-100 transition-colors">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
                        </span>
                        <h3 class="text-[10px] font-bold text-slate-500/80 uppercase tracking-[0.15em]">Tickets Today</h3>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <span class="text-3xl font-extrabold text-slate-900 tracking-tight">{{ $totalTicketsToday }}</span>
                        @if($ticketsTrend !== 0)
                            <span class="inline-flex items-center gap-0.5 text-[10px] font-bold {{ $ticketsTrend > 0 ? 'text-rose-600 bg-rose-50 ring-1 ring-rose-100' : 'text-emerald-600 bg-emerald-50 ring-1 ring-emerald-100' }} px-1.5 py-0.5 rounded-md">
                                {!! $ticketsTrend > 0 ? '↑' : '↓' !!} {{ abs($ticketsTrend) }}%
                            </span>
                        @endif
                    </div>
                    <p class="text-[10px] text-slate-400 mt-1 font-medium">vs yesterday ({{ $totalTicketsYesterday }})</p>
                </div>
            </div>

            <!-- ③ Active Tickets -->
            <div class="relative overflow-hidden rounded-2xl p-5 shadow-sm border border-slate-200/70 bg-white group hover:shadow-lg hover:border-amber-200/70 transition-all duration-300">
                <div class="absolute -right-4 -bottom-4 h-20 w-20 rounded-full bg-gradient-to-br from-amber-100/50 to-orange-50/30 blur-lg opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                <div class="relative">
                    <div class="flex items-center gap-2.5 mb-3">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-50 text-amber-600 ring-1 ring-amber-100 group-hover:bg-amber-100 transition-colors">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        </span>
                        <h3 class="text-[10px] font-bold text-slate-500/80 uppercase tracking-[0.15em]">Active</h3>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <span class="text-3xl font-extrabold text-slate-900 tracking-tight">{{ $openTickets + $inProgressTickets }}</span>
                        <span class="text-[10px] font-semibold text-amber-600/80 bg-amber-50 px-1.5 py-0.5 rounded ring-1 ring-amber-100/80">In queue</span>
                    </div>
                    <div class="flex items-center gap-3 mt-2">
                        <span class="text-[10px] text-slate-400"><span class="inline-block h-1.5 w-1.5 rounded-full bg-amber-400 mr-1"></span>Open {{ $openTickets }}</span>
                        <span class="text-[10px] text-slate-400"><span class="inline-block h-1.5 w-1.5 rounded-full bg-blue-400 mr-1"></span>Progress {{ $inProgressTickets }}</span>
                    </div>
                </div>
            </div>

            <!-- ④ Resolved Today -->
            <div class="relative overflow-hidden rounded-2xl p-5 shadow-sm border border-slate-200/70 bg-white group hover:shadow-lg hover:border-emerald-200/70 transition-all duration-300">
                <div class="absolute -right-4 -bottom-4 h-20 w-20 rounded-full bg-gradient-to-br from-emerald-100/50 to-green-50/30 blur-lg opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                <div class="relative">
                    <div class="flex items-center gap-2.5 mb-3">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 ring-1 ring-emerald-100 group-hover:bg-emerald-100 transition-colors">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                        </span>
                        <h3 class="text-[10px] font-bold text-slate-500/80 uppercase tracking-[0.15em]">Resolved Today</h3>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <span class="text-3xl font-extrabold text-slate-900 tracking-tight">{{ $resolvedToday }}</span>
                        @if($resolvedTrend !== 0)
                            <span class="inline-flex items-center gap-0.5 text-[10px] font-bold {{ $resolvedTrend > 0 ? 'text-emerald-600 bg-emerald-50 ring-1 ring-emerald-100' : 'text-rose-600 bg-rose-50 ring-1 ring-rose-100' }} px-1.5 py-0.5 rounded-md">
                                {!! $resolvedTrend > 0 ? '↑' : '↓' !!} {{ abs($resolvedTrend) }}%
                            </span>
                        @endif
                    </div>
                    <p class="text-[10px] text-slate-400 mt-1 font-medium">vs yesterday ({{ $resolvedYesterday }})</p>
                </div>
            </div>

            <!-- ⑤ SLA Breaches -->
            <div class="relative overflow-hidden rounded-2xl p-5 shadow-sm border {{ $slaBreachCount > 0 ? 'border-rose-200/80 bg-gradient-to-br from-rose-50/60 via-white to-red-50/30' : 'border-slate-200/70 bg-white' }} group hover:shadow-lg transition-all duration-300 {{ $slaBreachCount > 0 ? 'hover:border-rose-300' : 'hover:border-slate-300' }}">
                @if($slaBreachCount > 0)
                <div class="absolute -right-3 -top-3 h-16 w-16 rounded-full bg-rose-200/40 blur-xl animate-pulse"></div>
                @endif
                <div class="relative">
                    <div class="flex items-center gap-2.5 mb-3">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl {{ $slaBreachCount > 0 ? 'bg-rose-100 text-rose-600 ring-1 ring-rose-200' : 'bg-slate-50 text-slate-400 ring-1 ring-slate-100' }} transition-colors">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 9v4"/><path d="M12 17h.01"/><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg>
                        </span>
                        <h3 class="text-[10px] font-bold {{ $slaBreachCount > 0 ? 'text-rose-600/80' : 'text-slate-500/80' }} uppercase tracking-[0.15em]">SLA Breaches</h3>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <span class="text-3xl font-extrabold {{ $slaBreachCount > 0 ? 'text-rose-600' : 'text-slate-900' }} tracking-tight">{{ $slaBreachCount }}</span>
                        @if($slaBreachCount > 0)
                            <span class="text-[10px] font-bold text-rose-600 bg-rose-100/60 px-1.5 py-0.5 rounded ring-1 ring-rose-200/60 animate-pulse">Action req.</span>
                        @else
                            <span class="text-[10px] font-semibold text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded ring-1 ring-emerald-100">All clear</span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- ⑥ MTTR (Avg Resolution Time) -->
            <div class="relative overflow-hidden rounded-2xl p-5 shadow-sm border border-slate-200/70 bg-white group hover:shadow-lg hover:border-blue-200/70 transition-all duration-300 hidden lg:block">
                <div class="absolute -right-4 -bottom-4 h-20 w-20 rounded-full bg-gradient-to-br from-blue-100/50 to-cyan-50/30 blur-lg opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                <div class="relative">
                    <div class="flex items-center gap-2.5 mb-3">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-blue-600 ring-1 ring-blue-100 group-hover:bg-blue-100 transition-colors">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        </span>
                        <h3 class="text-[10px] font-bold text-slate-500/80 uppercase tracking-[0.15em]">MTTR (30d)</h3>
                    </div>
                    <div class="flex items-baseline gap-1.5">
                        <span class="text-3xl font-extrabold text-slate-900 tracking-tight">{{ $globalAvgResTime }}</span>
                        <span class="text-xs font-bold text-slate-400 pb-0.5">hrs</span>
                    </div>
                    <p class="text-[10px] text-slate-400 mt-1 font-medium">Mean time to resolve</p>
                </div>
            </div>

        </div>

        <!-- Quick Admin Actions -->
        <div class="rounded-2xl border border-slate-200/60 bg-white p-5 shadow-sm">
            <div class="flex items-center gap-2 mb-4">
                <svg class="h-4 w-4 text-indigo-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                <h3 class="text-sm font-bold text-slate-800">Quick Actions</h3>
            </div>
            <div class="flex flex-wrap gap-2.5">

                <a href="{{ route('tickets.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm hover:bg-amber-50 hover:border-amber-200 hover:text-amber-700 transition-all hover:-translate-y-0.5">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
                    Assign Ticket
                </a>
                <a href="{{ route('users.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm hover:bg-emerald-50 hover:border-emerald-200 hover:text-emerald-700 transition-all hover:-translate-y-0.5">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    Add User
                </a>
                <a href="{{ route('assets.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm hover:bg-blue-50 hover:border-blue-200 hover:text-blue-700 transition-all hover:-translate-y-0.5">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                    Manage Assets
                </a>
                <button type="button" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm hover:bg-violet-50 hover:border-violet-200 hover:text-violet-700 transition-all hover:-translate-y-0.5">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    Export Report
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-4 gap-6 lg:gap-8 items-start">
            
            <!-- Main Column (Left - 3/4 Width) -->
            <div class="xl:col-span-3 flex flex-col gap-6 lg:gap-8 min-w-0">
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 lg:gap-8">
                    <!-- Ticket Trend Chart (Created vs Resolved) -->
                    <div class="rounded-2xl border border-slate-200/60 bg-white p-5 sm:p-6 shadow-sm flex flex-col surface-card">
                        <div class="flex items-center justify-between mb-5">
                            <div>
                                <h3 class="font-bold text-slate-800 text-base">Ticket Volume Trend</h3>
                                <p class="text-xs text-slate-500 mt-0.5">Created vs Resolved (Last 30 Days)</p>
                            </div>
                        </div>
                        <div class="flex-1 w-full min-h-[260px] relative">
                            @php
                                $hasChartData = collect($trendData['created'] ?? [])->sum() > 0 || collect($trendData['resolved'] ?? [])->sum() > 0;
                            @endphp
                            @if($hasChartData)
                                <canvas id="trendChart"></canvas>
                            @else
                                <div class="flex flex-col items-center justify-center h-full text-center px-6">
                                    <div class="h-14 w-14 rounded-full bg-slate-100 flex items-center justify-center mb-4">
                                        <svg class="h-7 w-7 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/></svg>
                                    </div>
                                    <p class="text-sm font-semibold text-slate-700 mb-1">No data available yet</p>
                                    <p class="text-xs text-slate-500">Create tickets to generate analytics</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Analytics Sliders (Hotspots) -->
                    <div x-data="{ slide: 'dept' }" class="rounded-2xl border border-slate-200/60 bg-white p-5 sm:p-6 shadow-sm flex flex-col surface-card relative overflow-hidden">
                        
                        <!-- Toggle Controls -->
                        <div class="flex items-center justify-between mb-5">
                            <div>
                                <h3 class="font-bold text-slate-800 text-base flex items-center gap-2">
                                    <span x-text="slide === 'dept' ? 'Department Hotspots' : 'Category Hotspots'"></span>
                                </h3>
                                <p class="text-xs text-slate-500 mt-0.5" x-text="slide === 'dept' ? 'Ticket generation by department (30 Days)' : 'Ticket generation by category (30 Days)'"></p>
                            </div>
                            <!-- Simple pills to toggle -->
                            <div class="flex bg-slate-100/80 p-1 rounded-lg">
                                <button @click="slide = 'dept'" type="button" :class="slide === 'dept' ? 'bg-white shadow-sm text-slate-800' : 'text-slate-500 hover:text-slate-700'" class="px-3 py-1 text-xs font-semibold rounded-md transition-all">Depts</button>
                                <button @click="slide = 'cat'" type="button" :class="slide === 'cat' ? 'bg-white shadow-sm text-slate-800' : 'text-slate-500 hover:text-slate-700'" class="px-3 py-1 text-xs font-semibold rounded-md transition-all">Categories</button>
                            </div>
                        </div>

                        <div class="flex-1 w-full relative h-[260px]">
                            
                            <!-- Slide 1: Departments -->
                            <div x-show="slide === 'dept'" 
                                 x-transition:enter="transition ease-out duration-300 transform"
                                 x-transition:enter-start="opacity-0 translate-x-4"
                                 x-transition:enter-end="opacity-100 translate-x-0"
                                 x-transition:leave="transition ease-in duration-200 transform absolute inset-0 z-10"
                                 x-transition:leave-start="opacity-100 translate-x-0"
                                 x-transition:leave-end="opacity-0 -translate-x-4"
                                 class="w-full h-full flex flex-col gap-4 overflow-y-auto pr-2 custom-scrollbar absolute top-0 left-0 bg-white">
                                @forelse($departmentHeatmap as $dept)
                                    @php
                                        $maxTotal = max($departmentHeatmap->max('total_count'), 1);
                                        $percentage = ($dept->total_count / $maxTotal) * 100;
                                    @endphp
                                    <div>
                                        <div class="flex justify-between items-end mb-1">
                                            <span class="text-sm font-medium text-slate-700">{{ $dept->department_name }}</span>
                                            <div class="text-xs">
                                                <span class="font-bold text-slate-900">{{ $dept->total_count }}</span>
                                                <span class="text-slate-400 font-medium"> tickets</span>
                                            </div>
                                        </div>
                                        <div class="w-full bg-slate-100 rounded-full h-2.5 flex overflow-hidden">
                                            @if($dept->open_count > 0)
                                                <div class="bg-amber-400 h-2.5" style="width: {{ ($dept->open_count / $dept->total_count) * $percentage }}%"></div>
                                            @endif
                                            @if($dept->resolved_count > 0)
                                                <div class="bg-indigo-500 h-2.5" style="width: {{ ($dept->resolved_count / $dept->total_count) * $percentage }}%"></div>
                                            @endif
                                        </div>
                                        <div class="flex gap-3 text-[10px] mt-1.5">
                                            <span class="flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full bg-amber-400 inline-block"></span> {{ $dept->open_count }} Open</span>
                                            <span class="flex items-center gap-1 text-slate-500"><span class="w-1.5 h-1.5 rounded-full bg-indigo-500 inline-block"></span> {{ $dept->resolved_count }} Resolved</span>
                                        </div>
                                    </div>
                                @empty
                                    <div class="flex flex-col items-center justify-center h-full text-center px-6 py-8">
                                        <div class="h-14 w-14 rounded-full bg-slate-100 flex items-center justify-center mb-4">
                                            <svg class="h-7 w-7 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                                        </div>
                                        <p class="text-sm font-semibold text-slate-700 mb-1">No data available yet</p>
                                        <p class="text-xs text-slate-500">Create tickets to generate analytics</p>
                                    </div>
                                @endforelse
                            </div>

                            <!-- Slide 2: Categories -->
                            <div x-show="slide === 'cat'" style="display: none;"
                                 x-transition:enter="transition ease-out duration-300 transform max-h-full"
                                 x-transition:enter-start="opacity-0 translate-x-4"
                                 x-transition:enter-end="opacity-100 translate-x-0"
                                 x-transition:leave="transition ease-in duration-200 transform absolute inset-0 z-10"
                                 x-transition:leave-start="opacity-100 translate-x-0"
                                 x-transition:leave-end="opacity-0 -translate-x-4"
                                 class="w-full h-full flex flex-col gap-4 overflow-y-auto pr-2 custom-scrollbar absolute top-0 left-0 bg-white">
                                @forelse($categoryHeatmap as $cat)
                                    @php
                                        $maxTotalCat = max($categoryHeatmap->max('total_count'), 1);
                                        $percentageCat = ($cat->total_count / $maxTotalCat) * 100;
                                    @endphp
                                    <div>
                                        <div class="flex justify-between items-end mb-1">
                                            <span class="text-sm font-medium text-slate-700">{{ $cat->category_name }}</span>
                                            <div class="text-xs">
                                                <span class="font-bold text-slate-900">{{ $cat->total_count }}</span>
                                                <span class="text-slate-400 font-medium"> tickets</span>
                                            </div>
                                        </div>
                                        <div class="w-full bg-slate-100 rounded-full h-2.5 flex overflow-hidden">
                                            @if($cat->open_count > 0)
                                                <div class="bg-rose-400 h-2.5" style="width: {{ ($cat->open_count / $cat->total_count) * $percentageCat }}%"></div>
                                            @endif
                                            @if($cat->resolved_count > 0)
                                                <div class="bg-indigo-500 h-2.5" style="width: {{ ($cat->resolved_count / $cat->total_count) * $percentageCat }}%"></div>
                                            @endif
                                        </div>
                                        <div class="flex gap-3 text-[10px] mt-1.5">
                                            <span class="flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full bg-rose-400 inline-block"></span> {{ $cat->open_count }} Open</span>
                                            <span class="flex items-center gap-1 text-slate-500"><span class="w-1.5 h-1.5 rounded-full bg-indigo-500 inline-block"></span> {{ $cat->resolved_count }} Resolved</span>
                                        </div>
                                    </div>
                                @empty
                                    <div class="flex flex-col items-center justify-center h-full text-center px-6 py-8">
                                        <div class="h-14 w-14 rounded-full bg-slate-100 flex items-center justify-center mb-4">
                                            <svg class="h-7 w-7 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                                        </div>
                                        <p class="text-sm font-semibold text-slate-700 mb-1">No data available yet</p>
                                        <p class="text-xs text-slate-500">Create tickets to generate analytics</p>
                                    </div>
                                @endforelse
                            </div>

                        </div>
                    </div>
                </div>

                <!-- Technician Performance Ranking -->
                <div class="rounded-2xl border border-slate-200/60 bg-white shadow-sm overflow-hidden flex flex-col surface-card">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between border-b border-slate-100 px-6 py-5 bg-slate-50/30">
                        <div>
                            <h3 class="font-bold text-slate-800 text-base flex items-center gap-2">Technician Performance Matrix</h3>
                            <p class="text-xs text-slate-500 mt-0.5">Workload distribution and resolution efficiency</p>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm whitespace-nowrap">
                            <thead class="bg-slate-50/50 text-[11px] uppercase tracking-wider text-slate-500 border-b border-slate-100">
                                <tr>
                                    <th class="px-6 py-4 font-semibold w-16 text-center">Rank</th>
                                    <th class="px-6 py-4 font-semibold">Technician</th>
                                    <th class="px-6 py-4 font-semibold w-64">Resolution Progress</th>
                                    <th class="px-6 py-4 font-semibold text-center">Open</th>
                                    <th class="px-6 py-4 font-semibold text-right">Avg Res Time</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($technicians as $index => $tech)
                                    @php
                                        $totalAssigned = $tech['open'] + $tech['resolved'];
                                        $resolveRate = $totalAssigned > 0 ? ($tech['resolved'] / $totalAssigned) * 100 : 0;
                                    @endphp
                                    <tr class="hover:bg-slate-50/50 transition-colors group">
                                        <td class="px-6 py-4 text-center">
                                            @if($index === 0 && $tech['resolved'] > 0)
                                                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-amber-100 text-amber-600 font-bold text-xs ring-2 ring-amber-50">1</span>
                                            @elseif($index === 1 && $tech['resolved'] > 0)
                                                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-slate-200 text-slate-600 font-bold text-xs ring-2 ring-slate-100">2</span>
                                            @elseif($index === 2 && $tech['resolved'] > 0)
                                                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-orange-100 text-orange-700 font-bold text-xs ring-2 ring-orange-50">3</span>
                                            @else
                                                <span class="text-slate-400 font-semibold">{{ $index + 1 }}</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="h-9 w-9 rounded-full bg-gradient-to-br from-indigo-100 to-indigo-50 text-indigo-700 font-bold flex items-center justify-center text-sm shrink-0 border border-indigo-200 shadow-sm">
                                                    {{ substr($tech['name'], 0, 1) }}
                                                </div>
                                                <div>
                                                    <p class="font-semibold text-slate-900 group-hover:text-indigo-600 transition-colors">{{ $tech['name'] }}</p>
                                                    <p class="text-[11px] text-slate-500 mt-0.5">{{ $tech['email'] }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="flex-1 h-2 bg-slate-100 rounded-full overflow-hidden">
                                                    <div class="h-full bg-emerald-500 rounded-full transition-all duration-1000 ease-out" style="width: {{ $resolveRate }}%"></div>
                                                </div>
                                                <div class="w-12 text-right">
                                                    <span class="text-xs font-bold text-slate-700">{{ $tech['resolved'] }}</span>
                                                    <span class="text-[9px] text-slate-400 block -mt-1">resolved</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <span class="inline-flex items-center justify-center min-w-[2.5rem] rounded-md {{ $tech['open'] > 5 ? 'bg-amber-100 text-amber-700 ring-1 ring-amber-200' : 'bg-slate-100 text-slate-700 ring-1 ring-slate-200' }} px-2 py-1 text-xs font-bold">
                                                {{ $tech['open'] }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <div class="flex flex-col items-end">
                                                <span class="text-slate-800 font-bold">{{ $tech['avg_res_time'] }} <span class="text-[10px] font-medium text-slate-500 uppercase">hrs</span></span>
                                                @if($tech['avg_res_time'] > $globalAvgResTime && $globalAvgResTime > 0)
                                                    <span class="text-[10px] text-rose-500 font-medium">Below Avg</span>
                                                @elseif($tech['avg_res_time'] > 0)
                                                    <span class="text-[10px] text-emerald-500 font-medium">Above Avg</span>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-8 text-center text-sm text-slate-500">No technicians found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- NOC Live Queue -->
                <div class="w-full" data-live-slot="dashboard-live-queue">
                    @include('dashboard.partials.live-queue', [
                        'liveMonitoringQueue' => $liveMonitoringQueue,
                    ])
                </div>

            </div>
            
            <!-- Side Column (Right - 1/4 Width) -->
            <div class="xl:col-span-1 flex flex-col gap-6 lg:gap-8 min-w-0">
                
                <!-- Asset / Infrastructure Overview -->
                <div class="rounded-2xl border border-slate-200/80 bg-gradient-to-br from-slate-900 to-slate-800 text-white p-6 shadow-lg relative overflow-hidden">
                    <div class="absolute right-0 top-0 opacity-10 pointer-events-none">
                        <svg width="150" height="150" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                    </div>
                    <h3 class="font-bold text-white text-base flex items-center gap-2 mb-1 relative z-10">
                        <svg class="h-4 w-4 text-indigo-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                        Infrastructure Health
                    </h3>
                    <p class="text-xs text-slate-400 mb-5 relative z-10">Asset inventory snapshot</p>
                    <div class="grid grid-cols-2 gap-3 relative z-10">
                        <div class="bg-white/10 rounded-xl p-3.5 backdrop-blur-sm border border-white/5">
                            <span class="text-2xl font-bold text-white block">{{ $assetOverview['total'] }}</span>
                            <span class="text-[10px] text-slate-300 uppercase tracking-wider font-semibold">Total Assets</span>
                        </div>
                        <div class="bg-emerald-500/20 rounded-xl p-3.5 backdrop-blur-sm border border-emerald-500/20">
                            <span class="text-2xl font-bold text-emerald-400 block">{{ $assetOverview['active'] }}</span>
                            <span class="text-[10px] text-emerald-200 uppercase tracking-wider font-semibold">In Use</span>
                        </div>
                        <div class="bg-amber-500/20 rounded-xl p-3.5 backdrop-blur-sm border border-amber-500/20">
                            <span class="text-2xl font-bold text-amber-400 block">{{ $assetOverview['maintenance'] }}</span>
                            <span class="text-[10px] text-amber-200 uppercase tracking-wider font-semibold">Maintenance</span>
                        </div>
                        <div class="bg-rose-500/20 rounded-xl p-3.5 backdrop-blur-sm border border-rose-500/20">
                            <span class="text-2xl font-bold text-rose-400 block">{{ $assetOverview['broken'] }}</span>
                            <span class="text-[10px] text-rose-200 uppercase tracking-wider font-semibold">Broken</span>
                        </div>
                    </div>
                </div>

                <!-- SLA Monitoring -->
                <div class="rounded-2xl border border-rose-200 bg-white shadow-sm flex flex-col overflow-hidden ring-1 ring-rose-100">
                    <div class="flex items-center justify-between px-5 py-4 border-b border-rose-100 bg-rose-50/50">
                        <div>
                            <h3 class="font-bold text-rose-800 text-base flex items-center gap-2">
                                <span class="relative flex h-2 w-2">
                                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75"></span>
                                  <span class="relative inline-flex rounded-full h-2 w-2 bg-rose-600"></span>
                                </span>
                                SLA Priority Log
                            </h3>
                            <p class="text-[11px] font-medium text-rose-600/80 mt-0.5">Tickets requiring attention</p>
                        </div>
                        <span class="text-xs font-bold bg-white ring-1 ring-rose-200 text-rose-700 px-2.5 py-1 rounded-full shadow-sm">{{ $slaBreachCount }}</span>
                    </div>
                    <div class="p-4 flex-1 overflow-y-auto space-y-2.5 custom-scrollbar max-h-[380px]">
                        @if($slaBreachTickets->isEmpty())
                            <div class="py-8 text-center flex flex-col items-center gap-2">
                                <div class="h-12 w-12 rounded-full bg-emerald-50 flex items-center justify-center text-emerald-500 mb-2 border border-emerald-100">
                                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                                </div>
                                <p class="text-sm font-semibold text-slate-800">100% SLA Compliance</p>
                                <p class="text-[11px] text-slate-500">All tickets are operating within SLA margins.</p>
                            </div>
                        @else
                            @foreach($slaBreachTickets as $ticket)
                                <a href="{{ route('tickets.show', $ticket) }}" class="block p-3 rounded-xl border {{ $ticket->priority === 'high' ? 'border-rose-200 bg-rose-50/50 hover:bg-rose-50' : 'border-orange-200 bg-orange-50/50 hover:bg-orange-50' }} transition-colors group relative overflow-hidden">
                                    <div class="absolute left-0 top-0 bottom-0 w-1 {{ $ticket->priority === 'high' ? 'bg-rose-500' : 'bg-orange-500' }}"></div>
                                    <div class="pl-2">
                                        <span class="text-[10px] font-bold uppercase {{ $ticket->priority === 'high' ? 'text-rose-700' : 'text-orange-700' }} tracking-wide">#{{ $ticket->id }} &bull; {{ ucfirst($ticket->priority) }}</span>
                                        <p class="text-sm font-bold text-slate-800 line-clamp-1 group-hover:text-indigo-600 mt-0.5">{{ $ticket->title }}</p>
                                        <div class="flex justify-between items-center text-[11px] text-slate-600 mt-2">
                                            <span class="font-medium text-rose-600 flex items-center gap-1">
                                                <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                                {{ $ticket->created_at->diffForHumans() }}
                                            </span>
                                            @if($ticket->assignedAdmin)
                                                <span class="font-semibold text-slate-700 bg-slate-100/80 px-1.5 py-0.5 rounded text-[10px]">{{ explode(' ', trim($ticket->assignedAdmin->name))[0] }}</span>
                                            @else
                                                <span class="text-rose-600 font-bold bg-rose-100/50 px-1.5 py-0.5 rounded text-[10px]">Unassigned</span>
                                            @endif
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        @endif
                    </div>
                </div>

                <!-- Live Activity Feed -->
                <div class="rounded-2xl border border-slate-200/60 bg-white shadow-sm flex flex-col overflow-hidden surface-card">
                    <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/50">
                        <h3 class="font-bold text-slate-800 text-base flex items-center gap-2">
                            <svg class="h-4 w-4 text-emerald-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                            Live Activity Stream
                        </h3>
                    </div>
                    <div class="p-5 flex-1 overflow-y-auto custom-scrollbar max-h-[400px]">
                        <div class="space-y-4">
                            @forelse($recentActivity as $log)
                                <div class="flex items-start gap-3">
                                    <div class="flex items-center justify-center w-7 h-7 rounded-full shrink-0 mt-0.5
                                        {{ $log->action === 'created' ? 'bg-indigo-100 text-indigo-600' : '' }}
                                        {{ $log->action === 'status_updated' && in_array($log->new_value ?? '', ['resolved', 'closed']) ? 'bg-emerald-100 text-emerald-600' : '' }}
                                        {{ !in_array($log->action, ['created']) && !($log->action === 'status_updated' && in_array($log->new_value ?? '', ['resolved', 'closed'])) ? 'bg-slate-100 text-slate-500' : '' }}
                                    ">
                                        @if($log->action === 'created')
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                                        @elseif($log->action === 'status_updated' && in_array($log->new_value ?? '', ['resolved', 'closed']))
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                        @else
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M17 8l-5-5-5 5M12 3v12"/></svg>
                                        @endif
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="text-xs text-slate-800">
                                            <span class="font-bold">{{ $log->actor_name ?? ($log->user ? $log->user->name : 'System') }}</span>
                                            <span class="text-slate-500 mx-1">{{ $log->action === 'created' ? 'created' : 'updated' }}</span>
                                            <a href="{{ route('tickets.show', $log->ticket_id) }}" class="text-indigo-600 hover:text-indigo-800 font-semibold">#{{ $log->ticket_id }}</a>
                                        </div>
                                        <div class="text-[10px] text-slate-400 font-medium mt-0.5">{{ $log->created_at->diffForHumans() }}</div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center text-xs text-slate-500 py-4">No recent activity.</div>
                            @endforelse
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Shared Chart Settings for Enterprise Look
            if (typeof Chart !== 'undefined') {
                Chart.defaults.font.family = "'Inter', sans-serif";
                Chart.defaults.color = '#94a3b8';

                // Trend Chart (Bar Chart: Created vs Resolved)
                const trendCtx = document.getElementById('trendChart');
                if (trendCtx) {
                    const trendDataRaw = @json($trendData ?? ['labels' => [], 'created' => [], 'resolved' => []]);
                    
                    new Chart(trendCtx, {
                        type: 'bar',
                        data: {
                            labels: trendDataRaw.labels,
                            datasets: [
                                {
                                    label: 'Created',
                                    data: trendDataRaw.created,
                                    backgroundColor: '#6366f1',
                                    borderRadius: 4,
                                    barPercentage: 0.6,
                                    categoryPercentage: 0.8
                                },
                                {
                                    label: 'Resolved',
                                    data: trendDataRaw.resolved,
                                    backgroundColor: '#10b981',
                                    borderRadius: 4,
                                    barPercentage: 0.6,
                                    categoryPercentage: 0.8
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { 
                                    position: 'top',
                                    align: 'end',
                                    labels: {
                                        usePointStyle: true,
                                        boxWidth: 6,
                                        font: { size: 11, weight: '600' }
                                    }
                                },
                                tooltip: {
                                    backgroundColor: '#0f172a',
                                    padding: 12,
                                    titleFont: { size: 13 },
                                    bodyFont: { size: 14, weight: 'bold' },
                                    usePointStyle: true,
                                    callbacks: {
                                        label: function(context) { return ' ' + context.dataset.label + ': ' + context.parsed.y; }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    border: { display: false },
                                    grid: { color: '#f1f5f9', drawBorder: false },
                                    ticks: { precision: 0, padding: 10, font: {size: 11}}
                                },
                                x: {
                                    border: { display: false },
                                    grid: { display: false },
                                    ticks: { maxRotation: 45, maxTicksLimit: 15, font: {size: 10} }
                                }
                            },
                            interaction: {
                                intersect: false,
                                mode: 'index',
                            },
                        }
                    });
                }
            }
        });
    </script>
    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 5px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 5px; }
        .custom-scrollbar:hover::-webkit-scrollbar-thumb { background: #94a3b8; }
    </style>
</x-app-layout>
