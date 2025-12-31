@php
    use Illuminate\Support\Str;

    $searchTerm = isset($searchTerm) ? (string) $searchTerm : '';
    $startDate = isset($startDate) ? (string) $startDate : (string) request()->query('start_date', '');
    $endDate = isset($endDate) ? (string) $endDate : (string) request()->query('end_date', '');
    $filterParams = static fn (array $params): array => array_filter(
        $params,
        static fn ($value) => $value !== null && $value !== ''
    );
@endphp

<x-ui.panel class="shadow-md border-ink-100/80 bg-gradient-to-br from-[#F6F9F8] via-white to-[#EDF3F2] w-full max-w-none mx-0 px-0">
    <div class="space-y-6">
        <div class="flex flex-col gap-3 border-b border-slate-100 pb-3 md:flex-row md:items-center md:justify-between">
            <div class="space-y-2">
                <p class="heading-font text-[11px] font-semibold uppercase tracking-[0.42em] text-[#23455D]/70 flex items-center gap-2">
                    <svg class="h-4 w-4 text-[#12824C]/70" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20v-6" /><path d="M6 20v-4" /><path d="M18 20v-8" /><path d="M3 13h18" /></svg>
                    Daftar Tiket
                </p>
                <p class="text-base font-semibold text-[#0C1F2C] leading-tight">Daftar tiket yang perlu ditangani tim IT hari ini.</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <div
                    class="badge-live inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white/80 px-3 py-1.5 text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-500 shadow-inner shadow-white/60"
                    title="Auto refresh setiap 10 detik"
                >
                    Auto refresh · 10s
                    <span class="live-dot h-[6px] w-[6px] rounded-full bg-emerald-400"></span>
                </div>
                @if (Route::has('tickets.export'))
                    <a
                        href="{{ route('tickets.export', request()->query()) }}"
                        class="inline-flex items-center gap-2 rounded-2xl border border-[#C5E5D0] bg-white px-4 py-2 text-sm font-semibold text-[#0C1F2C] shadow-sm transition hover:-translate-y-0.5 hover:shadow-[0_10px_22px_rgba(18,130,76,0.12)]"
                    >
                        <svg class="h-4.5 w-4.5 text-[#12824C]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                            <polyline points="7 10 12 15 17 10" />
                            <line x1="12" y1="15" x2="12" y2="3" />
                        </svg>
                        Export Excel
                    </a>
                @endif
            </div>
        </div>

        @php
            $baseFilters = [
                'department' => $departmentFilter,
                'search' => $searchTerm,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ];
            $primaryStatuses = collect($statuses);
            $advancedActive = collect($baseFilters)->filter(fn ($value) => filled($value))->isNotEmpty();
            $statusLabel = $statusFilter && isset($statuses[$statusFilter])
                ? $statuses[$statusFilter]
                : ($statusFilter ? str_replace('_', ' ', $statusFilter) : 'Semua status');
        @endphp

        <div class="rounded-2xl border border-slate-200/80 bg-white/60 px-4 py-3">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-[11px] font-semibold uppercase tracking-[0.32em] text-slate-400">Filter Status</p>
                <span class="text-[11px] text-slate-400">Table filter</span>
            </div>
            <div class="mt-2 flex flex-wrap items-center gap-2 text-[11px] font-semibold uppercase tracking-[0.24em]">
                <a
                    href="{{ route('tickets.index', $filterParams($baseFilters)) }}"
                    data-live-link
                    class="rounded-full border px-4 py-2 shadow-sm transition duration-200 hover:-translate-y-0.5 {{ empty($statusFilter) ? 'bg-emerald-600 text-white border-transparent shadow-emerald-500/30 hover:bg-emerald-600 hover:text-white' : 'border-slate-200 bg-white text-slate-600 hover:border-emerald-300 hover:bg-emerald-100/70 hover:text-emerald-900 hover:ring-1 hover:ring-emerald-200' }}"
                >
                    Semua
                </a>
                @foreach ($primaryStatuses as $key => $label)
                    <a
                        href="{{ route('tickets.index', $filterParams(array_merge($baseFilters, ['status' => $key]))) }}"
                        data-live-link
                        class="rounded-full border px-4 py-2 capitalize shadow-sm transition duration-200 hover:-translate-y-0.5 {{ ($statusFilter === $key) ? 'bg-emerald-600 text-white border-transparent shadow-emerald-500/30 hover:bg-emerald-600 hover:text-white' : 'border-slate-200 bg-white text-slate-600 hover:border-emerald-300 hover:bg-emerald-100/70 hover:text-emerald-900 hover:ring-1 hover:ring-emerald-200' }}"
                    >
                        {{ str_replace('_', ' ', $key) }}
                        <span class="ms-2 text-[0.68rem] font-bold {{ ($statusFilter === $key) ? 'text-emerald-50' : 'text-ink-400' }}">{{ $statusCounts[$key] ?? 0 }}</span>
                    </a>
                @endforeach
            </div>
            <p class="mt-2 text-[11px] text-slate-400">
                Menampilkan: {{ $statusLabel }}{{ $advancedActive ? ' · Filter lanjutan aktif' : '' }}
            </p>
        </div>

        <div class="space-y-5">
            <div
                class="rounded-2xl border border-dashed border-slate-200/80 bg-white/60 p-4"
                x-data="{
                    open: {{ $advancedActive ? 'true' : 'false' }},
                    init() {
                        const saved = sessionStorage.getItem('ticket-advanced-open');
                        if (saved !== null) this.open = saved === '1';
                        this.$watch('open', (value) => {
                            sessionStorage.setItem('ticket-advanced-open', value ? '1' : '0');
                        });
                    }
                }"
            >
                    <button
                        type="button"
                        class="flex w-full items-center justify-between gap-3 text-left"
                        @click="open = !open"
                        :aria-expanded="open"
                    >
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-[0.3em] text-slate-500">Advanced Filters</p>
                            <p class="text-[11px] text-slate-400">Optional refinement</p>
                        </div>
                        <svg class="h-4 w-4 text-slate-400 transition" :class="{ 'rotate-180': open }" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.25 8.29a.75.75 0 01-.02-1.06z" clip-rule="evenodd" />
                        </svg>
                    </button>
                <form
                    method="GET"
                    action="{{ route('tickets.index') }}"
                    class="mt-3 flex flex-col gap-3 lg:flex-col"
                    data-live-form
                    x-show="open"
                    x-transition
                    x-cloak
                    x-data="{
                    endpoint: '{{ route('tickets.index') }}',
                    searchTerm: @js($searchTerm),
                    status: @js($statusFilter),
                    department: @js($departmentFilter),
                    startDate: @js($startDate),
                    endDate: @js($endDate),
                    suggestions: [],
                    suggestionsOpen: false,
                    suggestionsLoading: false,
                    timer: null,
                    searchTimer: null,
                    hasResults: {{ $tickets->count() > 0 ? 'true' : 'false' }},
                    init() {
                        this.$watch('searchTerm', () => {
                            this.queueFetch();
                            this.queueSearch();
                        });
                    },
                    queueFetch() {
                        clearTimeout(this.timer);
                        this.suggestionsOpen = true;
                        this.timer = setTimeout(() => this.fetchSuggestions(), 250);
                    },
                    queueSearch() {
                        clearTimeout(this.searchTimer);
                        this.searchTimer = setTimeout(() => this.submitSearch(), 300);
                    },
                    clearSearch() {
                        this.searchTerm = '';
                        this.suggestions = [];
                        this.suggestionsOpen = false;
                        this.$nextTick(() => this.submitSearch());
                    },
                    async fetchSuggestions() {
                        const term = (this.searchTerm || '').trim();
                        if (!term) {
                            this.suggestions = [];
                            this.suggestionsLoading = false;
                            return;
                        }
                        this.suggestionsLoading = true;
                        try {
                            const url = new URL(this.endpoint);
                            url.searchParams.set('autocomplete', '1');
                            url.searchParams.set('search', term);
                            if (this.status) url.searchParams.set('status', this.status);
                            if (this.department) url.searchParams.set('department', this.department);
                            if (this.startDate) url.searchParams.set('start_date', this.startDate);
                            if (this.endDate) url.searchParams.set('end_date', this.endDate);
                            const res = await fetch(url.toString(), { headers: { 'Accept': 'application/json' } });
                            const data = await res.json();
                            this.suggestions = data.suggestions || [];
                        } catch (e) {
                            this.suggestions = [];
                        } finally {
                            this.suggestionsLoading = false;
                        }
                    },
                    async submitSearch() {
                        const form = this.$root;
                        const target = new URL(form.action, window.location.origin);
                        const formData = new FormData(form);
                        target.searchParams.delete('page');
                        formData.forEach((value, key) => {
                            if (value !== null && value !== '') {
                                target.searchParams.set(key, value);
                            } else {
                                target.searchParams.delete(key);
                            }
                        });
                        target.searchParams.set('refresh', '1');

                        try {
                            const res = await fetch(target.toString(), {
                                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                            });
                            if (!res.ok) return;
                            const data = await res.json();
                            const tableContainer = document.querySelector('[data-live-slot=\"ticket-table\"]');
                            const summaryContainer = document.querySelector('[data-live-slot=\"ticket-summary\"]');
                            if (data.fragments?.['ticket-table'] && tableContainer) {
                                const parser = new DOMParser();
                                const doc = parser.parseFromString(data.fragments['ticket-table'], 'text/html');
                                const incomingResults = doc.querySelector('[data-ticket-results]');
                                const currentResults = document.querySelector('[data-ticket-results]');
                                if (incomingResults && currentResults) {
                                    currentResults.innerHTML = incomingResults.innerHTML;
                                    if (window.Alpine?.initTree) window.Alpine.initTree(currentResults);
                                } else {
                                    tableContainer.innerHTML = data.fragments['ticket-table'];
                                    if (window.Alpine?.initTree) window.Alpine.initTree(tableContainer);
                                }
                            }
                            if (data.fragments?.['ticket-summary'] && summaryContainer) {
                                summaryContainer.innerHTML = data.fragments['ticket-summary'];
                                if (window.Alpine?.initTree) window.Alpine.initTree(summaryContainer);
                            }
                            if (typeof data.hasResults !== 'undefined') {
                                this.hasResults = !!data.hasResults;
                            }
                            if (data.checksum) {
                                const container = document.querySelector('[data-live-refresh]');
                                if (container) {
                                    const cleanUrl = new URL(target.toString());
                                    cleanUrl.searchParams.delete('refresh');
                                    container.dataset.liveUrl = cleanUrl.toString();
                                    container.dataset.liveQuery = cleanUrl.searchParams.toString();
                                    container.dataset.liveChecksum = data.checksum;
                                    window.history.replaceState({}, '', cleanUrl.toString());
                                }
                            }
                        } catch (e) {
                            console.error(e);
                        }
                    },
                }"
                >
                    @if ($statusFilter)
                        <input type="hidden" name="status" value="{{ $statusFilter }}">
                    @endif

                    <div class="flex flex-col gap-3">
                        <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center rounded-2xl border border-slate-200/70 bg-slate-50/70 px-4 py-2.5 text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-600 transition duration-200">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                            <label for="department" class="text-slate-500">Departemen</label>
                            <select
                                id="department"
                                name="department"
                                class="h-[42px] w-full sm:min-w-[180px] rounded-[12px] border border-slate-200 bg-white px-[12px] py-[8px] text-[11px] font-medium text-slate-700 shadow-sm transition duration-150 focus:border-emerald-400 focus:ring-1 focus:ring-emerald-100"
                                onchange="this.form.submit()"
                            >
                                <option value="">Semua Dept</option>
                                @foreach ($departments as $department)
                                    <option value="{{ $department->id }}" @selected($departmentFilter == $department->id)>
                                        {{ $department->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                            <label for="start_date" class="text-slate-500">Mulai</label>
                            <input
                                type="date"
                                id="start_date"
                                name="start_date"
                                value="{{ $startDate }}"
                                class="h-[42px] w-full sm:min-w-[170px] rounded-[12px] border border-slate-200 bg-white px-[12px] py-[8px] text-[13px] font-medium text-slate-700 shadow-sm focus:border-emerald-400 focus:ring-1 focus:ring-emerald-100"
                            >
                        </div>
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                            <label for="end_date" class="text-slate-500">Selesai</label>
                            <input
                                type="date"
                                id="end_date"
                                name="end_date"
                                value="{{ $endDate }}"
                                class="h-[42px] w-full sm:min-w-[170px] rounded-[12px] border border-slate-200 bg-white px-[12px] py-[8px] text-[13px] font-medium text-slate-700 shadow-sm focus:border-emerald-400 focus:ring-1 focus:ring-emerald-100"
                            >
                        </div>
                        <button
                            type="submit"
                            class="inline-flex h-[42px] items-center rounded-[14px] bg-[#12824C] px-4 text-[11px] font-semibold text-white shadow-[0_3px_12px_rgba(18,130,76,0.2)] transition duration-150 hover:-translate-y-[1px] hover:shadow-[0_8px_14px_rgba(18,130,76,0.26)]"
                        >
                            Terapkan
                        </button>
                        <div class="flex-1 min-w-[220px] w-full sm:min-w-[260px] sm:ml-auto normal-case tracking-normal">
                            <label for="search" class="sr-only">Cari tiket</label>
                            <div class="relative search-shell flex w-full items-center gap-2 rounded-[18px] border border-slate-200 bg-white px-[12px] py-[6px] h-[44px] shadow-sm focus-within:border-emerald-400 focus-within:ring-1 focus-within:ring-emerald-100" @click.away="suggestionsOpen = false">
                                <input
                                    type="search"
                                    id="search"
                                    name="search"
                                    x-model="searchTerm"
                                    placeholder="Cari tiket..."
                                    class="min-w-0 flex-1 border-none bg-transparent px-[10px] py-[6px] text-[12px] font-medium text-slate-700 placeholder:text-slate-400 outline-none focus:ring-0 appearance-none"
                                    @input="queueFetch(); queueSearch()"
                                    @focus="suggestionsOpen = true; fetchSuggestions()"
                                >
                                <button
                                    type="button"
                                    class="relative z-10 inline-flex h-8 w-8 items-center justify-center rounded-full text-xs text-slate-500 hover:text-rose-500 hover:scale-110 transition"
                                    x-show="searchTerm"
                                    @click="clearSearch"
                                >
                                    ×
                                </button>
                                <button
                                    type="submit"
                                    class="inline-flex h-[36px] shrink-0 items-center rounded-[14px] bg-[#12824C] px-[12px] text-[10px] font-semibold uppercase tracking-wide text-white shadow-[0_3px_10px_rgba(18,130,76,0.2)] transition duration-150 hover:-translate-y-[1px] hover:shadow-[0_8px_14px_rgba(18,130,76,0.26)]"
                                >
                                    Search
                                </button>

                                <div
                                    x-show="suggestionsOpen && (suggestionsLoading || suggestions.length || searchTerm)"
                                    class="absolute left-0 right-0 top-[calc(100%+6px)] z-30 rounded-xl border border-slate-200 bg-white shadow-xl shadow-slate-300/30"
                                    x-transition
                                >
                                    <template x-if="suggestionsLoading">
                                        <div class="px-4 py-3 text-sm text-slate-500">Memuat...</div>
                                    </template>
                                    <template x-if="!suggestionsLoading && suggestions.length">
                                        <ul class="divide-y divide-slate-100">
                                            <template x-for="item in suggestions" :key="item.id">
                                                <li>
                                                    <a
                                                        class="flex w-full items-start gap-3 px-4 py-3 hover:bg-emerald-50 transition"
                                                        :href="item.url"
                                                        @click="suggestionsOpen = false"
                                                    >
                                                        <div class="flex-1 min-w-0">
                                                            <p class="text-xs font-semibold text-ink-400 uppercase tracking-[0.28em]" x-text="`#${item.id}`"></p>
                                                            <p class="font-semibold text-slate-800 truncate" x-text="item.title"></p>
                                                            <p class="text-[11px] text-slate-500 truncate" x-text="item.user ? `Pelapor: ${item.user}` : 'User tidak diketahui'"></p>
                                                        </div>
                                                        <span class="rounded-full border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-[0.18em] text-emerald-700" x-text="item.status"></span>
                                                    </a>
                                                </li>
                                            </template>
                                        </ul>
                                    </template>
                                    <template x-if="!suggestionsLoading && !suggestions.length && searchTerm">
                                        <div class="px-4 py-3 text-sm text-slate-500">Tidak ada tiket yang cocok.</div>
                                    </template>
                                </div>

                                <div
                                    x-show="!suggestionsLoading && !hasResults && searchTerm"
                                    class="absolute left-0 right-0 top-[calc(100%+6px)] z-20 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-600 shadow-lg shadow-slate-300/30"
                                    x-transition
                                >
                                    Tidak ada hasil untuk pencarian ini.
                                </div>
                            </div>
                        </div>
                    </div>

                </form>
            </div>

            <div data-ticket-results>
                <div class="space-y-4">
                    <div class="space-y-4 md:hidden">
                        @forelse ($tickets as $ticket)
                <article class="rounded-[20px] border border-slate-100 bg-white/95 p-4 shadow-[0_10px_36px_rgba(0,0,0,0.06)] animate-fade-up transition duration-200 hover:-translate-y-[2px] hover:scale-[1.01] hover:shadow-[0_12px_40px_rgba(0,0,0,0.08)] hover:shadow-emerald-100/70">
                    <div class="flex flex-col gap-3">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <p class="text-[0.68rem] font-semibold uppercase tracking-[0.32em] text-ink-400">Tiket</p>
                                <a
                                    href="{{ route('tickets.show', $ticket) }}"
                                    class="text-base font-semibold text-ink-900 transition hover:text-brand-700"
                                >
                                    #{{ $ticket->id }} • {{ $ticket->title }}
                                </a>
                            </div>
                            <x-ui.status-chip :status="$ticket->status" class="px-2.5 py-1 text-[0.65rem] tracking-[0.18em]" />
                        </div>

                        <p class="text-xs leading-relaxed text-ink-600 overflow-hidden text-ellipsis" style="-webkit-line-clamp: 2; display: -webkit-box; -webkit-box-orient: vertical;">
                            {{ Str::limit(strip_tags($ticket->description), 200) }}
                        </p>

                        <dl class="grid gap-3 text-xs text-ink-600 sm:grid-cols-2">
                            <div class="space-y-1">
                                <dt class="font-semibold text-ink-500">Pelapor</dt>
                                <dd>
                                    <div>{{ optional($ticket->user)->name ?? 'User eksternal' }}</div>
                                    <div class="text-[0.7rem] text-ink-400">{{ optional($ticket->user)->email ?? 'Tidak terdaftar' }}</div>
                                </dd>
                            </div>
                            <div class="space-y-1">
                                <dt class="font-semibold text-ink-500">Kategori</dt>
                                <dd>{{ optional($ticket->category)->name ?? 'Tidak ada' }}</dd>
                            </div>
                            <div class="space-y-1">
                                <dt class="font-semibold text-ink-500">Departemen</dt>
                                <dd>{{ optional($ticket->department)->name ?? 'Tidak ada' }}</dd>
                            </div>
                            <div class="space-y-1">
                                <dt class="font-semibold text-ink-500">Dibuat</dt>
                                <dd>
                                    <div>{{ $ticket->created_at->timezone(config('app.timezone'))->format('d M Y') }}</div>
                                    <div class="text-[0.7rem] text-ink-400">{{ $ticket->created_at->timezone(config('app.timezone'))->format('H:i') }} WIB</div>
                                </dd>
                            </div>
                        </dl>

                        @if ($ticket->attachments_count > 0)
                            <div class="flex flex-wrap gap-2">
                                @foreach ($ticket->attachments as $attachment)
                                    <a
                                        href="{{ route('tickets.attachments.download', [$ticket, $attachment]) }}"
                                        class="inline-flex items-center gap-2 rounded-full border border-ink-200 bg-ink-50 px-3 py-1 text-[0.7rem] font-semibold text-ink-700 transition hover:border-brand-200 hover:bg-brand-50"
                                    >
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M2.5 5.75A2.75 2.75 0 0 1 5.25 3h3a.75.75 0 0 1 0 1.5h-3A1.25 1.25 0 0 0 4 5.75v8.5A1.25 1.25 0 0 0 5.25 15.5h9.5A1.25 1.25 0 0 0 16 14.25v-8.5A1.25 1.25 0 0 0 14.75 4.5h-3a.75.75 0 0 1 0-1.5h3A2.75 2.75 0 0 1 17.5 5.75v8.5A2.75 2.75 0 0 1 14.75 17h-9.5A2.75 2.75 0 0 1 2.5 14.25v-8.5Zm6.53 2.22a.75.75 0 1 0-1.06 1.06l1.22 1.22H7a.75.75 0 0 0 0 1.5h2.19l-1.22 1.22a.75.75 0 0 0 1.06 1.06l2.5-2.5a.75.75 0 0 0 0-1.06l-2.5-2.5Zm2.72 1.28a.75.75 0 0 0 0 1.5h1a.75.75 0 0 0 0-1.5h-1Zm0 2a.75.75 0 0 0 0 1.5h1a.75.75 0 0 0 0-1.5h-1Z" clip-rule="evenodd" />
                                        <span class="max-w-[140px] truncate align-middle">{{ $attachment->original_name ?? $attachment->file_name ?? 'Attachment' }}</span>
                                    </a>
                                @endforeach
                            </div>
                        @else
                            <span class="text-[0.7rem] text-ink-400">&mdash;</span>
                        @endif

                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <a
                                href="{{ route('tickets.show', $ticket) }}"
                                class="inline-flex items-center gap-3 rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-[0_8px_20px_rgba(16,185,129,0.25)] transition duration-150 hover:-translate-y-[1px] hover:bg-emerald-700"
                            >
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-white/15 text-white">
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M9.78 15.78a.75.75 0 0 1-1.06 0l-4.5-4.5a.75.75 0 0 1 0-1.06l4.5-4.5a.75.75 0 1 1 1.06 1.06L6.31 9.25H15a.75.75 0 0 1 0 1.5H6.31l3.47 3.47a.75.75 0 0 1 0 1.06Z" clip-rule="evenodd" />
                                    </svg>
                                </span>
                                <span>Lihat Detail</span>
                            </a>

                            <form
                                method="POST"
                                action="{{ route('tickets.updateStatus', $ticket) }}"
                                class="flex flex-wrap items-center gap-3"
                            >
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="filter" value="{{ $statusFilter }}">
                                <input type="hidden" name="department_filter" value="{{ $departmentFilter }}">
                                @if ($searchTerm !== '')
                                    <input type="hidden" name="search" value="{{ $searchTerm }}">
                                @endif
                                @if ($startDate)
                                    <input type="hidden" name="start_date" value="{{ $startDate }}">
                                @endif
                                @if ($endDate)
                                    <input type="hidden" name="end_date" value="{{ $endDate }}">
                                @endif
                                <div class="flex flex-col gap-1">
                                    <span class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Ubah Status</span>
                                    <select
                                        name="status"
                                        data-current="{{ $ticket->status }}"
                                        data-ticket="{{ $ticket->id }}"
                                        class="rounded-[14px] border border-amber-200 bg-amber-50 px-[14px] py-[10px] text-sm font-semibold text-amber-800 shadow-sm focus:border-amber-300 focus:ring-1 focus:ring-amber-100"
                                        onchange="if (this.value === this.dataset.current) return; const label = this.options[this.selectedIndex] ? this.options[this.selectedIndex].text : this.value; if (!confirm('Ubah status tiket #' + this.dataset.ticket + ' ke ' + label + '?')) { this.value = this.dataset.current; return; } this.dataset.current = this.value; if (this.form.requestSubmit) { this.form.requestSubmit(); } else { this.form.submit(); }"
                                        aria-label="Ubah status tiket"
                                        title="Ubah status tiket"
                                    >
                                        @foreach ($statuses as $value => $label)
                                            <option value="{{ $value }}" @selected($ticket->status === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </form>
                        </div>
                    </div>
                </article>
            @empty
                <div class="mx-auto flex max-w-md flex-col items-center gap-3 rounded-[16px] bg-gradient-to-br from-white to-[#F6F9F8] px-8 py-8 text-center text-sm text-[#6b7280] shadow-[0_8px_32px_rgba(18,130,76,0.12)]">
                    <div class="relative">
                        <div class="absolute inset-0 rounded-full bg-emerald-100 blur-2xl opacity-60"></div>
                        <svg class="relative h-24 w-24" viewBox="0 0 120 120" role="img" aria-hidden="true">
                            <defs>
                                <linearGradient id="ticketMascotGradientMobile" x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" stop-color="#E3F5EE" />
                                    <stop offset="100%" stop-color="#F7FFFB" />
                                </linearGradient>
                            </defs>
                            <circle cx="60" cy="60" r="48" fill="url(#ticketMascotGradientMobile)" stroke="#CFEADF" stroke-width="2" />
                            <rect x="35" y="38" width="50" height="44" rx="12" fill="#fff" stroke="#CFEADF" stroke-width="2" />
                            <circle cx="50" cy="54" r="4" fill="#12824C" />
                            <circle cx="70" cy="54" r="4" fill="#12824C" />
                            <path d="M48 70c4 6 20 6 24 0" stroke="#53B77A" stroke-width="3" stroke-linecap="round" />
                            <circle cx="90" cy="40" r="8" fill="#FFD966" stroke="#F7C948" stroke-width="2" />
                        </svg>
                    </div>
                    <p class="text-sm font-semibold text-ink-800">Tidak ada tiket untuk filter ini.</p>
                    <p class="text-xs text-ink-500">Coba reset filter atau gunakan kata kunci yang lebih umum.</p>
                    <a
                        href="{{ route('tickets.index') }}"
                        class="mt-2 inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-4 py-2 text-xs font-semibold text-emerald-700 transition hover:-translate-y-0.5 hover:bg-emerald-100"
                    >
                        Reset filter
                    </a>
                </div>
            @endforelse
                    </div>

                    <div class="hidden md:block pt-4 md:pt-6 lg:pt-0">
                        <div class="w-full max-w-none min-w-0 border-t border-slate-100 px-3 pr-8 pt-4 sm:px-4 sm:pr-10 overflow-visible">
                            <table class="w-full table-fixed divide-y divide-slate-200">
                                <thead class="bg-slate-50/90 backdrop-blur text-[10px] uppercase tracking-[0.16em] text-slate-800 font-semibold sticky top-0 z-10">
                                    <tr>
                                        <th scope="col" class="px-2 py-2.5 text-left font-semibold w-[72px] min-w-[72px]">ID</th>
                                        <th scope="col" class="px-2 py-2.5 text-left font-semibold w-[220px] min-w-[220px] max-w-[220px] truncate">Judul</th>
                                        <th scope="col" class="px-2 py-2.5 text-left font-semibold w-[180px] min-w-[180px] truncate">Dari</th>
                                        <th scope="col" class="px-2 py-2.5 text-left font-semibold truncate">Kategori</th>
                                        <th scope="col" class="px-2 py-2.5 text-left font-semibold truncate hidden md:table-cell">Dept</th>
                                        <th scope="col" class="px-2 py-2.5 text-left font-semibold truncate hidden md:table-cell">Lampiran</th>
                                        <th scope="col" class="px-2 py-2.5 text-left font-semibold w-[120px] min-w-[120px] truncate">Status</th>
                                        <th scope="col" class="px-2 py-2.5 text-right font-semibold w-[160px] min-w-[160px] whitespace-nowrap">Dibuat</th>
                                        <th scope="col" class="px-2 py-2.5 text-right font-semibold w-[280px] min-w-[280px] whitespace-nowrap align-top">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 bg-white text-sm text-slate-800">
                                    @forelse ($tickets as $ticket)
                                        <tr class="table-hover-row group transition duration-150 {{ $loop->even ? 'bg-emerald-50/10' : 'bg-white' }} hover:bg-emerald-50/40 hover:shadow-md align-top">
                                            <td class="relative py-2 pl-4 pr-2 font-semibold text-slate-800 text-left whitespace-nowrap align-top before:absolute before:left-0 before:top-2 before:bottom-2 before:w-1 before:rounded-full before:bg-emerald-400 before:opacity-0 before:transition group-hover:before:opacity-100 w-[72px] min-w-[72px]">
                                                <span class="inline-flex items-center gap-1">
                                                    #{{ $ticket->id }}
                                                    <button
                                                        type="button"
                                                        class="text-slate-400 hover:text-emerald-600 transition"
                                                        onclick="navigator.clipboard?.writeText('{{ $ticket->id }}')"
                                                        aria-label="Salin ID tiket"
                                                    >
                                                        <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                                            <rect x="7" y="7" width="10" height="10" rx="2" />
                                                            <path d="M5 13H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h7a2 2 0 0 1 2 2v1" />
                                                        </svg>
                                                    </button>
                                                </span>
                                            </td>
                                            <td class="px-2 py-2 text-left align-top min-w-0 truncate w-[220px] min-w-[220px] max-w-[220px]">
                                                <a
                                                    href="{{ route('tickets.show', $ticket) }}"
                                                    class="block truncate font-semibold text-ink-900 text-sm transition duration-150 hover:-translate-y-[1px] hover:text-brand-700 hover:shadow-[0_2px_8px_rgba(0,0,0,0.06)] leading-tight"
                                                    title="{{ $ticket->title }}"
                                                >
                                                    {{ $ticket->title }}
                                                </a>
                                                <p
                                                    class="mt-1 text-[11px] leading-snug text-slate-500 overflow-hidden text-ellipsis min-w-0"
                                                    style="-webkit-line-clamp: 1; display: -webkit-box; -webkit-box-orient: vertical;"
                                                    title="{{ strip_tags($ticket->description) }}"
                                                >
                                                    {{ Str::limit(strip_tags($ticket->description), 180) }}
                                                </p>
                                            </td>
                                            <td class="px-2 py-2 text-sm text-slate-600 text-left align-top truncate w-[180px] min-w-[180px] max-w-[180px]">
                                                <div class="truncate">{{ optional($ticket->user)->name ?? 'User eksternal' }}</div>
                                                <div class="truncate text-xs text-slate-400">{{ optional($ticket->user)->email ?? 'Tidak terdaftar' }}</div>
                                            </td>
                                            <td class="px-2 py-2 text-sm text-slate-600 text-left align-top truncate" title="{{ optional($ticket->category)->name ?? 'Tidak ada' }}">
                                                <span class="inline-flex max-w-full items-center justify-center rounded-full bg-slate-50 px-2 py-0.5 text-[11px] font-medium text-slate-700 truncate">
                                                    {{ optional($ticket->category)->name ?? 'Tidak ada' }}
                                                </span>
                                            </td>
                                            <td class="px-2 py-2 text-sm text-slate-600 text-left align-top truncate hidden md:table-cell" title="{{ optional($ticket->department)->name ?? 'Tidak ada' }}">
                                                <span class="inline-flex max-w-full items-center justify-center rounded-full bg-slate-50 px-2 py-0.5 text-[11px] font-medium text-slate-700 truncate">
                                                    {{ optional($ticket->department)->name ?? 'Tidak ada' }}
                                                </span>
                                            </td>
                                            <td class="px-2 py-2 text-sm text-slate-600 text-left align-top truncate hidden md:table-cell">
                                                @if ($ticket->attachments_count > 0)
                                                    <div class="flex flex-wrap gap-2">
                                                        @foreach ($ticket->attachments as $attachment)
                                                            <a
                                                                href="{{ route('tickets.attachments.download', [$ticket, $attachment]) }}"
                                                                class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-[0.7rem] font-semibold text-slate-700 transition hover:border-emerald-200 hover:bg-emerald-50"
                                                            >
                                                                <svg class="h-3.5 w-3.5 text-slate-500" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                                                    <path d="M13.5 6.5 20 13a3.536 3.536 0 0 1-5 5l-7.5-7.5a3 3 0 1 1 4.243-4.243L16.5 11.5" />
                                                                    <path d="M8.5 12.5 11 15" />
                                                                </svg>
                                                                <span class="max-w-[140px] truncate align-middle">{{ $attachment->original_name ?? $attachment->file_name ?? 'Attachment' }}</span>
                                                            </a>
                                                        @endforeach
                                                    </div>
                                                @else
                                                    <span class="text-xs text-slate-400">&mdash;</span>
                                                @endif
                                            </td>
                                            <td class="px-2 py-2 text-center align-top w-[120px] min-w-[120px]">
                                                @php
                                                    $isClosed = in_array($ticket->status, ['resolved', 'closed'], true);
                                                    $dotColor = $isClosed ? '#dc2626' : '#10b981';
                                                @endphp
                                                <span class="inline-flex items-center justify-center gap-1.5 rounded-full bg-slate-50 px-2.5 py-1 text-[11px] font-semibold text-slate-700 border border-slate-200">
                                                    <span class="h-2 w-2 rounded-full" style="background-color: {{ $dotColor }}"></span>
                                                    <span class="capitalize">{{ str_replace('_', ' ', $ticket->status) }}</span>
                                                </span>
                                            </td>
                                            <td class="px-2 py-2 text-sm text-slate-600 text-right whitespace-nowrap align-top w-[160px] min-w-[160px]">
                                                <div class="font-medium truncate text-[13px]">{{ $ticket->created_at->timezone(config('app.timezone'))->format('d M Y') }}</div>
                                                <div class="text-xs text-slate-400 truncate">{{ $ticket->created_at->timezone(config('app.timezone'))->format('H:i') }} WIB</div>
                                            </td>
                                            <td class="px-2 py-2 text-right align-top overflow-visible w-[280px] min-w-[280px] whitespace-nowrap">
                                                <div class="flex flex-col items-end gap-2 opacity-80 transition group-hover:opacity-100 md:flex-row md:items-center md:justify-end">
                                                    <a
                                                        href="{{ route('tickets.show', $ticket) }}"
                                                        class="inline-flex h-8 w-full min-w-[90px] items-center justify-center rounded-lg bg-emerald-600 px-3 text-[11px] font-semibold text-white shadow-sm transition hover:bg-emerald-700 md:w-auto"
                                                    >
                                                        Detail
                                                    </a>
                                                    <form
                                                        method="POST"
                                                        action="{{ route('tickets.updateStatus', $ticket) }}"
                                                        class="flex w-full items-center md:w-auto"
                                                        data-live-form
                                                    >
                                                        @csrf
                                                        @method('PATCH')
                                                        @if ($statusFilter)
                                                            <input type="hidden" name="filter" value="{{ $statusFilter }}">
                                                        @endif
                                                        @if ($departmentFilter)
                                                            <input type="hidden" name="department_filter" value="{{ $departmentFilter }}">
                                                        @endif
                                                        @if ($searchTerm !== '')
                                                            <input type="hidden" name="search" value="{{ $searchTerm }}">
                                                        @endif
                                                        @if ($startDate)
                                                            <input type="hidden" name="start_date" value="{{ $startDate }}">
                                                        @endif
                                                        @if ($endDate)
                                                            <input type="hidden" name="end_date" value="{{ $endDate }}">
                                                        @endif
                                                        <div class="relative w-full md:w-auto">
                                                            <select
                                                                name="status"
                                                                data-current="{{ $ticket->status }}"
                                                                data-ticket="{{ $ticket->id }}"
                                                                class="w-full min-w-[160px] whitespace-nowrap rounded-lg border border-amber-200/80 bg-amber-50/80 bg-none px-3 py-2 pr-10 text-left text-[11px] font-semibold text-amber-800 shadow-sm focus:border-amber-300 focus:ring-2 focus:ring-amber-100 md:w-auto appearance-none"
                                                                onchange="if (this.value === this.dataset.current) return; const label = this.options[this.selectedIndex] ? this.options[this.selectedIndex].text : this.value; if (!confirm('Ubah status tiket #' + this.dataset.ticket + ' ke ' + label + '?')) { this.value = this.dataset.current; return; } this.dataset.current = this.value; if (this.form.requestSubmit) { this.form.requestSubmit(); } else { this.form.submit(); }"
                                                                aria-label="Ubah status tiket"
                                                                title="Ubah status tiket"
                                                            >
                                                                @foreach ($statuses as $value => $label)
                                                                    <option value="{{ $value }}" @selected($ticket->status === $value)>{{ $label }}</option>
                                                                @endforeach
                                                            </select>
                                                            <svg class="pointer-events-none absolute right-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-amber-700/70" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.25 8.29a.75.75 0 01-.02-1.06z" clip-rule="evenodd" />
                                                            </svg>
                                                        </div>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="px-3 py-10 text-center text-sm text-slate-500">
                                                <div class="mx-auto flex max-w-md flex-col items-center gap-3 rounded-[16px] bg-gradient-to-br from-white to-[#F6F9F8] px-8 py-8 text-center text-sm text-[#6b7280] shadow-[0_8px_32px_rgba(18,130,76,0.12)]">
                                                    <div class="relative">
                                                        <div class="absolute inset-0 rounded-full bg-emerald-100 blur-2xl opacity-60"></div>
                                                        <svg class="relative h-24 w-24" viewBox="0 0 120 120" role="img" aria-hidden="true">
                                                            <defs>
                                                                <linearGradient id="ticketMascotGradientDesktop" x1="0%" y1="0%" x2="100%" y2="100%">
                                                                    <stop offset="0%" stop-color="#E3F5EE" />
                                                                    <stop offset="100%" stop-color="#F7FFFB" />
                                                                </linearGradient>
                                                            </defs>
                                                            <circle cx="60" cy="60" r="48" fill="url(#ticketMascotGradientDesktop)" stroke="#CFEADF" stroke-width="2" />
                                                            <rect x="35" y="38" width="50" height="44" rx="12" fill="#fff" stroke="#CFEADF" stroke-width="2" />
                                                            <circle cx="50" cy="54" r="4" fill="#12824C" />
                                                            <circle cx="70" cy="54" r="4" fill="#12824C" />
                                                            <path d="M48 70c4 6 20 6 24 0" stroke="#53B77A" stroke-width="3" stroke-linecap="round" />
                                                            <circle cx="90" cy="40" r="8" fill="#FFD966" stroke="#F7C948" stroke-width="2" />
                                                        </svg>
                                                    </div>
                                                    <div class="text-sm font-semibold text-slate-800">Tidak ada tiket untuk filter ini.</div>
                                                    <p class="text-xs text-[#6b7280]">Reset filter atau gunakan kata kunci yang lebih umum.</p>
                                                    <a
                                                        href="{{ route('tickets.index') }}"
                                                        class="mt-2 inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-4 py-2 text-xs font-semibold text-emerald-700 transition hover:-translate-y-0.5 hover:bg-emerald-100"
                                                    >
                                                        Reset filter
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                @if ($tickets->hasPages())
                    <div class="border-t border-slate-100 pt-4">
                        {{ $tickets->onEachSide(1)->links() }}
                    </div>
                @endif
            </div>
</x-ui.panel>
