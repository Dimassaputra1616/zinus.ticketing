@props([
    'pill',
    'title',
    'description' => null,
])

<header {{ $attributes->merge(['class' => 'space-y-2 mt-0']) }}>
    <span class="heading-font inline-flex items-center gap-2 rounded-full bg-[#EDF3F2] px-5 py-1.5 text-sm font-semibold uppercase tracking-[0.3em] text-[#23455D] shadow-sm">
        @isset($pillIcon)
            {{ $pillIcon }}
        @endisset
        {{ $pill }}
    </span>
    <div class="space-y-1.5">
        <p class="heading-font text-2xl font-semibold text-[#0C1F2C] tracking-tight flex items-center gap-2.5">
            @isset($icon)
                <span class="shrink-0">
                    {{ $icon }}
                </span>
            @endisset
            {{ $title }}
        </p>
        @if ($description)
            <p class="text-sm max-w-2xl text-[#1f5948]" style="background-image: linear-gradient(90deg, #23455D 0%, #12824C 45%, #53B77A 100%); -webkit-background-clip: text; color: transparent;">
                {{ $description }}
            </p>
        @endif
    </div>
</header>
