@php
    $typeLabels = [
        'handover' => 'Handover',
        'return' => 'Return',
        'replacement' => 'Replacement',
        'loan' => 'Loan',
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
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Asset Reports</p>
                    <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">BAST & Inspection Report</h1>
                    <p class="mt-1 text-sm text-slate-500">
                        Periode {{ $startDate->format('d M Y') }} sampai {{ $endDate->format('d M Y') }}.
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('admin.assets.bast.create') }}" class="inline-flex h-10 items-center rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 shadow-sm hover:border-emerald-200 hover:text-emerald-700">
                        New BAST
                    </a>
                    <a href="{{ route('admin.assets.inspections.create') }}" class="inline-flex h-10 items-center rounded-lg bg-emerald-600 px-4 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700">
                        New Inspection
                    </a>
                </div>
            </div>

            <form method="GET" action="{{ route('admin.assets.reports.index') }}" class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                <div class="grid gap-3 sm:grid-cols-[200px_200px_auto] sm:items-end">
                    <label class="block">
                        <span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Start Date</span>
                        <input
                            type="date"
                            name="start_date"
                            value="{{ $startDate->toDateString() }}"
                            class="mt-1 h-10 w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                        >
                    </label>
                    <label class="block">
                        <span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">End Date</span>
                        <input
                            type="date"
                            name="end_date"
                            value="{{ $endDate->toDateString() }}"
                            class="mt-1 h-10 w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                        >
                    </label>
                    <button type="submit" class="inline-flex h-10 items-center justify-center rounded-lg bg-slate-950 px-4 text-sm font-semibold text-white transition hover:bg-slate-800">
                        Apply
                    </button>
                </div>
            </form>

            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ([
                    ['label' => 'BAST Total', 'value' => $stats['bast_total']],
                    ['label' => 'BAST Signed', 'value' => $stats['bast_signed']],
                    ['label' => 'Inspection Total', 'value' => $stats['inspection_total']],
                    ['label' => 'Inspection Issues', 'value' => $stats['inspection_issue']],
                ] as $item)
                    <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">{{ $item['label'] }}</p>
                        <p class="mt-2 text-2xl font-bold text-slate-950">{{ number_format($item['value']) }}</p>
                    </div>
                @endforeach
            </div>

            <div class="grid gap-5 lg:grid-cols-2">
                <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">BAST By Type</p>
                    <div class="mt-4 space-y-3">
                        @foreach ($typeLabels as $key => $label)
                            @php
                                $value = (int) ($bastByType[$key] ?? 0);
                                $percentage = $stats['bast_total'] > 0 ? min(100, round(($value / $stats['bast_total']) * 100)) : 0;
                            @endphp
                            <div>
                                <div class="flex items-center justify-between text-sm">
                                    <span class="font-semibold text-slate-700">{{ $label }}</span>
                                    <span class="text-slate-500">{{ $value }}</span>
                                </div>
                                <div class="mt-2 h-2 rounded-full bg-slate-100">
                                    <div class="h-2 rounded-full bg-emerald-500" style="width: {{ $percentage }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>

                <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Inspection By Result</p>
                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        @foreach ($resultLabels as $key => $label)
                            <div class="rounded-lg border border-slate-200 p-4">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $resultClasses[$key] }}">
                                    {{ $label }}
                                </span>
                                <p class="mt-3 text-2xl font-bold text-slate-950">{{ (int) ($inspectionByResult[$key] ?? 0) }}</p>
                            </div>
                        @endforeach
                    </div>
                </section>
            </div>

            <div class="grid gap-5 xl:grid-cols-2">
                <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                    <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Recent BAST</p>
                            <p class="text-sm text-slate-500">Dokumen terakhir di periode ini.</p>
                        </div>
                        <a href="{{ route('admin.assets.bast.index') }}" class="text-sm font-semibold text-emerald-700 hover:text-emerald-800">View all</a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">
                                <tr>
                                    <th class="px-4 py-3 text-left">Document</th>
                                    <th class="px-4 py-3 text-left">Asset</th>
                                    <th class="px-4 py-3 text-left">Recipient</th>
                                    <th class="px-4 py-3 text-right">Print</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($basts as $bast)
                                    <tr>
                                        <td class="px-4 py-3">
                                            <a href="{{ route('admin.assets.bast.show', $bast) }}" class="font-semibold text-slate-950 hover:text-emerald-700">{{ $bast->document_number }}</a>
                                            <div class="text-xs text-slate-500">{{ optional($bast->bast_date)->format('d M Y') }}</div>
                                        </td>
                                        <td class="px-4 py-3 text-slate-700">{{ $bast->asset?->name ?? '-' }}</td>
                                        <td class="px-4 py-3 text-slate-700">{{ $bast->recipient_name }}</td>
                                        <td class="px-4 py-3 text-right">
                                            <a href="{{ route('admin.assets.bast.print', $bast) }}" target="_blank" class="text-sm font-semibold text-emerald-700 hover:text-emerald-800">Print</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-4 py-8 text-center text-sm text-slate-500">Tidak ada BAST di periode ini.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                    <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Recent Inspection</p>
                            <p class="text-sm text-slate-500">Hasil pengecekan terakhir di periode ini.</p>
                        </div>
                        <a href="{{ route('admin.assets.inspections.index') }}" class="text-sm font-semibold text-emerald-700 hover:text-emerald-800">View all</a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">
                                <tr>
                                    <th class="px-4 py-3 text-left">Inspection</th>
                                    <th class="px-4 py-3 text-left">Asset</th>
                                    <th class="px-4 py-3 text-left">Result</th>
                                    <th class="px-4 py-3 text-right">Print</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($inspections as $inspection)
                                    <tr>
                                        <td class="px-4 py-3">
                                            <a href="{{ route('admin.assets.inspections.show', $inspection) }}" class="font-semibold text-slate-950 hover:text-emerald-700">{{ $inspection->inspection_number }}</a>
                                            <div class="text-xs text-slate-500">{{ optional($inspection->inspection_date)->format('d M Y') }}</div>
                                        </td>
                                        <td class="px-4 py-3 text-slate-700">{{ $inspection->asset?->name ?? '-' }}</td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $resultClasses[$inspection->result] ?? 'bg-slate-100 text-slate-700 ring-slate-200' }}">
                                                {{ $resultLabels[$inspection->result] ?? ucwords(str_replace('_', ' ', $inspection->result)) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <a href="{{ route('admin.assets.inspections.print', $inspection) }}" target="_blank" class="text-sm font-semibold text-emerald-700 hover:text-emerald-800">Print</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-4 py-8 text-center text-sm text-slate-500">Tidak ada inspection di periode ini.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
