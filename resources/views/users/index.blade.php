<x-app-layout>
    <div
        class="w-full pt-4 sm:pt-6 pb-10 space-y-8"
        x-data="userPage({
            csrf: '{{ csrf_token() }}',
            initialSearch: @js($search ?? ''),
            authId: {{ Auth::id() ?? 'null' }}
        })"
        x-init="initPage()"
    >
        <x-ui.page-hero
            pill="Kelola User"
            brand="Zinus Dream"
            eyebrow="Dashboard Ticketing"
            title="Atur akses dan peran tim"
            description="Tambah akun baru, ubah role, dan jaga keamanan akses support dari satu panel."
            :badges="[
                ['label' => $adminCount . ' Admin', 'dot' => '#10b981'],
                ['label' => $staffCount . ' User', 'dot' => '#38bdf8'],
                ['label' => $totalUsers . ' Total', 'dot' => '#fbbf24'],
            ]"
        >
            <x-slot:side>
                <div class="space-y-3">
                    <p class="text-sm font-semibold text-ink-900">Gunakan role admin hanya untuk pengelola.</p>
                    <p class="text-xs text-ink-600">Jaga keamanan dengan password kuat dan reset berkala.</p>
                    <x-ui.button
                        type="button"
                        size="sm"
                        variant="primary"
                        class="w-full justify-center"
                        icon='<svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>'
                        @click="openAdd()"
                    >
                        Tambah User Baru
                    </x-ui.button>
                </div>
            </x-slot:side>
        </x-ui.page-hero>

        @if ($errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-6 py-4 text-rose-700 shadow-sm shadow-rose-100">
                <ul class="list-disc space-y-1 ps-4 text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('ok'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-6 py-4 text-emerald-700 shadow-sm shadow-emerald-100">
                {{ session('ok') }}
            </div>
        @endif

        <div class="fixed top-4 right-4 z-50 space-y-2 max-w-sm" aria-live="polite">
            <template x-for="toast in toasts" :key="toast.id">
                <div
                    class="flex items-start gap-3 rounded-xl border px-4 py-3 shadow-lg bg-white"
                    :class="toast.type === 'error' ? 'border-rose-200 text-rose-700 shadow-rose-200/70' : 'border-emerald-200 text-emerald-700 shadow-emerald-200/70'"
                >
                    <span class="mt-0.5 inline-flex h-8 w-8 items-center justify-center rounded-lg"
                        :class="toast.type === 'error' ? 'bg-rose-50 text-rose-600' : 'bg-emerald-50 text-emerald-600'">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path x-show="toast.type !== 'error'" d="M20 6 9 17l-5-5" />
                            <g x-show="toast.type === 'error'">
                                <circle cx="12" cy="12" r="9" />
                                <path d="M12 8v4" />
                                <path d="M12 16h.01" />
                            </g>
                        </svg>
                    </span>
                    <div class="leading-snug">
                        <p class="font-semibold" x-text="toast.title"></p>
                        <p class="text-sm" x-text="toast.message"></p>
                    </div>
                </div>
            </template>
        </div>

        @php
            $controlBase = 'h-12 rounded-xl border border-slate-200 bg-slate-50/70 px-4 text-sm text-slate-800 shadow-sm focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200';
            $inputClass = 'w-full ' . $controlBase;
            $labelClass = 'text-2xs font-semibold uppercase tracking-[0.24em] text-slate-500';
            $isSuperAdmin = Auth::user()?->is_super_admin;
        @endphp

        <template x-teleport="body">
            <div
                x-show="showReset || showAdd || showUpdate || showConfirm"
                x-transition.opacity
                class="fixed inset-0 z-[120] bg-slate-900/80 backdrop-blur-sm"
                aria-hidden="true"
                x-cloak
            ></div>
        </template>

        <x-ui.panel
            title="Daftar User"
            subtitle="Ubah role atau hapus akun yang tidak diperlukan"
            class="shadow-md border-ink-100/80 bg-white/95 [&>div:first-child]:!border-b [&>div:first-child]:!px-5 [&>div:first-child]:!py-3 [&_h3]:text-lg [&_h3]:font-semibold space-y-4"
        >
            <form class="flex flex-col gap-3 text-sm sm:flex-row sm:items-center" @submit.prevent="submitSearch">
                <label class="text-2xs font-semibold uppercase tracking-[0.22em] text-slate-500">Cari User</label>
                <div class="relative flex items-center" @click.away="suggestionsOpen = false">
                    <input
                        type="search"
                        name="q"
                        x-model="searchTerm"
                        placeholder="Cari nama/email/role..."
                        class="w-full sm:min-w-[240px] rounded-xl border border-slate-200 bg-white px-4 py-2 pr-20 text-sm shadow-sm focus:border-emerald-400 focus:ring-1 focus:ring-emerald-100 appearance-none"
                        @input="queueTypeahead"
                        @focus="suggestionsOpen = true; fetchSuggestions()"
                    >
                    <button
                        type="button"
                        class="absolute right-12 inline-flex h-8 w-8 items-center justify-center rounded-full text-xs text-slate-500 hover:text-rose-500 hover:scale-110 transition relative z-10"
                        x-show="searchTerm"
                        @click="clearSearch"
                    >
                        ×
                    </button>
                    <x-ui.button type="submit" size="sm" variant="primary" class="absolute right-1">Search</x-ui.button>

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
                                        <button
                                            type="button"
                                            class="w-full px-4 py-3 text-left hover:bg-emerald-50 transition flex items-start justify-between gap-3"
                                            @click="selectSuggestion(item)"
                                        >
                                            <div>
                                                <p class="font-semibold text-slate-800" x-text="item.name"></p>
                                                <p class="text-xs text-slate-500" x-text="item.email"></p>
                                            </div>
                                            <span class="text-[11px] font-semibold uppercase tracking-[0.2em] text-emerald-600" x-text="item.role"></span>
                                        </button>
                                    </li>
                                </template>
                            </ul>
                        </template>
                        <template x-if="!suggestionsLoading && !suggestions.length && searchTerm">
                            <div class="px-4 py-3 text-sm text-slate-500">Tidak ada hasil untuk pencarian ini.</div>
                        </template>
                    </div>
                </div>
            </form>

            <div x-ref="tableContainer">
                @include('users.partials.table')
            </div>
        </x-ui.panel>

        @if ($isSuperAdmin)
        <template x-teleport="body">
            <div
                x-show="showReset"
                x-transition.opacity
                class="fixed inset-0 z-[130] flex items-center justify-center"
                x-cloak
            >
                <div class="relative w-full max-w-xl overflow-hidden rounded-3xl bg-white/95 shadow-[0_30px_120px_-45px_rgba(15,23,42,0.65)] ring-1 ring-emerald-100/70 pointer-events-auto">
                    <div class="absolute -left-20 -top-28 h-44 w-44 rounded-full bg-emerald-200/40 blur-3xl"></div>
                    <div class="absolute -right-24 -bottom-24 h-48 w-48 rounded-full bg-sky-200/35 blur-3xl"></div>

                    <div class="relative flex items-start justify-between gap-4 border-b border-white/60 bg-gradient-to-r from-[#F2FBF7] via-white to-[#EEF6FF] px-6 py-5">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-[0.28em] text-emerald-700 flex items-center gap-2">
                                <span class="inline-flex h-7 w-7 items-center justify-center rounded-xl bg-white shadow-inner shadow-white/60 text-emerald-700">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14" /><path d="M5 12h14" /></svg>
                                </span>
                                Reset Password
                            </p>
                            <h3 class="mt-1 text-xl font-semibold text-slate-900" x-text="resetName || 'User'"></h3>
                            <p class="text-sm text-emerald-900/80">Pastikan password baru kuat agar akses support tetap aman.</p>
                        </div>
                        <button type="button" class="rounded-full p-1.5 text-slate-400 transition hover:bg-white hover:text-slate-600" @click="closeReset()">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form class="relative space-y-5 px-6 py-6" x-ref="resetForm" @submit.prevent="submitReset">
                        <div class="space-y-2">
                            <div class="flex items-center justify-between text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">
                                <span>Password Admin</span>
                                <span class="text-emerald-600">Wajib</span>
                            </div>
                            <div class="relative" x-data="{ show: false }">
                                <div class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-emerald-500">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 15v2" /><path d="M8 11V7a4 4 0 1 1 8 0v4" /><rect x="6" y="11" width="12" height="10" rx="2" /></svg>
                                </div>
                                <input
                                    :type="show ? 'text' : 'password'"
                                    x-model="resetForm.admin_password"
                                    name="admin_password"
                                    placeholder="Masukkan password Anda"
                                    required
                                    class="w-full h-12 rounded-2xl border border-slate-200/80 bg-white/70 px-4 pl-11 pr-12 text-sm text-slate-900 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 focus:bg-white"
                                >
                                <button
                                    type="button"
                                    class="absolute inset-y-0 right-3 flex items-center text-emerald-500/80 hover:text-emerald-700"
                                    @click="show = !show"
                                    :aria-pressed="show"
                                    :title="show ? 'Sembunyikan password' : 'Lihat password'"
                                >
                                    <svg x-show="!show" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12Z" />
                                        <circle cx="12" cy="12" r="3" />
                                    </svg>
                                    <svg x-show="show" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="m3 3 18 18" />
                                        <path d="M10.58 10.58a2 2 0 0 0 2.84 2.84" />
                                        <path d="M9.88 4.24A10.82 10.82 0 0 1 12 4c7 0 11 8 11 8a16.8 16.8 0 0 1-3.64 4.8" />
                                        <path d="M6.61 6.61A16.85 16.85 0 0 0 1 12s4 8 11 8a10.94 10.94 0 0 0 5.39-1.61" />
                                    </svg>
                                </button>
                            </div>
                            <p class="text-xs text-rose-500" x-text="errors.admin_password" x-show="errors.admin_password"></p>
                        </div>

                        <div class="space-y-2">
                            <div class="flex items-center justify-between text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">
                                <span>Password Baru</span>
                                <span class="text-emerald-600">Min. 8 karakter</span>
                            </div>
                            <div class="relative" x-data="{ show: false }">
                                <div class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-emerald-500">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 15v2" /><path d="M8 11V7a4 4 0 1 1 8 0v4" /><rect x="6" y="11" width="12" height="10" rx="2" /></svg>
                                </div>
                                <input
                                    :type="show ? 'text' : 'password'"
                                    x-model="resetForm.password"
                                    required
                                    minlength="8"
                                    class="w-full h-12 rounded-2xl border border-slate-200/80 bg-white/70 px-4 pl-11 pr-12 text-sm text-slate-900 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 focus:bg-white"
                                    x-ref="resetPassword"
                                >
                                <button
                                    type="button"
                                    class="absolute inset-y-0 right-3 flex items-center text-emerald-500/80 hover:text-emerald-700"
                                    @click="show = !show"
                                    :aria-pressed="show"
                                    :title="show ? 'Sembunyikan password' : 'Lihat password'"
                                >
                                    <svg x-show="!show" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12Z" />
                                        <circle cx="12" cy="12" r="3" />
                                    </svg>
                                    <svg x-show="show" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="m3 3 18 18" />
                                        <path d="M10.58 10.58a2 2 0 0 0 2.84 2.84" />
                                        <path d="M9.88 4.24A10.82 10.82 0 0 1 12 4c7 0 11 8 11 8a16.8 16.8 0 0 1-3.64 4.8" />
                                        <path d="M6.61 6.61A16.85 16.85 0 0 0 1 12s4 8 11 8a10.94 10.94 0 0 0 5.39-1.61" />
                                    </svg>
                                </button>
                            </div>
                            <p class="text-xs text-rose-500" x-text="errors.password" x-show="errors.password"></p>
                        </div>

                        <div class="space-y-2">
                            <div class="flex items-center justify-between text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">
                                <span>Konfirmasi Password Baru</span>
                                <span class="text-emerald-600">Harus sama</span>
                            </div>
                            <div class="relative" x-data="{ show: false }">
                                <div class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-emerald-500">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3 5h18" /><path d="M7 10h10" /><path d="M10 15h4" /><path d="M12 4v17" /></svg>
                                </div>
                                <input
                                    :type="show ? 'text' : 'password'"
                                    x-model="resetForm.password_confirmation"
                                    required
                                    minlength="8"
                                    class="w-full h-12 rounded-2xl border border-slate-200/80 bg-white/70 px-4 pl-11 pr-12 text-sm text-slate-900 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 focus:bg-white"
                                >
                                <button
                                    type="button"
                                    class="absolute inset-y-0 right-3 flex items-center text-emerald-500/80 hover:text-emerald-700"
                                    @click="show = !show"
                                    :aria-pressed="show"
                                    :title="show ? 'Sembunyikan password' : 'Lihat password'"
                                >
                                    <svg x-show="!show" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12Z" />
                                        <circle cx="12" cy="12" r="3" />
                                    </svg>
                                    <svg x-show="show" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="m3 3 18 18" />
                                        <path d="M10.58 10.58a2 2 0 0 0 2.84 2.84" />
                                        <path d="M9.88 4.24A10.82 10.82 0 0 1 12 4c7 0 11 8 11 8a16.8 16.8 0 0 1-3.64 4.8" />
                                        <path d="M6.61 6.61A16.85 16.85 0 0 0 1 12s4 8 11 8a10.94 10.94 0 0 0 5.39-1.61" />
                                    </svg>
                                </button>
                            </div>
                            <p class="text-xs text-rose-500" x-text="errors.password_confirmation" x-show="errors.password_confirmation"></p>
                        </div>

                    <div class="flex flex-wrap items-center justify-between gap-4 border-t border-slate-100 pt-5">
                        <div class="flex items-center gap-2 text-xs text-slate-500">
                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 shadow-inner shadow-white/70">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 5.29a.75.75 0 0 1 .006 1.06l-6.71 6.75a.75.75 0 0 1-1.065.005L3.29 9.17a.75.75 0 0 1 1.06-1.06l4.02 4.02 6.18-6.21a.75.75 0 0 1 1.054-.006Z" clip-rule="evenodd" /></svg>
                            </span>
                            <span>Gunakan kombinasi huruf besar, kecil, angka, dan simbol.</span>
                        </div>
                        <div class="flex items-center gap-4">
                            <button type="button" class="text-sm font-semibold text-slate-500 hover:text-slate-700" @click="closeReset()">Batal</button>
                            <x-ui.button type="submit" size="md" variant="primary" class="shadow-button ml-1">
                                Reset Password
                            </x-ui.button>
                        </div>
                    </div>
                    </form>
                </div>
            </div>
        </template>
        @endif

        <template x-teleport="body">
            <div
                x-show="showAdd"
                x-transition.opacity
                class="fixed inset-0 z-[130] flex items-center justify-center"
                x-cloak
                @wheel.prevent
                @touchmove.prevent
            >
                <div class="w-full max-w-2xl max-h-[90vh] rounded-3xl bg-white shadow-[0_30px_120px_-40px_rgba(16,24,40,0.45)] ring-1 ring-emerald-100/60 overflow-hidden pointer-events-auto flex flex-col">
                    <div class="flex-1 overflow-y-auto flex flex-col">
                        <div class="relative overflow-hidden border-b border-slate-100 bg-gradient-to-r from-[#F3FBF7] via-white to-[#F0F7FF] px-6 py-5 sticky top-0 z-10">
                            <div class="absolute -left-8 -top-10 h-24 w-24 rounded-full bg-emerald-200/30 blur-3xl"></div>
                            <div class="absolute -right-10 -bottom-14 h-28 w-28 rounded-full bg-sky-200/30 blur-3xl"></div>
                            <div class="relative flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.32em] text-emerald-700 flex items-center gap-2">
                                        <span class="inline-flex h-7 w-7 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700 shadow-inner shadow-white/70">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14" /><path d="M5 12h14" /></svg>
                                        </span>
                                        Tambah User
                                    </p>
                                    <h3 class="mt-1 text-2xl font-semibold text-[#0C1F2C] leading-tight">Buat akun baru</h3>
                                    <p class="text-sm text-slate-600">Isi detail user dengan password kuat. Semua kolom wajib diisi.</p>
                                </div>
                                <button type="button" class="text-slate-400 hover:text-slate-600 rounded-full p-1" @click="closeAdd()">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <form class="flex-1 px-6 py-6 flex flex-col gap-5" @submit.prevent="submitAdd" novalidate>
                            <div class="grid gap-4 lg:grid-cols-2">
                                <div class="space-y-2">
                                    <div class="flex items-center justify-between text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">
                                        <span>Nama Lengkap</span>
                                        <span class="text-[10px] text-emerald-600">Wajib</span>
                                    </div>
                                    <div class="relative">
                                        <div class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-emerald-500">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" /><circle cx="12" cy="7" r="4" /></svg>
                                        </div>
                                        <input type="text" x-model="addForm.name" required class="{{ $inputClass }} pl-9 bg-white/60 focus:bg-white">
                                    </div>
                                    <p class="text-xs text-rose-500" x-text="errors.name" x-show="errors.name"></p>
                                </div>
                                <div class="space-y-2">
                                    <div class="flex items-center justify-between text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">
                                        <span>Email</span>
                                        <span class="text-[10px] text-emerald-600">Wajib</span>
                                    </div>
                                    <div class="relative">
                                        <div class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-emerald-500">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16v16H4z" /><path d="m4 4 8 8 8-8" /></svg>
                                        </div>
                                        <input type="email" x-model="addForm.email" required class="{{ $inputClass }} pl-9 bg-white/60 focus:bg-white">
                                    </div>
                                    <p class="text-xs text-rose-500" x-text="errors.email" x-show="errors.email"></p>
                                </div>
                                <div class="space-y-2">
                                    <div class="flex items-center justify-between text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">
                                        <span>Password</span>
                                        <span class="text-[10px] text-emerald-600">Min. 8 karakter</span>
                                    </div>
                                    <div class="relative" x-data="{ show: false }">
                                        <div class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-emerald-500">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height "11" rx="2" /><path d="M7 11V7a5 5 0 0 1 10 0v4" /></svg>
                                        </div>
                                        <input :type="show ? 'text' : 'password'" x-model="addForm.password" required class="{{ $inputClass }} pl-9 pr-12 bg-white/60 focus:bg-white">
                                        <button
                                            type="button"
                                            class="absolute inset-y-0 right-3 flex items-center text-emerald-500/80 hover:text-emerald-700"
                                            @click="show = !show"
                                            :aria-pressed="show"
                                            :title="show ? 'Sembunyikan password' : 'Lihat password'"
                                        >
                                            <svg x-show="!show" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12Z" />
                                                <circle cx="12" cy="12" r="3" />
                                            </svg>
                                            <svg x-show="show" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="m3 3 18 18" />
                                                <path d="M10.58 10.58a2 2 0 0 0 2.84 2.84" />
                                                <path d="M9.88 4.24A10.82 10.82 0 0 1 12 4c7 0 11 8 11 8a16.8 16.8 0 0 1-3.64 4.8" />
                                                <path d="M6.61 6.61A16.85 16.85 0 0 0 1 12s4 8 11 8a10.94 10.94 0 0 0 5.39-1.61" />
                                            </svg>
                                        </button>
                                    </div>
                                    <p class="text-xs text-rose-500" x-text="errors.password" x-show="errors.password"></p>
                                </div>
                                <div class="space-y-2">
                                    <div class="flex items-center justify-between text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">
                                        <span>Konfirmasi Password</span>
                                        <span class="text-[10px] text-emerald-600">Harus sama</span>
                                    </div>
                                    <div class="relative" x-data="{ show: false }">
                                        <div class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-emerald-500">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 15v2" /><path d="M8 11V7a4 4 0 1 1 8 0v4" /><rect x="6" y="11" width="12" height="10" rx="2" /></svg>
                                        </div>
                                        <input :type="show ? 'text' : 'password'" x-model="addForm.password_confirmation" required class="{{ $inputClass }} pl-9 pr-12 bg-white/60 focus:bg-white">
                                        <button
                                            type="button"
                                            class="absolute inset-y-0 right-3 flex items-centered text-emerald-500/80 hover:text-emerald-700"
                                            @click="show = !show"
                                            :aria-pressed="show"
                                            :title="show ? 'Sembunyikan password' : 'Lihat password'"
                                        >
                                            <svg x-show="!show" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12Z" />
                                                <circle cx="12" cy="12" r="3" />
                                            </svg>
                                            <svg x-show="show" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="m3 3 18 18" />
                                                <path d="M10.58 10.58a2 2 0 0 0 2.84 2.84" />
                                                <path d="M9.88 4.24A10.82 10.82 0 0 1 12 4c7 0 11 8 11 8a16.8 16.8 0 0 1-3.64 4.8" />
                                                <path d="M6.61 6.61A16.85 16.85 0 0 0 1 12s4 8 11 8a10.94 10.94 0 0 0 5.39-1.61" />
                                            </svg>
                                        </button>
                                    </div>
                                    <p class="text-xs text-rose-500" x-text="errors.password_confirmation" x-show="errors.password_confirmation"></p>
                                </div>
                                <div class="space-y-2 lg:col-span-2">
                                    <div class="flex items-center justify-between text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">
                                        <span>Role</span>
                                        <span class="text-[10px] text-emerald-600">Pilih akses</span>
                                    </div>
                                    <div class="relative">
                                        <div class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-emerald-500">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3 5h18" /><path d="M7 10h10" /><path d="M10 15h4" /><path d="M12 4v17" /></svg>
                                        </div>
                                        <select x-model="addForm.role" required class="{{ $inputClass }} pl-9 bg-white/60 focus:bg-white">
                                            <option value="user">User</option>
                                            <option value="admin">Admin</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="sticky bottom-0 bg-white pt-4 border-t border-slate-100 flex items-center justify-between gap-3">
                                <div class="flex items-center gap-2 text-xs text-slate-500">
                                    <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="CurrentColor"><path fill-rule="evenodd" d="M16.704 5.29a.75.75 0 0 1 .006 1.06l-6.71 6.75a.75.75 0 0 1-1.065.005L3.29 9.17a.75.75 0 0 1 1.06-1.06l4.02 4.02 6.18-6.21a.75.75 0 0 1 1.054-.006Z" clip-rule="evenodd" /></svg>
                                    </span>
                                    <span>Pastikan email aktif dan password kuat.</span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <button type="button" class="text-sm font-semibold text-slate-500 hover:text-slate-700" @click="closeAdd()">Batal</button>
                                    <x-ui.button type="submit" size="md" variant="primary" class="shadow-button">
                                        Simpan User
                                    </x-ui.button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </template>

        @if ($isSuperAdmin)
        <template x-teleport="body">
            <div
                x-show="showUpdate"
                x-transition.opacity
                class="fixed inset-0 z-[130] flex items-center justify-center"
                x-cloak
            >
                <div class="w-full max-w-lg rounded-2xl bg-white shadow-2xl shadow-slate-900/20 ring-1 ring-slate-100 pointer-events-auto">
                    <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Update Role</p>
                            <h3 class="text-lg font-semibold text-slate-900" x-text="updateForm.name"></h3>
                            <p class="text-xs text-slate-500" x-text="updateForm.email"></p>
                        </div>
                        <button type="button" class="text-slate-400 hover:text-slate-600" @click="closeUpdate()">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form class="space-y-4 px-6 py-5" @submit.prevent="submitUpdate">
                        <div class="space-y-2">
                            <label class="{{ $labelClass }}">Role</label>
                            <select x-model="updateForm.role" required class="{{ $inputClass }}">
                                <option value="user">User</option>
                                <option value="admin">Admin</option>
                            </select>
                            <p class="text-xs text-rose-500" x-text="errors.role" x-show="errors.role"></p>
                        </div>
                        <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-4">
                            <button type="button" class="text-sm font-semibold text-slate-500 hover:text-slate-700" @click="closeUpdate()">Batal</button>
                            <x-ui.button type="submit" size="md" variant="primary">
                                Update Role
                            </x-ui.button>
                        </div>
                    </form>
                </div>
            </div>
        </template>
        @endif

        @if ($isSuperAdmin)
        <template x-teleport="body">
            <div
                x-show="showConfirm"
                x-transition.opacity
                class="fixed inset-0 z-[130] flex items-center justify-center"
                x-cloak
            >
                <div class="w-full max-w-md rounded-2xl bg-white shadow-2xl shadow-slate-900/20 ring-1 ring-slate-100 p-6 space-y-4 pointer-events-auto">
                    <h3 class="text-lg font-semibold text-slate-900">Hapus User?</h3>
                    <p class="text-sm text-slate-600">Anda yakin ingin menghapus <span class="font-semibold" x-text="confirmData.name"></span>? Tindakan ini tidak dapat dibatalkan.</p>
                    <div class="flex items-center justify-end gap-3">
                        <button type="button" class="text-sm font-semibold text-slate-500 hover:text-slate-700" @click="closeConfirm()">Batal</button>
                        <x-ui.button type="button" size="md" class="border border-red-200 bg-[#ffe4e6] text-red-600 hover:border-red-200 hover:bg-[#fecdd3]" @click="doDelete()">
                            Ya, hapus
                        </x-ui.button>
                    </div>
                </div>
            </div>
        </template>
        @endif
    </div>

    <script>
        function safeUUID() {
            if (
                typeof window !== 'undefined'
                && window.crypto
                && typeof window.crypto.randomUUID === 'function'
            ) {
                return window.crypto.randomUUID();
            }

            const ts = Date.now().toString(36);
            const rand = Math.random().toString(36).substring(2, 10);
            return `${ts}-${rand}`;
        }

        function userPage(config) {
            return {
                csrf: config.csrf,
                authId: config.authId,
                searchTerm: config.initialSearch || '',
                showReset: false,
                showAdd: false,
                showUpdate: false,
                showConfirm: false,
                searchTimer: null,
                suggestions: [],
                suggestionsOpen: false,
                suggestionsLoading: false,
                resetName: '',
                resetAction: '',
                resetForm: { admin_password: '', password: '', password_confirmation: '' },
                addForm: { name: '', email: '', password: '', password_confirmation: '', role: 'user' },
                updateForm: { id: null, name: '', email: '', role: 'user', action: '' },
                confirmData: { name: '', action: '' },
                errors: {},
                toasts: [],
                initPage() {},
                generateToastId() {
                    return safeUUID();
                },
                addToast(message, type = 'success', title = type === 'error' ? 'Gagal' : 'Berhasil') {
                    const id = this.generateToastId();
                    this.toasts.push({ id, message, type, title });
                    setTimeout(() => {
                        this.toasts = this.toasts.filter(t => t.id !== id);
                    }, 3200);
                },
                clearErrors() {
                    this.errors = {};
                },
                openAdd() {
                    this.clearErrors();
                    this.addForm = { name: '', email: '', password: '', password_confirmation: '', role: 'user' };
                    this.showAdd = true;
                },
                closeAdd() { this.showAdd = false; },
                openUpdate(user) {
                    this.clearErrors();
                    this.updateForm = { ...user };
                    this.showUpdate = true;
                },
                closeUpdate() { this.showUpdate = false; },
                openReset(name, action) {
                    if (!action) {
                        this.addToast('Link reset tidak ditemukan.', 'error');
                        return;
                    }
                    console.debug('openReset', name, action);
                    this.resetName = name;
                    this.resetAction = action;
                    this.resetForm = { admin_password: '', password: '', password_confirmation: '' };
                    this.showReset = true;
                    this.$nextTick(() => this.$refs.resetPassword?.focus());
                },
                closeReset() {
                    this.showReset = false;
                    this.resetName = '';
                    this.resetAction = '';
                    this.resetForm = { admin_password: '', password: '', password_confirmation: '' };
                },
                openConfirm(name, action) {
                    this.confirmData = { name, action };
                    this.showConfirm = true;
                },
                closeConfirm() { this.showConfirm = false; },
                validateAdd() {
                    this.clearErrors();
                    if (!this.addForm.name.trim()) this.errors.name = 'Nama wajib diisi.';
                    const email = this.addForm.email.trim();
                    this.addForm.email = email;
                    if (!email) {
                        this.errors.email = 'Email wajib diisi.';
                    } else if (!/^[^@\s]+@[^@\s]+\.[^@\s]+$/i.test(email)) {
                        this.errors.email = 'Format email tidak valid.';
                    }
                    if (!this.addForm.password) this.errors.password = 'Password wajib diisi.';
                    if (this.addForm.password && this.addForm.password.length < 8) this.errors.password = 'Minimal 8 karakter.';
                    if (this.addForm.password_confirmation !== this.addForm.password) this.errors.password_confirmation = 'Password tidak sama.';
                    return Object.keys(this.errors).length === 0;
                },
                async submitAdd() {
                    if (!this.validateAdd()) return;
                    const formData = new FormData();
                    Object.entries(this.addForm).forEach(([k, v]) => formData.append(k, v));
                    formData.append('_token', this.csrf);
                    try {
                        const res = await fetch('{{ route('users.store') }}', {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': this.csrf,
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            credentials: 'same-origin',
                            body: formData,
                        });
                        const isJson = res.headers.get('content-type')?.includes('application/json');
                        const data = isJson ? await res.json().catch(() => ({})) : {};
                        if (!res.ok) {
                            this.errors = data.errors ?? {};
                            this.addToast(data.message || `Gagal menambah user (status ${res.status})`, 'error');
                            return;
                        }
                        this.addToast(data.message || 'User baru ditambahkan');
                        this.closeAdd();
                        this.refreshTable();
                    } catch (e) {
                        this.addToast('Terjadi kesalahan jaringan', 'error');
                    }
                },
                async submitUpdate() {
                    if (!this.updateForm.action) return;
                    this.clearErrors();
                    const formData = new FormData();
                    formData.append('role', this.updateForm.role);
                    formData.append('_token', this.csrf);
                    try {
                        const res = await fetch(this.updateForm.action, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': this.csrf,
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            credentials: 'same-origin',
                            body: formData,
                        });
                        const isJson = res.headers.get('content-type')?.includes('application/json');
                        const data = isJson ? await res.json().catch(() => ({})) : {};
                        if (!res.ok) {
                            this.errors = data.errors ?? {};
                            this.addToast(data.message || `Gagal memperbarui role (status ${res.status})`, 'error');
                            return;
                        }
                        this.addToast(data.message || 'Role diperbarui');
                        this.closeUpdate();
                        this.refreshTable();
                    } catch (e) {
                        this.addToast('Terjadi kesalahan jaringan', 'error');
                    }
                },
                async submitReset() {
                    if (!this.resetAction) return;
                    this.clearErrors();
                    if (!this.resetForm.admin_password) {
                        this.errors.admin_password = 'Password admin wajib diisi.';
                        return;
                    }
                    if (this.resetForm.password !== this.resetForm.password_confirmation) {
                        this.errors.password_confirmation = 'Password tidak sama.';
                        return;
                    }
                    const formData = new FormData();
                    formData.append('admin_password', this.resetForm.admin_password);
                    formData.append('password', this.resetForm.password);
                    formData.append('password_confirmation', this.resetForm.password_confirmation);
                    formData.append('_token', this.csrf);
                    try {
                        const res = await fetch(this.resetAction, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': this.csrf,
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            credentials: 'same-origin',
                            body: formData,
                        });
                        const isJson = res.headers.get('content-type')?.includes('application/json');
                        const data = isJson ? await res.json().catch(() => ({})) : {};
                        if (!res.ok || !isJson) {
                            this.errors = data.errors ?? {};
                            const message = data.message
                                || (!isJson ? 'Sesi kadaluarsa, silakan login ulang.' : `Gagal reset password (status ${res.status})`);
                            this.addToast(message, 'error');
                            return;
                        }
                        this.addToast(data.message || 'Password direset');
                        this.closeReset();
                    } catch (e) {
                        this.addToast('Terjadi kesalahan jaringan', 'error');
                    }
                },
                async confirmDelete(name, action) {
                    if (this.authId && action.includes('/' + this.authId)) {
                        this.addToast('Tidak bisa menghapus akun sendiri.', 'error');
                        return;
                    }
                    this.openConfirm(name, action);
                },
                async doDelete() {
                    if (!this.confirmData.action) return;
                    const formData = new FormData();
                    formData.append('_token', this.csrf);
                    formData.append('_method', 'DELETE');
                    try {
                        const res = await fetch(this.confirmData.action, {
                            method: 'POST',
                            headers: { 'Accept': 'application/json' },
                            body: formData,
                        });
                        const data = await res.json();
                        if (!res.ok) {
                            this.addToast(data.message || 'Gagal menghapus user', 'error');
                            return;
                        }
                        this.addToast(data.message || 'User dihapus');
                        this.closeConfirm();
                        this.refreshTable();
                    } catch (e) {
                        this.addToast('Terjadi kesalahan jaringan', 'error');
                    }
                },
                async refreshTable(url = null) {
                    const target = new URL(url || window.location.href);
                    target.searchParams.set('fragment', '1');
                    try {
                        const res = await fetch(target.toString(), { headers: { 'Accept': 'application/json' } });
                        if (!res.ok) return;
                        const data = await res.json();
                        if (data.table) {
                            this.$refs.tableContainer.innerHTML = data.table;
                            if (window.Alpine && window.Alpine.initTree) {
                                window.Alpine.initTree(this.$refs.tableContainer);
                            }
                        }
                    } catch (e) {
                        console.error(e);
                    }
                },
                async submitSearch() {
                    const target = new URL(window.location.href);
                    if (this.searchTerm) {
                        target.searchParams.set('q', this.searchTerm);
                    } else {
                        target.searchParams.delete('q');
                    }
                    target.searchParams.delete('page');
                    await this.refreshTable(target.toString());
                    history.replaceState({}, '', target.toString().replace('fragment=1', '').replace('&&', '&'));
                },
                queueTypeahead() {
                    clearTimeout(this.searchTimer);
                    this.suggestionsOpen = true;
                    this.searchTimer = setTimeout(() => {
                        this.submitSearch();
                        this.fetchSuggestions();
                    }, 250);
                },
                clearSearch() {
                    this.searchTerm = '';
                    this.suggestions = [];
                    this.suggestionsOpen = false;
                    this.submitSearch();
                },
                async fetchSuggestions() {
                    const term = this.searchTerm.trim();
                    if (!term) {
                        this.suggestions = [];
                        return;
                    }
                    this.suggestionsLoading = true;
                    try {
                        const target = new URL('{{ route('users.index') }}');
                        target.searchParams.set('autocomplete', '1');
                        target.searchParams.set('q', term);
                        const res = await fetch(target.toString(), { headers: { 'Accept': 'application/json' } });
                        if (!res.ok) throw new Error('Failed');
                        const data = await res.json();
                        this.suggestions = data.suggestions || [];
                    } catch (e) {
                        this.suggestions = [];
                    } finally {
                        this.suggestionsLoading = false;
                    }
                },
                selectSuggestion(item) {
                    if (!item) return;
                    this.searchTerm = item.name;
                    this.suggestionsOpen = false;
                    this.submitSearch();
                },
                async quickUpdateRole(payload) {
                    if (!payload?.action) return;
                    const formData = new FormData();
                    formData.append('role', payload.role);
                    formData.append('_token', this.csrf);
                    try {
                        const res = await fetch(payload.action, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': this.csrf,
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            credentials: 'same-origin',
                            body: formData,
                        });
                        const isJson = res.headers.get('content-type')?.includes('application/json');
                        const data = isJson ? await res.json().catch(() => ({})) : {};
                        if (!res.ok || !isJson) {
                            this.addToast(data.message || `Gagal memperbarui role (status ${res.status})`, 'error');
                            return;
                        }
                        this.addToast(data.message || 'Role diperbarui');
                        this.refreshTable();
                    } catch (e) {
                        this.addToast('Terjadi kesalahan jaringan', 'error');
                    }
                }
            };
        }
    </script>
</x-app-layout>
