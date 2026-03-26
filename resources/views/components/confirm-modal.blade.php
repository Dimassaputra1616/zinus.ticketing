<div
    x-data="{
        isOpen: false,
        title: '',
        message: '',
        confirmText: '{{ __('messages.continue_button') ?? 'Lanjut' }}',
        cancelText: '{{ __('messages.cancel') ?? 'Batal' }}',
        onConfirm: null,
        onCancel: null,

        openModal(detail) {
            this.title = detail.title || '';
            this.message = detail.message || '';
            this.confirmText = detail.confirmText || '{{ __('messages.continue_button') ?? 'Lanjut' }}';
            this.cancelText = detail.cancelText || '{{ __('messages.cancel') ?? 'Batal' }}';
            this.onConfirm = detail.onConfirm || null;
            this.onCancel = detail.onCancel || null;
            this.isOpen = true;
            document.documentElement.style.overflow = 'hidden';
            document.body.style.overflow = 'hidden';
        },

        closeModal() {
            this.isOpen = false;
            document.documentElement.style.overflow = '';
            document.body.style.overflow = '';
            if (this.onCancel) {
                this.onCancel();
            }
        },

        confirmAction() {
            this.isOpen = false;
            document.documentElement.style.overflow = '';
            document.body.style.overflow = '';
            if (this.onConfirm) {
                this.onConfirm();
            }
        }
    }"
    @open-confirm-modal.window="openModal($event.detail)"
    x-show="isOpen"
    style="display: none;"
    class="relative z-[999999]"
    aria-labelledby="confirm-modal-title"
    role="dialog"
    aria-modal="true"
>
    <!-- Background backdrop -->
    <div
        x-show="isOpen"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0 backdrop-blur-none"
        x-transition:enter-end="opacity-100 backdrop-blur-md"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100 backdrop-blur-md"
        x-transition:leave-end="opacity-0 backdrop-blur-none"
        class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm transition-all"
    ></div>

    <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <!-- Modal panel -->
            <div
                x-show="isOpen"
                x-transition:enter="ease-out duration-300 transform"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95 sm:rotate-1"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100 sm:rotate-0"
                x-transition:leave="ease-in duration-200 transform"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100 sm:rotate-0"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95 sm:rotate-1"
                @click.away="closeModal()"
                class="relative transform overflow-hidden rounded-2xl bg-white/95 text-left shadow-[0_32px_80px_-12px_rgba(0,0,0,0.3)] backdrop-blur-xl transition-all sm:my-8 sm:w-full sm:max-w-md ring-1 ring-slate-200/50"
            >
                <!-- Header Gradient Accent -->
                <div class="absolute inset-x-0 top-0 h-2 bg-gradient-to-r from-amber-400 to-orange-500"></div>

                <div class="px-5 pb-5 pt-8 sm:p-7 sm:pb-5">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-full bg-amber-100 sm:mx-0 sm:h-12 sm:w-12 ring-8 ring-amber-50">
                            <svg class="h-6 w-6 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div class="mt-4 text-center sm:ml-5 sm:mt-0 sm:text-left w-full">
                            <h3 class="text-xl font-bold leading-6 text-slate-800 tracking-tight" id="confirm-modal-title" x-text="title"></h3>
                            <div class="mt-3">
                                <p class="text-[15px] leading-relaxed text-slate-500" x-text="message"></p>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Actions -->
                <div class="bg-slate-50/50 px-5 py-4 sm:flex sm:flex-row-reverse sm:px-7 border-t border-slate-100">
                    <button
                        type="button"
                        @click="confirmAction()"
                        class="btn-animate inline-flex w-full justify-center rounded-xl bg-amber-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm ring-1 ring-inset ring-amber-600 hover:bg-amber-500 sm:ml-3 sm:w-auto"
                        x-text="confirmText"
                    ></button>
                    <button
                        type="button"
                        @click="closeModal()"
                        class="btn-animate mt-3 inline-flex w-full justify-center rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 sm:mt-0 sm:w-auto"
                        x-text="cancelText"
                    ></button>
                </div>
            </div>
        </div>
    </div>
</div>
