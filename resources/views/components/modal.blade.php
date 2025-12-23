@props([
    'name',
    'show' => false,
])

<div
    x-data="{
        show: @js($show),
        lockScroll() {
            document.documentElement.style.overflow = 'hidden';
            document.body.style.overflow = 'hidden';
        },
        unlockScroll() {
            document.documentElement.style.overflow = '';
            document.body.style.overflow = '';
        },
        resetScroll() {
            [this.$refs.dialogBody, this.$refs.dialog].forEach((el) => {
                if (el && typeof el.scrollTop === 'number') {
                    el.scrollTop = 0;
                }
            });
            window.scrollTo({ top: 0, behavior: 'auto' });
        },
        focusables() {
            let selector = 'a, button, input:not([type=\'hidden\']), textarea, select, details, [tabindex]:not([tabindex=\'-1\'])'
            return [...$el.querySelectorAll(selector)].filter(el => ! el.hasAttribute('disabled'))
        },
        firstFocusable() { return this.focusables()[0] },
        lastFocusable() { return this.focusables().slice(-1)[0] },
        nextFocusable() { return this.focusables()[this.nextFocusableIndex()] || this.firstFocusable() },
        prevFocusable() { return this.focusables()[this.prevFocusableIndex()] || this.lastFocusable() },
        nextFocusableIndex() { return (this.focusables().indexOf(document.activeElement) + 1) % (this.focusables().length + 1) },
        prevFocusableIndex() { return Math.max(0, this.focusables().indexOf(document.activeElement)) -1 },
    }"
    x-init="$watch('show', value => {
        if (value) {
            lockScroll();
            {{ $attributes->has('focusable') ? 'setTimeout(() => firstFocusable().focus(), 100)' : '' }}
        } else {
            unlockScroll();
        }
    })"
    x-on:open-modal.window="
        if ($event.detail == '{{ $name }}') {
            show = true;
            $nextTick(() => resetScroll());
        }
    "
    x-on:close-modal.window="$event.detail == '{{ $name }}' ? show = false : null"
    x-on:close.stop="$dispatch('close-modal', '{{ $name }}')"
    x-on:keydown.escape.window="$dispatch('close-modal', '{{ $name }}')"
    x-on:keydown.tab.prevent="$event.shiftKey || nextFocusable().focus()"
    x-on:keydown.shift.tab.prevent="prevFocusable().focus()"
    x-trap.noscroll.inert="show"
    x-show="show"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/30 backdrop-blur px-3 sm:px-4 py-8 sm:py-10"
    style="display: {{ $show ? 'block' : 'none' }};"
    role="dialog"
    aria-modal="true"
    aria-label="{{ $name }}"
>
    <div
        x-show="show"
        class="absolute inset-0"
        x-on:click="$dispatch('close-modal', '{{ $name }}')"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    ></div>

    <div
        x-show="show"
        x-ref="dialog"
        class="w-full max-w-3xl max-h-[calc(100vh-160px)] bg-white rounded-2xl shadow-xl overflow-hidden flex flex-col min-h-0 focus:outline-none pointer-events-auto"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        tabindex="0"
    >
        <div
            x-ref="dialogBody"
            data-modal-body="{{ $name }}"
            class="flex-1 overflow-y-auto flex flex-col"
        >
            @isset($header)
                <div class="sticky top-0 z-10 bg-white px-6 pt-6 pb-4">
                    {{ $header }}
                </div>
            @endisset

            <div class="px-6 pb-6">
                {{ $slot }}
            </div>

            @isset($footer)
                <div class="sticky bottom-0 border-t border-slate-100 bg-white p-4 flex justify-end">
                    {{ $footer }}
                </div>
            @endisset
        </div>
    </div>
</div>
