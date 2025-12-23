<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Update Password') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('Ensure your account is using a long, random password to stay secure.') }}
        </p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('put')

        <div>
            <x-input-label for="update_password_current_password" :value="__('Current Password')" />
            <div class="relative" x-data="{ show: false }">
                <x-text-input id="update_password_current_password" name="current_password" :type="show ? 'text' : 'password'" class="mt-1 block w-full pr-12" autocomplete="current-password" />
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
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="update_password_password" :value="__('New Password')" />
            <div class="relative" x-data="{ show: false }">
                <x-text-input id="update_password_password" name="password" :type="show ? 'text' : 'password'" class="mt-1 block w-full pr-12" autocomplete="new-password" />
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
            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="update_password_password_confirmation" :value="__('Confirm Password')" />
            <div class="relative" x-data="{ show: false }">
                <x-text-input id="update_password_password_confirmation" name="password_confirmation" :type="show ? 'text' : 'password'" class="mt-1 block w-full pr-12" autocomplete="new-password" />
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
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'password-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
