@php
    $typeLabels = [
        'routine' => 'Routine',
        'handover' => 'Handover',
        'return' => 'Return',
        'repair' => 'Repair',
    ];
    $conditionLabels = [
        'good' => 'Good',
        'minor_issue' => 'Minor Issue',
        'damaged' => 'Damaged',
        'repair' => 'Repair',
    ];
    $resultLabels = [
        'passed' => 'Passed',
        'needs_repair' => 'Needs Repair',
        'replace' => 'Replace',
        'retire' => 'Retire',
    ];
    $checklistStatus = [
        'ok' => 'OK',
        'issue' => 'Issue',
        'na' => 'N/A',
    ];
@endphp

<x-app-layout>
    <div class="min-h-screen bg-slate-50 py-6">
        <div class="mx-auto max-w-5xl space-y-5 px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Device Inspection</p>
                    <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Create Inspection</h1>
                    <p class="mt-1 text-sm text-slate-500">Catat checklist kondisi device dan keputusan lanjutannya.</p>
                </div>
                <a href="{{ route('admin.assets.inspections.index') }}" class="inline-flex h-10 items-center justify-center rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 shadow-sm hover:border-emerald-200 hover:text-emerald-700">
                    Back to Inspection
                </a>
            </div>

            @if ($errors->any())
                <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                    <p class="font-semibold">Cek lagi input inspection-nya bang.</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($selectedAsset)
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Selected Asset</p>
                    <p class="mt-2 text-base font-bold text-slate-950">{{ $selectedAsset->name }}</p>
                    <p class="text-sm text-slate-600">{{ $selectedAsset->asset_code ?: '-' }} - {{ $selectedAsset->serial_number ?: 'No serial' }}</p>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.assets.inspections.store') }}" enctype="multipart/form-data" class="space-y-5">
                @csrf
                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="block">
                            <span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Inspection Number</span>
                            <input
                                type="text"
                                name="inspection_number"
                                value="{{ old('inspection_number', $inspectionNumber) }}"
                                class="mt-1 h-10 w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                            >
                        </label>
                        <label class="block">
                            <span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Inspection Date</span>
                            <input
                                type="date"
                                name="inspection_date"
                                value="{{ old('inspection_date', now()->toDateString()) }}"
                                class="mt-1 h-10 w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                                required
                            >
                        </label>
                        <label class="block md:col-span-2">
                            <span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Asset</span>
                            <select name="asset_id" class="mt-1 h-10 w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500" required>
                                <option value="">Select asset</option>
                                @foreach ($assets as $asset)
                                    <option value="{{ $asset->id }}" @selected((int) old('asset_id', $selectedAsset?->id) === $asset->id)>
                                        {{ $asset->name }} - {{ $asset->asset_code ?: $asset->serial_number ?: 'No code' }}
                                    </option>
                                @endforeach
                            </select>
                        </label>
                        <label class="block">
                            <span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Type</span>
                            <select name="inspection_type" class="mt-1 h-10 w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500" required>
                                @foreach ($typeLabels as $key => $label)
                                    <option value="{{ $key }}" @selected(old('inspection_type', 'routine') === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="block">
                            <span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Overall Condition</span>
                            <select name="overall_condition" class="mt-1 h-10 w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500" required>
                                @foreach ($conditionLabels as $key => $label)
                                    <option value="{{ $key }}" @selected(old('overall_condition', 'good') === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="block">
                            <span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Result</span>
                            <select name="result" class="mt-1 h-10 w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500" required>
                                @foreach ($resultLabels as $key => $label)
                                    <option value="{{ $key }}" @selected(old('result', 'passed') === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="block">
                            <span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Next Inspection</span>
                            <input
                                type="date"
                                name="next_inspection_date"
                                value="{{ old('next_inspection_date') }}"
                                class="mt-1 h-10 w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                            >
                        </label>
                    </div>
                </div>

                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Checklist</p>
                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                        @foreach ($checklistItems as $key => $label)
                            <label class="grid gap-2 rounded-lg border border-slate-200 p-3">
                                <span class="text-sm font-semibold text-slate-900">{{ $label }}</span>
                                <select name="checklist[{{ $key }}]" class="h-10 rounded-lg border-slate-200 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                    @foreach ($checklistStatus as $statusKey => $statusLabel)
                                        <option value="{{ $statusKey }}" @selected(old("checklist.$key", 'ok') === $statusKey)>{{ $statusLabel }}</option>
                                    @endforeach
                                </select>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="grid gap-4">
                        <label class="block">
                            <span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Findings</span>
                            <textarea
                                name="findings"
                                rows="4"
                                class="mt-1 w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                                placeholder="Temuan saat pengecekan."
                            >{{ old('findings') }}</textarea>
                        </label>
                        <label class="block">
                            <span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Action Required</span>
                            <textarea
                                name="action_required"
                                rows="4"
                                class="mt-1 w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                                placeholder="Perlu repair, replace part, monitor berkala, atau tidak ada tindakan."
                            >{{ old('action_required') }}</textarea>
                        </label>
                        <label class="block">
                            <span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Foto Inspection</span>
                            <input
                                type="file"
                                name="photos[]"
                                accept="image/jpeg,image/png,image/webp"
                                multiple
                                class="mt-1 block w-full rounded-lg border border-slate-200 bg-white text-sm text-slate-600 shadow-sm file:mr-4 file:border-0 file:bg-slate-950 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-white hover:file:bg-slate-800"
                            >
                            <p class="mt-2 text-xs text-slate-500">Upload maksimal 6 foto hasil pengecekan. Format JPG, PNG, atau WEBP, maksimal 5 MB per foto.</p>
                        </label>
                    </div>
                </div>

                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <a href="{{ route('admin.assets.inspections.index') }}" class="inline-flex h-10 items-center justify-center rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 shadow-sm hover:border-emerald-200 hover:text-emerald-700">
                        Cancel
                    </a>
                    <button type="submit" class="inline-flex h-10 items-center justify-center rounded-lg bg-emerald-600 px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700">
                        Save Inspection
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
