<x-guest-layout>
    <x-slot name="cardTop">
        <a
            href="{{ route('password.request') }}"
            class="inline-flex items-center gap-2 text-xs font-bold text-slate-600 transition hover:text-emerald-700 sm:text-sm"
        >
            <span aria-hidden="true">&lt;</span>
            <span>Back to email</span>
        </a>
    </x-slot>
    <x-slot name="compactCard">true</x-slot>

    <div class="space-y-5 sm:space-y-7">
        <div class="rounded-2xl border border-emerald-100 bg-emerald-50 p-4 shadow-sm sm:rounded-3xl sm:p-5">
            <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-emerald-700 sm:tracking-[0.22em]">Verifikasi reset</p>
            <h3 class="mt-2 text-lg font-black tracking-tight text-slate-950 sm:text-xl">Masukkan kode email</h3>
            <p class="mt-1 text-sm leading-relaxed text-slate-600 sm:mt-1.5">
                Kode 6 digit dikirim ke email yang Anda masukkan. Kode berlaku 10 menit dan maksimal salah 5 kali.
            </p>
        </div>

        <x-auth-session-status
            class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-700 shadow-sm"
            :status="session('status')"
        />

        <form method="POST" action="{{ route('password.code.verify') }}" class="space-y-4 sm:space-y-5">
            @csrf

            <div class="space-y-2">
                <x-input-label for="email" value="Email" />
                <input
                    id="email"
                    class="h-12 w-full rounded-2xl border border-slate-200 bg-white/90 px-4 text-sm font-semibold text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 hover:border-emerald-200 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100"
                    type="email"
                    name="email"
                    value="{{ $email }}"
                    required
                    autocomplete="email"
                >
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <div class="space-y-2">
                <x-input-label for="code" value="Kode Verifikasi" />
                <input
                    id="code"
                    class="h-12 w-full rounded-2xl border border-slate-200 bg-white/90 px-4 text-center text-xl font-black tracking-[0.28em] text-slate-950 shadow-sm outline-none transition placeholder:text-slate-300 hover:border-emerald-200 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100 sm:h-14 sm:text-2xl sm:tracking-[0.32em]"
                    type="text"
                    inputmode="numeric"
                    pattern="[0-9]{6}"
                    maxlength="6"
                    name="code"
                    value="{{ old('code') }}"
                    required
                    autofocus
                    placeholder="000000"
                >
                <x-input-error :messages="$errors->get('code')" class="mt-2" />
            </div>

            <div>
                <button
                    type="submit"
                    class="inline-flex h-12 w-full items-center justify-center rounded-2xl bg-gradient-to-r from-[#0B2F26] via-emerald-700 to-[#12824C] px-5 text-xs font-black uppercase tracking-[0.18em] text-white shadow-xl shadow-emerald-900/25 transition hover:-translate-y-0.5 hover:shadow-emerald-900/35 focus:outline-none focus:ring-4 focus:ring-emerald-200"
                >
                    Verifikasi Kode
                </button>
            </div>
        </form>
    </div>
</x-guest-layout>
