<x-guest-layout>
    <div class="space-y-7">
        <div class="rounded-3xl border border-emerald-100 bg-emerald-50 p-5 shadow-sm">
            <p class="text-[10px] font-bold uppercase tracking-[0.22em] text-emerald-700">Password baru</p>
            <h3 class="mt-2 text-xl font-black tracking-tight text-slate-950">Buat password baru</h3>
            <p class="mt-1.5 text-sm leading-relaxed text-slate-600">
                Kode verifikasi sudah valid untuk <span class="font-semibold text-slate-900">{{ $email }}</span>. Silakan buat password baru.
            </p>
        </div>

        <x-auth-session-status
            class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700 shadow-sm"
            :status="session('status')"
        />

        <form method="POST" action="{{ route('password.store') }}" class="space-y-5">
        @csrf

        <input type="hidden" name="email" value="{{ old('email', $email) }}">

        <div class="space-y-2">
            <x-input-label for="email_display" value="Email" />
            <input
                id="email_display"
                class="block h-12 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 text-sm font-semibold text-slate-600 shadow-sm"
                type="email"
                value="{{ old('email', $email) }}"
                disabled
            >
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="space-y-2">
            <x-input-label for="password" :value="__('Password')" />
            <div class="relative" x-data="{ show: false }">
                <x-text-input id="password" class="block h-12 w-full rounded-2xl pr-12" x-bind:type="show ? 'text' : 'password'" name="password" required autocomplete="new-password" autofocus />
                <button
                    type="button"
                    class="absolute inset-y-0 right-3 flex items-center text-gray-500 hover:text-gray-700"
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
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="space-y-2">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />

            <div class="relative" x-data="{ show: false }">
                <x-text-input id="password_confirmation" class="block h-12 w-full rounded-2xl pr-12"
                                x-bind:type="show ? 'text' : 'password'"
                                name="password_confirmation" required autocomplete="new-password" />
                <button
                    type="button"
                    class="absolute inset-y-0 right-3 flex items-center text-gray-500 hover:text-gray-700"
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

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="grid gap-3 sm:grid-cols-[0.9fr_1.4fr] sm:items-center">
            <a
                href="{{ route('password.code', ['email' => old('email', $email)]) }}"
                class="inline-flex h-12 items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-600 shadow-sm transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700"
            >
                Kembali ke Kode
            </a>

            <button
                type="submit"
                class="inline-flex h-12 items-center justify-center rounded-2xl bg-gradient-to-r from-[#0B2F26] via-emerald-700 to-[#12824C] px-5 text-xs font-black uppercase tracking-[0.18em] text-white shadow-xl shadow-emerald-900/25 transition hover:-translate-y-0.5 hover:shadow-emerald-900/35 focus:outline-none focus:ring-4 focus:ring-emerald-200"
            >
                Simpan Password Baru
            </button>
        </div>
    </form>
    </div>
</x-guest-layout>
