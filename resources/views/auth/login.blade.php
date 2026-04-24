<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-6" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <div class="space-y-2">
            <label for="email" class="text-sm font-semibold text-[#0B2F26] tracking-wide">{{ __('messages.company_email') }}</label>
            <input
                id="email"
                type="email"
                name="email"
                value="{{ old('email') }}"
                required
                autofocus
                autocomplete="username"
                placeholder="{{ __('messages.email_placeholder') }}"
                class="w-full rounded-2xl border border-emerald-200 bg-white px-4 py-3 text-sm text-[#0B2F26] placeholder-emerald-400/80 shadow-sm focus:border-[var(--zinus-green)] focus:ring focus:ring-emerald-200/60 transition"
            >
            <x-input-error :messages="$errors->get('email')" class="text-xs text-rose-500" />
        </div>

        <div class="space-y-2">
            <label for="password" class="text-sm font-semibold text-[#0B2F26] tracking-wide">{{ __('messages.password') }}</label>
            <div class="relative" x-data="{ show: false }">
                <input
                    id="password"
                    type="password"
                    :type="show ? 'text' : 'password'"
                    name="password"
                    required
                    autocomplete="current-password"
                    placeholder="{{ __('messages.enter_password') }}"
                    class="w-full rounded-2xl border border-emerald-200 bg-white px-4 py-3 pr-12 text-sm text-[#0B2F26] placeholder-emerald-400/80 shadow-sm focus:border-[var(--zinus-green)] focus:ring focus:ring-emerald-200/60 transition"
                >
                <button
                    type="button"
                    class="absolute inset-y-0 right-3 flex items-center text-emerald-500/70 hover:text-emerald-700"
                    @click="show = !show"
                    :aria-pressed="show"
                    :title="show ? '{{ __('messages.hide_password') }}' : '{{ __('messages.show_password') }}'"
                >
                    <svg x-show="!show" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12Z" />
                        <circle cx="12" cy="12" r="3" />
                    </svg>
                    <svg x-show="show" x-cloak class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m3 3 18 18" />
                        <path d="M10.58 10.58a2 2 0 0 0 2.84 2.84" />
                        <path d="M9.88 4.24A10.82 10.82 0 0 1 12 4c7 0 11 8 11 8a16.8 16.8 0 0 1-3.64 4.8" />
                        <path d="M6.61 6.61A16.85 16.85 0 0 0 1 12s4 8 11 8a10.94 10.94 0 0 0 5.39-1.61" />
                    </svg>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password')" class="text-xs text-rose-500" />
        </div>

        <div class="flex items-center justify-between text-xs text-emerald-800/80">
            <label for="remember_me" class="inline-flex items-center gap-2">
                <input id="remember_me" type="checkbox" class="h-4 w-4 rounded border-emerald-300 text-[#0B2F26] focus:ring-emerald-300/60" name="remember">
                <span>{{ __('messages.remember_me') }}</span>
            </label>
            @if (Route::has('password.request'))
                <a class="font-semibold text-[#0B2F26] hover:text-emerald-600 transition" href="{{ route('password.request') }}">
                    {{ __('messages.forgot_password') }}
                </a>
            @endif
        </div>

        <button type="submit" class="w-full rounded-2xl py-3 text-sm font-semibold uppercase tracking-wide text-white shadow-lg shadow-emerald-900/30 transition transition hover:opacity-90" style="background: linear-gradient(135deg, var(--zinus-green), var(--zinus-green-strong))">
            {{ __('messages.login_btn') }}
        </button>

        @if (Route::has('register'))
            <p class="text-center text-xs text-emerald-700/80">
                {{ __('messages.no_account') }}
                <a href="{{ route('register') }}" class="font-semibold text-[#004F3B] hover:text-emerald-600">{{ __('messages.register_here') }}</a>
            </p>
        @endif
    </form>
</x-guest-layout>
