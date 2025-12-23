<section class="space-y-6">
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Delete Account') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.') }}
        </p>
    </header>

    <x-danger-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >{{ __('Delete Account') }}</x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
            @csrf
            @method('delete')

            <h2 class="text-lg font-medium text-gray-900">
                {{ __('Are you sure you want to delete your account?') }}
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
            </p>

            <div class="mt-6">
                <x-input-label for="password" value="{{ __('Password') }}" class="sr-only" />

                <div class="relative w-3/4" x-data="{ show: false }">
                    <x-text-input
                        id="password"
                        name="password"
                        :type="show ? 'text' : 'password'"
                        class="mt-1 block w-full pr-12"
                        placeholder="{{ __('Password') }}"
                    />
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

                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    {{ __('Cancel') }}
                </x-secondary-button>

                <x-danger-button class="ms-3">
                    {{ __('Delete Account') }}
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
