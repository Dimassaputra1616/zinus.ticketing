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
                <p class="text-base font-semibold text-[#0C1F2C] leading-tight">Pantau dan ubah status tiket yang masuk ke tim IT.</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <div class="badge-live inline-flex items-center gap-2 rounded-full border border-[#C5E5D0] bg-gradient-to-r from-[#E3F5EE] to-[#F7FFFB] px-3 py-1.5 text-[11px] font-semibold uppercase tracking-[0.24em] text-[#1B4A37] shadow-inner shadow-white/60">
                    Live 10s
                    <span class="live-dot h-[6px] w-[6px] rounded-full bg-[#34d399]"></span>
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

        <div class="lg:grid lg:grid-cols-[280px_minmax(0,1fr)] lg:gap-6">
            <div class="space-y-4">
                <div class="flex flex-wrap items-center gap-1.5 sm:gap-2 text-[11px] font-semibold uppercase tracking-[0.24em] pt-1">
                    @php
                        $baseFilters = [
                            'department' => $departmentFilter,
                            'search' => $searchTerm,
                            'start_date' => $startDate,
                            'end_date' => $endDate,
                        ];
                    @endphp
                    <a
                        href="{{ route('tickets.index', $filterParams($baseFilters)) }}"
                        data-live-link
                        class="rounded-full border border-[#C8E2D8] bg-[#EDF3F2] px-4 py-2 text-[#23455D] transition duration-200 hover:bg-[#D7F5E9] hover:text-[#183153] hover:border-[#A7DCC1] hover:-translate-y-0.5 shadow-sm {{ empty($statusFilter) ? 'bg-[#12824C] text-white border-transparent shadow-emerald-500/20 hover:text-white hover:bg-[#12824C]' : '' }}"
                    >
                        Semua
                    </a>
                    @foreach ($statuses as $key => $label)
                        <a
                            href="{{ route('tickets.index', $filterParams(array_merge($baseFilters, ['status' => $key]))) }}"
                            data-live-link
                            class="rounded-full border border-[#C8E2D8] bg-[#EDF3F2] px-4 py-2 capitalize text-[#23455D] transition duration-200 hover:bg-[#D7F5E9] hover:text-[#183153] hover:border-[#A7DCC1] hover:-translate-y-0.5 shadow-sm {{ ($statusFilter === $key) ? 'bg-[#12824C] text-white border-transparent shadow-emerald-500/20 hover:text-white hover:bg-[#12824C]' : '' }}"
                        >
                            {{ str_replace('_', ' ', $key) }}
                            <span class="ms-2 text-[0.68rem] font-bold text-ink-400">{{ $statusCounts[$key] ?? 0 }}</span>
                        </a>
                    @endforeach
                </div>

                <div class="border-b border-slate-100 pb-3 lg:border-b-0 lg:pb-0">
                    <form
                        method="GET"
                        action="{{ route('tickets.index') }}"
                        class="flex flex-col gap-3 lg:flex-col"
                        data-live-form
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
                        this.submitSearch();
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
                        const target = new URL(form.action);
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
                            const res = await fetch(target.toString(), { headers: { 'Accept': 'application/json' } });
                            if (!res.ok) return;
                            const data = await res.json();
                            const tableContainer = document.querySelector('[data-live-slot=\"ticket-table\"]');
                            const summaryContainer = document.querySelector('[data-live-slot=\"ticket-summary\"]');
                            if (data.fragments?.['ticket-table'] && tableContainer) {
                                tableContainer.innerHTML = data.fragments['ticket-table'];
                                if (window.Alpine?.initTree) window.Alpine.initTree(tableContainer);
                            }
                            if (data.fragments?.['ticket-summary'] && summaryContainer) {
                                summaryContainer.innerHTML = data.fragments['ticket-summary'];
                                if (window.Alpine?.initTree) window.Alpine.initTree(summaryContainer);
                            }
                            if (typeof data.hasResults !== 'undefined') {
                                this.hasResults = !!data.hasResults;
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
                            <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-700 shadow-sm transition duration-200">
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
                    </div>

                            <div class="flex-1 min-w-0 w-full">
                                <label for="search" class="sr-only">Cari tiket</label>
                                <div class="relative search-shell flex w-full items-center gap-2 rounded-[18px] border border-slate-200 bg-white px-[12px] py-[6px] h-[44px] shadow-sm focus-within:border-emerald-400 focus-within:ring-1 focus-within:ring-emerald-100" @click.away="suggestionsOpen = false">
                                    <input
                                        type="search"
                                        id="search"
                                        name="search"
                                        x-model="searchTerm"
                                        placeholder="Cari tiket..."
                                        class="min-w-0 flex-1 border-none bg-transparent px-[10px] py-[6px] text-[12px] text-slate-700 placeholder:text-slate-400 outline-none focus:ring-0 appearance-none"
                                        @input="queueFetch(); queueSearch()"
                                        @focus="suggestionsOpen = true; fetchSuggestions(); queueSearch()"
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
            </div>

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
                                        <span class="max-w-[140px] truncate align-middle">{{ $attachment->original_name }}</span>
                                    </a>
                                @endforeach
                            </div>
                        @else
                            <span class="text-[0.7rem] text-ink-400">&mdash;</span>
                        @endif

                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <a
                                href="{{ route('tickets.show', $ticket) }}"
                                class="inline-flex items-center gap-3 rounded-xl border border-ink-100 bg-gradient-to-r from-white to-ink-50 px-4 py-2 text-sm font-semibold text-ink-700 shadow-[0_2px_8px_rgba(0,0,0,0.06)] transition duration-150 hover:-translate-y-[1px] hover:border-brand-200 hover:from-brand-50/80 hover:text-brand-800"
                            >
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-brand-50 text-brand-700">
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
                                <select
                                    name="status"
                                    class="rounded-[14px] border border-slate-200 bg-white px-[14px] py-[10px] text-sm font-medium text-ink-700 shadow-sm focus:border-emerald-400 focus:ring-1 focus:ring-emerald-100"
                                    onchange="this.form.submit()"
                                >
                                    @foreach ($statuses as $value => $label)
                                        <option value="{{ $value }}" @selected($ticket->status === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
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
                    <p class="text-sm font-semibold text-ink-800">Belum ada tiket ditemukan.</p>
                    <p class="text-xs text-ink-500">Coba ubah kata kunci, filter departemen, atau rentang tanggal.</p>
                </div>
            @endforelse
        </div>

        <div class="hidden md:block pt-4 md:pt-6 lg:pt-0">
            <div class="w-full max-w-none px-4 border-t border-slate-100 pt-4 overflow-visible" style="scrollbar-gutter: stable;">
                <table class="w-full table-auto divide-y divide-slate-200">
                    <thead class="bg-slate-50/90 backdrop-blur text-2xs uppercase tracking-[0.2em] text-slate-800 font-semibold sticky top-0 z-10">
                        <tr>
                            <th scope="col" class="px-2 py-2.5 text-left font-semibold w-[8%]">ID</th>
                            <th scope="col" class="px-2 py-2.5 text-left font-semibold w-[20%]">Judul</th>
                            <th scope="col" class="px-2 py-2.5 text-left font-semibold w-[16%]">Dari</th>
                            <th scope="col" class="px-2 py-2.5 text-left font-semibold w-[10%]">Kategori</th>
                            <th scope="col" class="px-2 py-2.5 text-left font-semibold w-[10%]">Dept</th>
                            <th scope="col" class="px-2 py-2.5 text-left font-semibold w-[10%]">Lampiran</th>
                            <th scope="col" class="px-2 py-2.5 text-left font-semibold w-[8%]">Status</th>
                            <th scope="col" class="px-2 py-2.5 text-right font-semibold w-[10%]">Dibuat</th>
                            <th scope="col" class="px-2 py-2.5 text-right font-semibold w-[8%] sticky right-0 bg-white">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white text-sm text-slate-800">
                        @forelse ($tickets as $ticket)
                            <tr class="table-hover-row transition duration-150 {{ $loop->even ? 'bg-emerald-50/10' : 'bg-white' }} hover:bg-[#f7faf9] hover:shadow-md align-top">
                                <td class="px-2 py-2 font-semibold text-slate-800 text-left whitespace-nowrap align-top">
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
                                <td class="max-w-xs px-2 py-2 text-left align-top min-w-0">
                                    <a
                                        href="{{ route('tickets.show', $ticket) }}"
                                        class="block max-w-[260px] truncate font-semibold text-ink-900 text-sm transition duration-150 hover:-translate-y-[1px] hover:text-brand-700 hover:shadow-[0_2px_8px_rgba(0,0,0,0.06)] leading-tight break-words"
                                        title="{{ $ticket->title }}"
                                    >
                                        {{ $ticket->title }}
                                    </a>
                                    <p
                                        class="mt-1 max-w-[260px] text-[11px] leading-snug text-slate-500 overflow-hidden text-ellipsis break-words min-w-0"
                                        style="-webkit-line-clamp: 1; display: -webkit-box; -webkit-box-orient: vertical;"
                                        title="{{ strip_tags($ticket->description) }}"
                                    >
                                        {{ Str::limit(strip_tags($ticket->description), 180) }}
                                    </p>
                                </td>
                                <td class="px-2 py-2 text-sm text-slate-600 text-left align-top">
                                    <div>{{ optional($ticket->user)->name ?? 'User eksternal' }}</div>
                                    <div class="max-w-[180px] truncate text-xs text-slate-400">{{ optional($ticket->user)->email ?? 'Tidak terdaftar' }}</div>
                                </td>
                                <td class="px-2 py-2 text-sm text-slate-600 text-left whitespace-nowrap align-top" title="{{ optional($ticket->category)->name ?? 'Tidak ada' }}">
                                    <span class="inline-flex items-center justify-center rounded-full bg-slate-50 px-2 py-0.5 text-[11px] font-medium text-slate-700">
                                        {{ optional($ticket->category)->name ?? 'Tidak ada' }}
                                    </span>
                                </td>
                                <td class="px-2 py-2 text-sm text-slate-600 text-left whitespace-nowrap align-top" title="{{ optional($ticket->department)->name ?? 'Tidak ada' }}">
                                    <span class="inline-flex items-center justify-center rounded-full bg-slate-50 px-2 py-0.5 text-[11px] font-medium text-slate-700 truncate max-w-[160px]">
                                        {{ optional($ticket->department)->name ?? 'Tidak ada' }}
                                    </span>
                                </td>
                                <td class="px-2 py-2 text-sm text-slate-600 max-w-[180px] text-left align-top">
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
                                                    <span class="max-w-[140px] truncate align-middle">{{ $attachment->original_name }}</span>
                                                </a>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-xs text-slate-400">&mdash;</span>
                                    @endif
                                </td>
                                <td class="px-2 py-2 text-center align-top">
                                    @php
                                        $isClosed = in_array($ticket->status, ['resolved', 'closed'], true);
                                        $dotColor = $isClosed ? '#dc2626' : '#10b981';
                                    @endphp
                                    <span class="inline-flex items-center justify-center gap-1.5 rounded-full bg-slate-50 px-2.5 py-1 text-[11px] font-semibold text-slate-700 border border-slate-200">
                                        <span class="h-2 w-2 rounded-full" style="background-color: {{ $dotColor }}"></span>
                                        <span class="capitalize">{{ str_replace('_', ' ', $ticket->status) }}</span>
                                    </span>
                                </td>
                                <td class="px-2 py-2 text-sm text-slate-600 text-right whitespace-nowrap align-top max-w-[120px] truncate">
                                    <div class="font-medium truncate text-[13px]">{{ $ticket->created_at->timezone(config('app.timezone'))->format('d M Y') }}</div>
                                    <div class="text-xs text-slate-400 truncate">{{ $ticket->created_at->timezone(config('app.timezone'))->format('H:i') }} WIB</div>
                                </td>
                                <td class="px-3 py-2 w-[8%] text-right sticky right-0 bg-white">
                                    <div class="flex justify-end items-center gap-2">
                                        <a
                                            href="{{ route('tickets.show', $ticket) }}"
                                            class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-gradient-to-r from-white to-slate-50 px-2 py-1.5 text-[11px] font-semibold text-slate-700 shadow-[0_2px_8px_rgba(0,0,0,0.06)] transition duration-150 hover:-translate-y-[1px] hover:border-emerald-200 hover:from-emerald-50/70 hover:text-emerald-800"
                                        >
                                            <span class="inline-flex h-5 w-5 items-center justify-center rounded-md bg-brand-50 text-brand-700">
                                                <svg class="h-2 w-2" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                    <path fill-rule="evenodd" d="M9.78 15.78a.75.75 0 0 1-1.06 0l-4.5-4.5a.75.75 0 0 1 0-1.06l4.5-4.5a.75.75 0 1 1 1.06 1.06L6.31 9.25H15a.75.75 0 0 1 0 1.5H6.31l3.47 3.47a.75.75 0 0 1 0 1.06Z" clip-rule="evenodd" />
                                                </svg>
                                            </span>
                                            <span class="whitespace-nowrap">Detail</span>
                                        </a>
                                        <form
                                            method="POST"
                                            action="{{ route('tickets.updateStatus', $ticket) }}"
                                            class="flex items-center"
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
                                            <select
                                                name="status"
                                                class="h-9 rounded-lg border border-emerald-200 bg-white px-3 pr-8 text-[12px] font-semibold text-slate-800 leading-tight shadow-sm focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100"
                                                onchange="this.form.submit()"
                                            >
                                                @foreach ($statuses as $value => $label)
                                                    <option value="{{ $value }}" @selected($ticket->status === $value)>{{ $label }}</option>
                                                @endforeach
                                            </select>
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
                                        <div class="text-sm font-semibold text-slate-800">Tidak ada tiket ditemukan.</div>
                                        <p class="text-xs text-[#6b7280]">Coba ubah kata kunci atau filter pencarian.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($tickets->hasPages())
            <div class="border-t border-slate-100 pt-4">
                {{ $tickets->onEachSide(1)->links() }}
            </div>
        @endif
    </div>
</x-ui.panel>
