<x-guest-layout>
    <div class="text-center">
        <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-3xl bg-amber-50 text-amber-600 shadow-inner shadow-white">
            <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 8v5" />
                <path d="M12 17h.01" />
                <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z" />
            </svg>
        </div>

        <p class="text-[11px] font-bold uppercase tracking-[0.34em] text-emerald-600">
            {{ __('messages.registration_pending_eyebrow') }}
        </p>
        <h1 class="mt-3 text-2xl font-black tracking-tight text-[#0B2F26]">
            {{ __('messages.registration_pending_title') }}
        </h1>
        <p class="mt-3 text-sm leading-6 text-slate-600">
            {{ __('messages.registration_pending_body') }}
        </p>

        @if (session('registered_email'))
            <div class="mt-5 rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
                {{ session('registered_email') }}
            </div>
        @endif

        <div class="mt-7 flex flex-col gap-3 sm:flex-row sm:justify-center">
            <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-2xl bg-[#101827] px-5 py-3 text-sm font-bold text-white shadow-lg shadow-slate-900/20 transition hover:-translate-y-0.5 hover:bg-[#172033]">
                {{ __('messages.back_to_login') }}
            </a>
            <a href="{{ route('password.request') }}" class="inline-flex items-center justify-center rounded-2xl border border-emerald-200 bg-white px-5 py-3 text-sm font-bold text-emerald-700 shadow-sm transition hover:border-emerald-300 hover:bg-emerald-50">
                {{ __('messages.forgot_password') }}
            </a>
        </div>
    </div>
</x-guest-layout>
