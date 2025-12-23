@props([
    'title',
    'subtitle',
    'liveLabel' => 'Live',
    'showLive' => true,
])

<div {{ $attributes->merge(['class' => 'rounded-xl border border-[#CFEADF] px-5 py-4 surface-card space-y-4 bg-gradient-to-br from-[#F6F9F8] via-white to-[#EDF3F2] reveal-on-scroll transition hover:-translate-y-0.5 hover:shadow-md hover:shadow-emerald-900/10']) }}>
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="space-y-0.5 max-w-xl">
            <p class="heading-font text-[10px] font-semibold uppercase tracking-[0.36em] text-[#23455D]/70">{{ $title }}</p>
            <p class="text-sm text-gray-600">{{ $subtitle }}</p>
        </div>
        @if ($showLive)
            <span class="badge-live badge-chip inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-[10px] font-semibold uppercase tracking-[0.28em] text-[#1B4A37] border border-[#C5E5D0] bg-gradient-to-r from-[#E3F5EE] to-[#F7FFFB] shadow-inner shadow-white/60">
                {{ $liveLabel }}
                <span class="h-2 w-2 rounded-full bg-emerald-500 live-dot"></span>
            </span>
        @endif
    </div>

    {{ $slot }}
</div>
