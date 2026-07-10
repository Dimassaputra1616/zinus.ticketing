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
@endphp

<x-app-layout>
    <div class="min-h-screen bg-slate-50 py-6">
        <div class="mx-auto max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
                    {{ session('success') }}
                </div>
            @endif

            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Device Inspection</p>
                    <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Inspection Device</h1>
                    <p class="mt-1 text-sm text-slate-500">Checklist kondisi device, hasil inspeksi, dan follow-up per asset.</p>
                </div>
                <a href="{{ route('admin.assets.inspections.create', ['return_to' => request()->fullUrl()]) }}" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 5v14" />
                        <path d="M5 12h14" />
                    </svg>
                    New Inspection
                </a>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ([
                    ['label' => 'Total Inspection', 'value' => $stats['total']],
                    ['label' => 'Passed', 'value' => $stats['passed']],
                    ['label' => 'Need Repair', 'value' => $stats['needs_repair']],
                    ['label' => 'This Month', 'value' => $stats['this_month']],
                ] as $item)
                    <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">{{ $item['label'] }}</p>
                        <p class="mt-2 text-2xl font-bold text-slate-950">{{ number_format($item['value']) }}</p>
                    </div>
                @endforeach
            </div>

            <form method="GET" action="{{ route('admin.assets.inspections.index') }}" class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                <div class="grid gap-3 lg:grid-cols-[1fr_180px_180px_auto] lg:items-end">
                    <label class="block">
                        <span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Search</span>
                        <input
                            type="search"
                            name="search"
                            value="{{ $search }}"
                            placeholder="Inspection, asset, finding..."
                            class="mt-1 h-10 w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                        >
                    </label>
                    <label class="block">
                        <span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Type</span>
                        <select name="type" class="mt-1 h-10 w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                            <option value="">All type</option>
                            @foreach ($typeLabels as $key => $label)
                                <option value="{{ $key }}" @selected($type === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block">
                        <span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Result</span>
                        <select name="result" class="mt-1 h-10 w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                            <option value="">All result</option>
                            @foreach ($resultLabels as $key => $label)
                                <option value="{{ $key }}" @selected($result === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <button type="submit" class="inline-flex h-10 items-center justify-center rounded-lg bg-slate-950 px-4 text-sm font-semibold text-white transition hover:bg-slate-800">
                        Filter
                    </button>
                </div>
            </form>

            <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                            <tr>
                                <th class="px-4 py-3 text-left">Inspection</th>
                                <th class="px-4 py-3 text-left">Asset</th>
                                <th class="px-4 py-3 text-left">Type</th>
                                <th class="px-4 py-3 text-left">Condition</th>
                                <th class="px-4 py-3 text-left">Result</th>
                                <th class="px-4 py-3 text-left">Date</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($inspections as $inspection)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-3">
                                        <a href="{{ route('admin.assets.inspections.show', $inspection) }}" class="font-semibold text-slate-950 hover:text-emerald-700">
                                            {{ $inspection->inspection_number }}
                                        </a>
                                        <div class="text-xs text-slate-500">{{ $inspection->inspector?->name ?? '-' }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="font-semibold text-slate-900">{{ $inspection->asset?->name ?? '-' }}</div>
                                        <div class="text-xs text-slate-500">{{ $inspection->asset?->asset_code ?? $inspection->asset?->serial_number ?? '-' }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-slate-700">{{ $typeLabels[$inspection->inspection_type] ?? ucwords(str_replace('_', ' ', $inspection->inspection_type)) }}</td>
                                    <td class="px-4 py-3 text-slate-700">{{ ucwords(str_replace('_', ' ', $inspection->overall_condition)) }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $resultClasses[$inspection->result] ?? 'bg-slate-100 text-slate-700 ring-slate-200' }}">
                                            {{ $resultLabels[$inspection->result] ?? ucwords(str_replace('_', ' ', $inspection->result)) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-slate-700">{{ optional($inspection->inspection_date)->format('d M Y') }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex justify-end gap-2">
                                            <a href="{{ route('admin.assets.inspections.print', $inspection) }}" target="_blank" class="inline-flex h-9 items-center rounded-lg border border-slate-200 px-3 text-xs font-semibold text-slate-700 hover:border-emerald-200 hover:text-emerald-700">
                                                Print
                                            </a>
                                            <a href="{{ route('admin.assets.inspections.show', $inspection) }}" class="inline-flex h-9 items-center rounded-lg bg-slate-950 px-3 text-xs font-semibold text-white hover:bg-slate-800">
                                                Detail
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-10 text-center text-sm text-slate-500">Belum ada inspection device.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($inspections->count())
                    <div class="border-t border-slate-200 px-4 py-3">
                        {{ $inspections->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
