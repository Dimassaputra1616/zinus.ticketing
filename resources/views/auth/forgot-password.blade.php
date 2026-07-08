<x-guest-layout>
    <div class="space-y-7">
        <div class="relative overflow-hidden rounded-3xl border border-emerald-100 bg-gradient-to-br from-emerald-50 via-white to-slate-50 p-5 shadow-sm">
            <div class="absolute -right-10 -top-10 h-28 w-28 rounded-full bg-emerald-300/20 blur-2xl"></div>
            <div class="absolute -bottom-12 left-6 h-24 w-24 rounded-full bg-cyan-300/10 blur-2xl"></div>

            <div class="relative flex items-start gap-4">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-white text-emerald-700 shadow-lg shadow-emerald-900/10 ring-1 ring-emerald-100">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect x="4" y="10" width="16" height="10" rx="2" />
                        <path d="M8 10V7a4 4 0 0 1 8 0v3" />
                        <path d="M12 14v2" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.18em] text-emerald-700 ring-1 ring-emerald-200/70">
                        {{ __('messages.reset_password') }}
                    </span>
                    <h3 class="mt-3 text-xl font-black tracking-tight text-slate-950">Kirim kode reset password</h3>
                    <p class="mt-1.5 text-sm leading-relaxed text-slate-600">
                        Masukkan email akun Anda. Sistem akan mengirim kode verifikasi 6 digit yang berlaku selama 10 menit.
                    </p>
                </div>
            </div>
        </div>

        <!-- Session Status -->
        <x-auth-session-status
            class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700 shadow-sm"
            :status="session('status')"
        />

        <form method="POST" action="{{ route('password.email') }}" class="space-y-6">
            @csrf

            <!-- Email Address -->
            <div class="space-y-2.5">
                <div class="flex items-center justify-between gap-3">
                    <x-input-label for="email" :value="__('messages.email')" class="text-sm font-bold text-slate-800" />
                    <span class="text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-400">Kode 6 digit</span>
                </div>
                <label class="group relative block">
                    <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 transition group-focus-within:text-emerald-600">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect x="3" y="5" width="18" height="14" rx="2" />
                            <path d="m3 7 9 6 9-6" />
                        </svg>
                    </span>
                    <input
                        id="email"
                        class="h-12 w-full rounded-2xl border border-slate-200 bg-white/90 py-3 pl-12 pr-4 text-sm font-semibold text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 hover:border-emerald-200 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        autocomplete="email"
                        placeholder="nama@perusahaan.com"
                    >
                </label>
                <p class="flex items-start gap-2 text-xs leading-relaxed text-slate-500">
                    <svg class="mt-0.5 h-3.5 w-3.5 shrink-0 text-emerald-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M18 10A8 8 0 1 1 2 10a8 8 0 0 1 16 0ZM9 8a1 1 0 1 1 2 0v5a1 1 0 1 1-2 0V8Zm1-4a1.25 1.25 0 1 0 0 2.5A1.25 1.25 0 0 0 10 4Z" clip-rule="evenodd" />
                    </svg>
                    <span>Kode hanya dikirim bila email terdaftar. Periksa inbox atau folder spam jika belum terlihat.</span>
                </p>
                <x-input-error :messages="$errors->get('email')" class="rounded-2xl border border-rose-100 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-600" />
            </div>

            <div class="grid gap-3 sm:grid-cols-[0.9fr_1.4fr] sm:items-center">
                <a
                    href="{{ route('login') }}"
                    class="inline-flex h-12 items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-600 shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M19 12H5" />
                        <path d="m12 19-7-7 7-7" />
                    </svg>
                    {{ __('messages.back_to_login') }}
                </a>

                <button
                    type="submit"
                    class="group inline-flex h-12 items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-[#0B2F26] via-emerald-700 to-[#12824C] px-5 text-xs font-black uppercase tracking-[0.18em] text-white shadow-xl shadow-emerald-900/25 transition hover:-translate-y-0.5 hover:shadow-emerald-900/35 focus:outline-none focus:ring-4 focus:ring-emerald-200"
                >
                    <span>Kirim Kode Verifikasi</span>
                    <svg class="h-4 w-4 transition group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M5 12h14" />
                        <path d="m12 5 7 7-7 7" />
                    </svg>
                </button>
            </div>
        </form>
    </div>
</x-guest-layout>
