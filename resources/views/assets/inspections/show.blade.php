@php
    $typeLabels = [
        'routine' => 'Routine',
        'handover' => 'Handover',
        'return' => 'Return',
        'repair' => 'Repair',
    ];
    $resultLabels = [
        'passed' => 'Passed',
        'needs_repair' => 'Needs Repair',
        'replace' => 'Replace',
        'retire' => 'Retire',
    ];
    $resultClasses = [
        'passed' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'needs_repair' => 'bg-amber-50 text-amber-700 ring-amber-200',
        'replace' => 'bg-orange-50 text-orange-700 ring-orange-200',
        'retire' => 'bg-rose-50 text-rose-700 ring-rose-200',
    ];
    $checklistClasses = [
        'ok' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'issue' => 'bg-amber-50 text-amber-700 ring-amber-200',
        'na' => 'bg-slate-100 text-slate-600 ring-slate-200',
    ];
    $checklistLabels = [
        'ok' => 'OK',
        'issue' => 'Issue',
        'na' => 'N/A',
    ];
    $photos = $inspection->photos ?? [];
@endphp

<x-app-layout>
    <div class="min-h-screen bg-slate-50 py-6">
        <div class="mx-auto max-w-6xl space-y-5 px-4 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
                    {{ session('success') }}
                </div>
            @endif

            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Inspection Detail</p>
                    <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">{{ $inspection->inspection_number }}</h1>
                    <p class="mt-1 text-sm text-slate-500">{{ optional($inspection->inspection_date)->format('d M Y') }} - {{ $typeLabels[$inspection->inspection_type] ?? $inspection->inspection_type }}</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('admin.assets.inspections.index') }}" class="inline-flex h-10 items-center rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 shadow-sm hover:border-emerald-200 hover:text-emerald-700">
                        Back
                    </a>
                    <a href="{{ route('admin.assets.inspections.print', $inspection) }}" target="_blank" class="inline-flex h-10 items-center rounded-lg bg-slate-950 px-4 text-sm font-semibold text-white shadow-sm hover:bg-slate-800">
                        Print
                    </a>
                </div>
            </div>

            <div class="grid gap-5 lg:grid-cols-[1fr_1.2fr]">
                <div class="space-y-5">
                    <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Result</p>
                                <p class="mt-1 text-lg font-bold text-slate-950">{{ $resultLabels[$inspection->result] ?? ucwords(str_replace('_', ' ', $inspection->result)) }}</p>
                            </div>
                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold ring-1 {{ $resultClasses[$inspection->result] ?? 'bg-slate-100 text-slate-700 ring-slate-200' }}">
                                {{ ucwords(str_replace('_', ' ', $inspection->overall_condition)) }}
                            </span>
                        </div>
                        <dl class="mt-5 grid gap-4 sm:grid-cols-2">
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Type</dt>
                                <dd class="mt-1 text-sm font-semibold text-slate-900">{{ $typeLabels[$inspection->inspection_type] ?? ucwords(str_replace('_', ' ', $inspection->inspection_type)) }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Inspector</dt>
                                <dd class="mt-1 text-sm font-semibold text-slate-900">{{ $inspection->inspector?->name ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Inspection Date</dt>
                                <dd class="mt-1 text-sm font-semibold text-slate-900">{{ optional($inspection->inspection_date)->format('d M Y') }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Next Inspection</dt>
                                <dd class="mt-1 text-sm font-semibold text-slate-900">{{ optional($inspection->next_inspection_date)->format('d M Y') ?: '-' }}</dd>
                            </div>
                        </dl>
                    </section>

                    <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Asset</p>
                        <p class="mt-2 text-lg font-bold text-slate-950">{{ $inspection->asset?->name ?? '-' }}</p>
                        <div class="mt-3 grid gap-3 sm:grid-cols-2">
                            @foreach ([
                                'Asset Code' => $inspection->asset?->asset_code,
                                'Serial Number' => $inspection->asset?->serial_number,
                                'Category' => $inspection->asset?->category,
                                'Department' => $inspection->asset?->department?->name,
                                'Assigned User' => $inspection->asset?->user?->name,
                                'Location' => $inspection->asset?->location,
                            ] as $label => $value)
                                <div class="rounded-lg border border-slate-100 bg-slate-50 px-3 py-2">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">{{ $label }}</p>
                                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ $value ?: '-' }}</p>
                                </div>
                            @endforeach
                        </div>
                    </section>
                </div>

                <div class="space-y-5">
                    <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Checklist</p>
                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                            @foreach ($checklistItems as $key => $label)
                                @php $value = $inspection->checklist[$key] ?? 'na'; @endphp
                                <div class="rounded-lg border border-slate-200 p-3">
                                    <p class="text-sm font-semibold text-slate-900">{{ $label }}</p>
                                    <span class="mt-2 inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $checklistClasses[$value] ?? $checklistClasses['na'] }}">
                                        {{ $checklistLabels[$value] ?? strtoupper($value) }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </section>

                    <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Findings</p>
                        <p class="mt-2 whitespace-pre-line text-sm text-slate-700">{{ $inspection->findings ?: '-' }}</p>
                        <p class="mt-5 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Action Required</p>
                        <p class="mt-2 whitespace-pre-line text-sm text-slate-700">{{ $inspection->action_required ?: '-' }}</p>
                    </section>

                    @if (count($photos))
                        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Foto Inspection</p>
                            <div class="mt-3 grid grid-cols-2 gap-3">
                                @foreach ($photos as $photo)
                                    @php $photoUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($photo['path']); @endphp
                                    <a href="{{ $photoUrl }}" target="_blank" class="group block overflow-hidden rounded-lg border border-slate-200 bg-slate-50">
                                        <img src="{{ $photoUrl }}" alt="{{ $photo['original_name'] ?? 'Foto inspection' }}" class="aspect-[4/3] w-full object-cover transition group-hover:scale-105">
                                        <div class="truncate px-2 py-1.5 text-xs font-semibold text-slate-600">{{ $photo['original_name'] ?? 'Foto inspection' }}</div>
                                    </a>
                                @endforeach
                            </div>
                        </section>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
