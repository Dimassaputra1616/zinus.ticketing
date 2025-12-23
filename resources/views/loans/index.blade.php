<x-app-layout>
    @php
        $statuses = $statuses ?? [];
        $statusBadge = [
            'waiting' => 'bg-amber-100 text-amber-700 border border-amber-200',
            'approved' => 'bg-emerald-100 text-emerald-700 border border-emerald-200',
            'returned' => 'bg-sky-100 text-sky-700 border border-sky-200',
            'rejected' => 'bg-rose-100 text-rose-700 border border-rose-200',
        ];
    @endphp

    <div x-data="loanPage({
        devices: @js($devices),
        statuses: @js($statuses),
        csrf: '{{ csrf_token() }}',
        isAdmin: {{ $isAdmin ? 'true' : 'false' }},
        initialSearch: @js($search ?? ''),
    })" class="w-full max-w-none mx-0 px-6 lg:px-10 pt-6 pb-10 space-y-6">
        <x-ui.section-hero
            pill="Asset & Inventory"
            title="Log Peminjaman"
            description="Pantau dan kelola peminjaman perangkat. Ajukan pinjam dan update status secara terpusat."
        >
            <x-slot:icon>
                <svg class="h-7 w-7 text-[#12824C]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="4" width="18" height="13" rx="2" />
                    <path d="M8 21h8" />
                    <path d="M10 17v4" />
                    <path d="M14 17v4" />
                </svg>
            </x-slot:icon>
            @unless($isAdmin)
                <x-slot:side>
                    <x-ui.button type="button" size="sm" variant="primary" class="shadow-button" @click="openAdd()">
                        + Ajukan Peminjaman
                    </x-ui.button>
                    <p class="text-xs text-emerald-800 mt-1">Lihat status pengajuanmu atau ajukan peminjaman baru.</p>
                </x-slot:side>
            @endunless
        </x-ui.section-hero>

        @if (session('ok'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-3 text-emerald-700 shadow-sm shadow-emerald-100">
                {{ session('ok') }}
            </div>
        @endif

        <x-ui.panel class="shadow-md border-ink-100/80 bg-gradient-to-br from-[#F6F9F8] via-white to-[#EDF3F2] w-full max-w-none mx-0 px-0">
            <div class="space-y-4">
                <div class="border-b border-slate-100 pb-3">
                    <form method="GET" action="{{ route('loans.index') }}" class="flex flex-col gap-3 lg:flex-row lg:flex-wrap lg:items-center lg:justify-between" data-live-form>
                        <div class="flex flex-wrap items-center gap-3 w-full">
                            @unless($isAdmin)
                                <x-ui.button type="button" size="sm" variant="primary" class="shadow-button" @click="openAdd()">
                                    + Ajukan Peminjaman
                                </x-ui.button>
                            @endunless
                            <div class="flex-1 min-w-[260px]">
                                <label class="sr-only" for="search">Cari</label>
                                <div class="flex items-center gap-2 rounded-[16px] border border-slate-200 bg-white px-3 py-2 focus-within:border-emerald-400 focus-within:ring-1 focus-within:ring-emerald-100">
                                    <svg class="h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8" /><path d="m21 21-4.3-4.3" /></svg>
                                    <input
                                        type="search"
                                        id="search"
                                        name="search"
                                        x-model="searchTerm"
                                        placeholder="{{ $isAdmin ? 'Cari user/device...' : 'Cari device atau catatan kamu...' }}"
                                        class="w-full border-none bg-transparent text-sm text-slate-700 placeholder:text-slate-400 focus:ring-0"
                                        @input="queueSearch"
                                    >
                                </div>
                            </div>

                            @if ($isAdmin)
                                <div class="flex items-center gap-2">
                                    <label class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Status</label>
                                    <select name="status" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-emerald-400 focus:ring-1 focus:ring-emerald-100" onchange="this.form.submit()">
                                        <option value="">Semua</option>
                                        @foreach ($statuses as $key => $label)
                                            <option value="{{ $key }}" @selected($statusFilter === $key)>{{ ucfirst($label) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="flex items-center gap-2">
                                    <label class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Device</label>
                                    <select name="device_id" class="min-w-[180px] rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-emerald-400 focus:ring-1 focus:ring-emerald-100" onchange="this.form.submit()">
                                        <option value="">Semua Device</option>
                                        @foreach ($devices as $device)
                                            <option value="{{ $device->id }}" @selected($deviceFilter == $device->id)>{{ $device->name }}{{ $device->code ? ' • '.$device->code : '' }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="flex items-center gap-2">
                                    <label class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Mulai</label>
                                    <input type="date" name="start_date" value="{{ $startDate }}" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-emerald-400 focus:ring-1 focus:ring-emerald-100">
                                </div>
                                <div class="flex items-center gap-2">
                                    <label class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Selesai</label>
                                    <input type="date" name="end_date" value="{{ $endDate }}" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-emerald-400 focus:ring-1 focus:ring-emerald-100">
                                </div>
                                <x-ui.button type="submit" size="sm" variant="primary">Terapkan</x-ui.button>
                            @else
                                <div class="flex items-center gap-2">
                                    <label class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Status</label>
                                    <select name="status" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-emerald-400 focus:ring-1 focus:ring-emerald-100" onchange="this.form.submit()">
                                        <option value="">Semua</option>
                                        @foreach ($statuses as $key => $label)
                                            <option value="{{ $key }}" @selected($statusFilter === $key)>{{ ucfirst($label) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                        </div>
                    </form>
                </div>

                @include('loans.partials.table', ['logs' => $logs, 'statuses' => $statuses, 'statusBadge' => $statusBadge, 'isAdmin' => $isAdmin])
            </div>
        </x-ui.panel>

        @unless($isAdmin)
        <template x-teleport="body">
            <div
                x-show="showAdd"
                x-transition.opacity
                class="fixed inset-0 z-[130] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
                x-cloak
            >
                <div class="w-full max-w-lg rounded-3xl bg-white shadow-2xl ring-1 ring-emerald-100 overflow-hidden">
                    <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4 bg-gradient-to-r from-[#F3FBF7] via-white to-[#F0F7FF]">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-[0.28em] text-emerald-700 flex items-center gap-2">
                                <span class="inline-flex h-7 w-7 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700 shadow-inner shadow-white/70">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14" /><path d="M5 12h14" /></svg>
                                </span>
                                Ajukan Peminjaman
                            </p>
                            <h3 class="text-xl font-semibold text-ink-900 leading-tight mt-1">Form peminjaman device</h3>
                        </div>
                        <button type="button" class="text-slate-400 hover:text-slate-600 rounded-full p-1" @click="closeAdd()">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form class="px-6 py-5 space-y-4" method="POST" action="{{ route('loans.store') }}">
                        @csrf
                        <div class="space-y-1">
                            <label class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Device</label>
                            <select name="device_id" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm focus:border-emerald-400 focus:ring-1 focus:ring-emerald-100" required>
                                <option value="">Pilih device</option>
                                @foreach ($devices as $device)
                                    <option value="{{ $device->id }}">{{ $device->name }}{{ $device->code ? ' • '.$device->code : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="grid gap-3 lg:grid-cols-2">
                            <div class="space-y-1">
                                <label class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Tanggal Pinjam</label>
                                <input type="date" name="start_date" required class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm focus:border-emerald-400 focus:ring-1 focus:ring-emerald-100">
                            </div>
                            <div class="space-y-1">
                                <label class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Tanggal Kembali</label>
                                <input type="date" name="end_date" required class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm focus:border-emerald-400 focus:ring-1 focus:ring-emerald-100">
                            </div>
                        </div>
                        <div class="space-y-1">
                            <label class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Alasan Peminjaman</label>
                            <textarea name="reason" rows="3" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm focus:border-emerald-400 focus:ring-1 focus:ring-emerald-100" placeholder="Tuliskan kebutuhan peminjaman"></textarea>
                        </div>
                        <div class="flex items-center justify-end gap-3 pt-2">
                            <button type="button" class="text-sm font-semibold text-slate-500 hover:text-slate-700" @click="closeAdd()">Batal</button>
                            <x-ui.button type="submit" size="md" variant="primary" class="shadow-button">
                                Kirim Pengajuan
                            </x-ui.button>
                        </div>
                    </form>
                </div>
            </div>
        </template>
        @endunless

    </div>

    <div id="loan-confirm-overlay" class="fixed inset-0 z-[140] hidden items-center justify-center bg-slate-900/60 backdrop-blur-sm px-4">
        <div class="w-full max-w-sm rounded-2xl bg-white shadow-2xl ring-1 ring-slate-100 p-5 space-y-4">
            <div class="flex items-start gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-rose-50 text-rose-600">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10" /><path d="m15 9-6 6" /><path d="m9 9 6 6" /></svg>
                </div>
                <div class="space-y-1">
                    <h4 class="text-base font-semibold text-ink-900">Konfirmasi aksi</h4>
                    <p id="loan-confirm-message" class="text-sm text-slate-600"></p>
                </div>
            </div>
            <div class="flex items-center justify-end gap-3">
                <button type="button" class="text-sm font-semibold text-slate-600 hover:text-slate-800" onclick="window.hideLoanConfirm()">Batal</button>
                <x-ui.button type="button" size="sm" variant="primary" onclick="window.submitLoanConfirm()">Lanjutkan</x-ui.button>
            </div>
        </div>
    </div>

    @if (session('ok'))
        <div id="loan-toast" class="fixed bottom-6 right-6 z-[150] max-w-xs rounded-2xl bg-white px-4 py-3 shadow-lg ring-1 ring-emerald-100 flex items-start gap-3 animate-fadeIn">
            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m20 6-11 11-5-5" /></svg>
            </div>
            <div class="text-sm text-slate-800">{{ session('ok') }}</div>
            <button type="button" class="ml-auto text-slate-400 hover:text-slate-600" onclick="this.parentElement.remove()">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18" /><path d="m6 6 12 12" /></svg>
            </button>
        </div>
    @endif

    <script>
        function loanPage(config) {
            return {
                devices: config.devices || [],
                statuses: config.statuses || {},
                csrf: config.csrf,
                isAdmin: config.isAdmin,
                searchTerm: config.initialSearch || '',
                showAdd: false,
                searchTimer: null,
                openAdd() { this.showAdd = true; },
                closeAdd() { this.showAdd = false; },
                queueSearch() {
                    clearTimeout(this.searchTimer);
                    this.searchTimer = setTimeout(() => {
                        const target = new URL(window.location.href);
                        if (this.searchTerm) {
                            target.searchParams.set('search', this.searchTerm);
                        } else {
                            target.searchParams.delete('search');
                        }
                        target.searchParams.delete('page');
                        window.location.href = target.toString();
                    }, 350);
                },
            };
        }

        (function setupLoanActions() {
            let confirmTargetForm = null;
            const overlay = document.getElementById('loan-confirm-overlay');
            const messageEl = document.getElementById('loan-confirm-message');

            window.hideLoanConfirm = function () {
                overlay.classList.add('hidden');
                overlay.classList.remove('flex');
                confirmTargetForm = null;
            };

            window.submitLoanConfirm = function () {
                if (confirmTargetForm) {
                    setLoading(confirmTargetForm, true);
                    confirmTargetForm.submit();
                }
                hideLoanConfirm();
            };

            function setLoading(form, isLoading) {
                const btn = form.querySelector('button[type="submit"]');
                const spinner = form.querySelector('.loan-btn-spinner');
                const label = form.querySelector('.loan-btn-label');
                const row = form.closest('.loan-row');
                if (btn) btn.disabled = isLoading;
                if (spinner && label) {
                    spinner.classList.toggle('hidden', !isLoading);
                    label.classList.toggle('hidden', isLoading);
                }
                if (row) {
                    row.classList.toggle('ring-2', isLoading);
                    row.classList.toggle('ring-emerald-200', isLoading);
                    row.classList.toggle('bg-emerald-50/50', isLoading);
                }
            }

            document.querySelectorAll('.loan-action-form').forEach((form) => {
                form.addEventListener('submit', (e) => {
                    e.preventDefault();
                    const needConfirm = form.dataset.confirm || '';
                    if (needConfirm) {
                        confirmTargetForm = form;
                        messageEl.textContent = needConfirm;
                        overlay.classList.remove('hidden');
                        overlay.classList.add('flex');
                        return;
                    }
                    setLoading(form, true);
                    form.submit();
                }, { once: false });
            });
        })();

        (function setupToast() {
            const toast = document.getElementById('loan-toast');
            if (!toast) return;
            setTimeout(() => {
                toast.classList.add('opacity-0', 'translate-y-2');
                setTimeout(() => toast.remove(), 350);
            }, 3000);
        })();
    </script>
</x-app-layout>
