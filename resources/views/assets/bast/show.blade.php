@php
    $typeLabels = [
        'handover' => 'Handover',
        'return' => 'Return',
        'replacement' => 'Replacement',
        'loan' => 'Loan',
    ];
    $statusLabels = [
        'draft' => 'Draft',
        'issued' => 'Issued',
        'signed' => 'Signed',
        'void' => 'Void',
    ];
    $statusClasses = [
        'draft' => 'bg-slate-100 text-slate-700 ring-slate-200',
        'issued' => 'bg-sky-50 text-sky-700 ring-sky-200',
        'signed' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'void' => 'bg-rose-50 text-rose-700 ring-rose-200',
    ];
    $snapshot = $bast->asset_snapshot ?? [];
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
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">BAST Detail</p>
                    <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">{{ $bast->document_number }}</h1>
                    <p class="mt-1 text-sm text-slate-500">{{ optional($bast->bast_date)->format('d M Y') }} - {{ $typeLabels[$bast->bast_type] ?? $bast->bast_type }}</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('admin.assets.bast.index') }}" class="inline-flex h-10 items-center rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 shadow-sm hover:border-emerald-200 hover:text-emerald-700">
                        Back
                    </a>
                    <a href="{{ route('admin.assets.bast.print', $bast) }}" target="_blank" class="inline-flex h-10 items-center rounded-lg bg-slate-950 px-4 text-sm font-semibold text-white shadow-sm hover:bg-slate-800">
                        Print
                    </a>
                </div>
            </div>

            <div class="grid gap-5 lg:grid-cols-[1.4fr_1fr]">
                <div class="space-y-5">
                    <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Document</p>
                                <p class="mt-1 text-lg font-bold text-slate-950">{{ $bast->document_number }}</p>
                            </div>
                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold ring-1 {{ $statusClasses[$bast->status] ?? 'bg-slate-100 text-slate-700 ring-slate-200' }}">
                                {{ $statusLabels[$bast->status] ?? ucwords(str_replace('_', ' ', $bast->status)) }}
                            </span>
                        </div>
                        <dl class="mt-5 grid gap-4 sm:grid-cols-2">
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Type</dt>
                                <dd class="mt-1 text-sm font-semibold text-slate-900">{{ $typeLabels[$bast->bast_type] ?? ucwords(str_replace('_', ' ', $bast->bast_type)) }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Location</dt>
                                <dd class="mt-1 text-sm font-semibold text-slate-900">{{ $bast->handover_location ?: '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Created By</dt>
                                <dd class="mt-1 text-sm font-semibold text-slate-900">{{ $bast->creator?->name ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Signed At</dt>
                                <dd class="mt-1 text-sm font-semibold text-slate-900">{{ optional($bast->signed_at)->format('d M Y H:i') ?: '-' }}</dd>
                            </div>
                        </dl>
                    </section>

                    <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Asset Snapshot</p>
                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                            @foreach ([
                                'asset_code' => 'Asset Code',
                                'name' => 'Name',
                                'category' => 'Category',
                                'serial_number' => 'Serial Number',
                                'hostname' => 'Hostname',
                                'brand' => 'Brand',
                                'model' => 'Model',
                                'status' => 'Status',
                                'condition' => 'Condition',
                                'location' => 'Location',
                                'department' => 'Department',
                                'assigned_user' => 'Assigned User',
                            ] as $key => $label)
                                <div class="rounded-lg border border-slate-100 bg-slate-50 px-3 py-2">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">{{ $label }}</p>
                                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ $snapshot[$key] ?? '-' }}</p>
                                </div>
                            @endforeach
                        </div>
                    </section>
                </div>

                <div class="space-y-5">
                    <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Recipient</p>
                        <p class="mt-2 text-lg font-bold text-slate-950">{{ $bast->recipient_name }}</p>
                        <div class="mt-3 space-y-2 text-sm text-slate-600">
                            <p>{{ $bast->recipient_email ?: '-' }}</p>
                            <p>{{ $bast->recipient_department ?: $bast->department?->name ?: '-' }}</p>
                        </div>
                    </section>

                    <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Condition</p>
                        <p class="mt-2 text-sm font-semibold text-slate-900">{{ $bast->condition_summary ?: '-' }}</p>
                        <p class="mt-4 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Accessories</p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            @forelse (($bast->accessories ?? []) as $accessory)
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700 ring-1 ring-slate-200">{{ $accessory }}</span>
                            @empty
                                <span class="text-sm text-slate-500">No accessories recorded.</span>
                            @endforelse
                        </div>
                    </section>

                    <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Notes</p>
                        <p class="mt-2 whitespace-pre-line text-sm text-slate-700">{{ $bast->notes ?: '-' }}</p>
                    </section>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
