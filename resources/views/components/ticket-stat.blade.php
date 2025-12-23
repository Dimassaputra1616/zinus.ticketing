@props([
    'title',
    'value',
    'subtitle',
    'badgeText',
    'badgeBg',
    'badgeColor',
    'iconBg',
    'icon',
])

<div class="h-40 rounded-[10px] border border-slate-200 bg-white px-5 py-5 shadow-[0_1px_2px_rgba(0,0,0,0.05)] transition duration-150 flex flex-col select-text hover:shadow-[0_10px_24px_rgba(15,23,42,0.08)] hover:scale-[1.01]">
    <div class="flex items-center gap-4">
        <div class="flex h-10 w-10 items-center justify-center rounded-md border border-slate-200 bg-white flex-shrink-0 [&>svg]:h-5 [&>svg]:w-5">
            {!! $icon !!}
        </div>
        <div class="space-y-1">
            <p class="text-[0.65rem] font-semibold uppercase tracking-[0.24em] text-slate-500">{{ $title }}</p>
            <p class="text-3xl font-bold text-slate-900 leading-tight">
                <span class="counter" data-counter="{{ $value }}">{{ $value }}</span>
            </p>
            <p class="text-sm text-slate-500">{{ $subtitle }}</p>
        </div>
    </div>

    <span
        class="mt-auto inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-[0.6rem] font-semibold uppercase tracking-[0.18em] text-slate-600"
    >
        {{ $badgeText }}
    </span>
</div>
