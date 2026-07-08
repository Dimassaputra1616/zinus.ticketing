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
    $accessoryOptions = ['Charger', 'Power Cable', 'Adapter', 'Mouse', 'Keyboard', 'Bag', 'Docking', 'Manual'];
    $conditionOptions = \App\Models\AssetBast::CONDITION_SUMMARY_OPTIONS;
    $selectedConditionSummary = old('condition_summary', $conditionOptions[$selectedAsset?->condition] ?? null);
    $selectedAccessories = (array) old('accessories', []);
    $prefillUser = $selectedLoan?->user ?? $selectedAsset?->user;
    $prefillType = $selectedLoan
        ? ($selectedLoan->status === \App\Models\BorrowLog::STATUS_RETURNED ? 'return' : 'loan')
        : 'handover';
@endphp

<x-app-layout>
    <div class="min-h-screen bg-slate-50 py-6">
        <div class="mx-auto max-w-5xl space-y-5 px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Asset Documentation</p>
                    <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Create BAST</h1>
                    <p class="mt-1 text-sm text-slate-500">Buat dokumen serah terima asset dengan snapshot kondisi saat ini.</p>
                </div>
                <a href="{{ route('admin.assets.bast.index') }}" class="inline-flex h-10 items-center justify-center rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 shadow-sm hover:border-emerald-200 hover:text-emerald-700">
                    Back to BAST
                </a>
            </div>

            @if ($errors->any())
                <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                    <p class="font-semibold">Cek lagi input BAST-nya bang.</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($selectedAsset || $selectedLoan)
                <div class="grid gap-3 lg:grid-cols-2">
                    @if ($selectedAsset)
                        <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Selected Asset</p>
                            <p class="mt-2 text-base font-bold text-slate-950">{{ $selectedAsset->name }}</p>
                            <p class="text-sm text-slate-600">{{ $selectedAsset->asset_code ?: '-' }} - {{ $selectedAsset->serial_number ?: 'No serial' }}</p>
                        </div>
                    @endif
                    @if ($selectedLoan)
                        <div class="rounded-lg border border-sky-200 bg-sky-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-sky-700">From Loan Request</p>
                            <p class="mt-2 text-base font-bold text-slate-950">{{ $selectedLoan->user?->name ?? 'User' }}</p>
                            <p class="text-sm text-slate-600">{{ optional($selectedLoan->start_date)->format('d M Y') }} - {{ optional($selectedLoan->end_date)->format('d M Y') }}</p>
                        </div>
                    @endif
                </div>
            @endif

            <form method="POST" action="{{ route('admin.assets.bast.store') }}" enctype="multipart/form-data" class="space-y-5">
                @csrf
                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="block">
                            <span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Document Number</span>
                            <input
                                type="text"
                                name="document_number"
                                value="{{ old('document_number', $documentNumber) }}"
                                class="mt-1 h-10 w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                            >
                        </label>
                        <label class="block">
                            <span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">BAST Date</span>
                            <input
                                type="date"
                                name="bast_date"
                                value="{{ old('bast_date', now()->toDateString()) }}"
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
                            <span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Linked Loan</span>
                            <select name="borrow_log_id" class="mt-1 h-10 w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                <option value="">No loan link</option>
                                @foreach ($loans as $loan)
                                    <option value="{{ $loan->id }}" @selected((int) old('borrow_log_id', $selectedLoan?->id) === $loan->id)>
                                        #{{ $loan->id }} - {{ $loan->user?->name ?? 'User' }} - {{ $loan->asset?->name ?? 'Asset' }}
                                    </option>
                                @endforeach
                            </select>
                        </label>
                        <label class="block">
                            <span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Type</span>
                            <select name="bast_type" class="mt-1 h-10 w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500" required>
                                @foreach ($typeLabels as $key => $label)
                                    <option value="{{ $key }}" @selected(old('bast_type', $prefillType) === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="block">
                            <span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Status</span>
                            <select name="status" class="mt-1 h-10 w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500" required>
                                @foreach ($statusLabels as $key => $label)
                                    <option value="{{ $key }}" @selected(old('status', 'issued') === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="block">
                            <span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Handover Location</span>
                            <input
                                type="text"
                                name="handover_location"
                                value="{{ old('handover_location') }}"
                                placeholder="IT Room, Front Office..."
                                class="mt-1 h-10 w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                            >
                        </label>
                    </div>
                </div>

                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm" data-bast-recipient-form>
                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="block">
                            <span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Recipient User</span>
                            <select
                                name="recipient_user_id"
                                data-recipient-user-select
                                class="mt-1 h-10 w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                            >
                                <option value="">Manual recipient</option>
                                @foreach ($users as $user)
                                    <option
                                        value="{{ $user->id }}"
                                        data-recipient-name="{{ $user->name }}"
                                        data-recipient-email="{{ $user->email }}"
                                        data-department-id="{{ $user->department_id }}"
                                        @selected((int) old('recipient_user_id', $prefillUser?->id) === $user->id)
                                    >
                                        {{ $user->name }} - {{ $user->email }}
                                    </option>
                                @endforeach
                            </select>
                        </label>
                        <label class="block">
                            <span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Department</span>
                            <select
                                name="department_id"
                                data-recipient-department-select
                                class="mt-1 h-10 w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                            >
                                <option value="">Follow asset/user department</option>
                                @foreach ($departments as $department)
                                    <option value="{{ $department->id }}" @selected((int) old('department_id', $selectedLoan?->department_id ?? $prefillUser?->department_id ?? $selectedAsset?->department_id) === $department->id)>
                                        {{ $department->name }}
                                    </option>
                                @endforeach
                            </select>
                        </label>
                        <label class="block">
                            <span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Recipient Name</span>
                            <input
                                type="text"
                                name="recipient_name"
                                data-recipient-name-input
                                value="{{ old('recipient_name', $prefillUser?->name) }}"
                                class="mt-1 h-10 w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                                required
                            >
                        </label>
                        <label class="block">
                            <span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Recipient Email</span>
                            <input
                                type="email"
                                name="recipient_email"
                                data-recipient-email-input
                                value="{{ old('recipient_email', $prefillUser?->email) }}"
                                class="mt-1 h-10 w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                            >
                        </label>
                    </div>
                </div>

                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="grid gap-4">
                        <label class="block">
                            <span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Condition Summary</span>
                            <select
                                name="condition_summary"
                                class="mt-1 h-10 w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                            >
                                <option value="">Select condition</option>
                                @foreach ($conditionOptions as $label)
                                    <option value="{{ $label }}" @selected($selectedConditionSummary === $label)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Accessories</p>
                            <div class="mt-2 grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                                @foreach ($accessoryOptions as $accessory)
                                    <label class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700">
                                        <input
                                            type="checkbox"
                                            name="accessories[]"
                                            value="{{ $accessory }}"
                                            class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                                            @checked(in_array($accessory, $selectedAccessories, true))
                                        >
                                        {{ $accessory }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <label class="block">
                            <span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Notes</span>
                            <textarea
                                name="notes"
                                rows="4"
                                class="mt-1 w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                                placeholder="Catatan tambahan, kelengkapan, atau kondisi khusus."
                            >{{ old('notes') }}</textarea>
                        </label>
                        <label class="block">
                            <span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Foto Asset</span>
                            <input
                                type="file"
                                name="photos[]"
                                accept="image/jpeg,image/png,image/webp"
                                multiple
                                class="mt-1 block w-full rounded-lg border border-slate-200 bg-white text-sm text-slate-600 shadow-sm file:mr-4 file:border-0 file:bg-slate-950 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-white hover:file:bg-slate-800"
                            >
                            <p class="mt-2 text-xs text-slate-500">Upload maksimal 6 foto kondisi asset. Format JPG, PNG, atau WEBP, maksimal 5 MB per foto.</p>
                        </label>
                    </div>
                </div>

                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <a href="{{ route('admin.assets.bast.index') }}" class="inline-flex h-10 items-center justify-center rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 shadow-sm hover:border-emerald-200 hover:text-emerald-700">
                        Cancel
                    </a>
                    <button type="submit" class="inline-flex h-10 items-center justify-center rounded-lg bg-emerald-600 px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700">
                        Save BAST
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                document.querySelectorAll('[data-bast-recipient-form]').forEach(form => {
                    const userSelect = form.querySelector('[data-recipient-user-select]');
                    const nameInput = form.querySelector('[data-recipient-name-input]');
                    const emailInput = form.querySelector('[data-recipient-email-input]');
                    const departmentSelect = form.querySelector('[data-recipient-department-select]');

                    if (!userSelect) return;

                    userSelect.addEventListener('change', () => {
                        const selected = userSelect.selectedOptions[0];

                        if (!selected || !selected.value) {
                            if (nameInput) nameInput.value = '';
                            if (emailInput) emailInput.value = '';
                            return;
                        }

                        if (nameInput) nameInput.value = selected.dataset.recipientName || '';
                        if (emailInput) emailInput.value = selected.dataset.recipientEmail || '';

                        if (departmentSelect && selected.dataset.departmentId) {
                            departmentSelect.value = selected.dataset.departmentId;
                        }
                    });
                });
            });
        </script>
    @endpush
</x-app-layout>
