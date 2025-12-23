<x-app-layout>
    @if (session('success'))
        <div
            x-data="{ open: true }"
            x-init="setTimeout(() => open = false, 2500)"
            x-show="open"
            x-transition
            class="fixed right-4 top-4 z-50 flex items-start gap-3 rounded-2xl bg-emerald-600 px-4 py-3 text-white shadow-xl shadow-emerald-500/30"
        >
            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-white/15">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 6 9 17l-5-5" />
                </svg>
            </div>
            <div>
                <p class="text-sm font-semibold">Berhasil</p>
                <p class="text-xs text-emerald-50">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    @php
        $isEdit = isset($asset);
        $formAction = $isEdit ? route('assets.update', $asset) : route('assets.store');
        $formTitle = $isEdit ? 'Ubah Asset' : 'Tambah Asset';
        $formSubtitle = $isEdit ? 'Perbarui detail perangkat yang sudah terdaftar.' : 'Rekam perangkat baru ke master data asset.';
        $submitLabel = $isEdit ? 'Simpan Perubahan' : 'Simpan Asset';
        $factoryOptions = ['Zinus F1 Bogor', 'Zinus F2 Karawang', 'Zinus F3 Tangerang'];
        $categoryOptions = collect(['PC', 'Laptop', 'Monitor', 'Peripheral'])->map(function ($label) {
            return ['id' => $label, 'name' => $label];
        });
        $statusOptions = [
            ['label' => 'Active', 'value' => \App\Models\Asset::STATUS_IN_USE],
            ['label' => 'In Repair', 'value' => \App\Models\Asset::STATUS_MAINTENANCE],
            ['label' => 'Spare', 'value' => \App\Models\Asset::STATUS_AVAILABLE],
            ['label' => 'Retired', 'value' => \App\Models\Asset::STATUS_BROKEN],
        ];
    @endphp

    @php
        $syncSource = $asset->last_sync_source ?? $asset->sync_source ?? null;
        $isAgent = ($syncSource === 'agent');
        $lastSyncedAt = $asset->last_synced_at ?? null;
    @endphp
    <div class="mx-auto w-full max-w-6xl space-y-6 px-4 pb-12 pt-8 lg:px-0">
        <x-ui.section-hero
            pill="Asset & Inventory"
            title="{{ $formTitle }}"
            description="{{ $formSubtitle }}"
        >
            <x-slot:icon>
                <svg class="h-7 w-7 text-[#12824C]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="6" width="18" height="12" rx="2" />
                    <path d="M8 6v-2h8v2" />
                    <path d="M12 18v3" />
                </svg>
            </x-slot:icon>
            <x-slot:side>
                <a
                    href="{{ $isEdit ? route('assets.show', $asset) : route('assets.index') }}"
                    class="inline-flex items-center gap-2 text-sm font-semibold text-emerald-700 hover:text-emerald-800"
                >
                    &larr; Kembali ke {{ $isEdit ? 'detail asset' : 'daftar asset' }}
                </a>
            </x-slot:side>
        </x-ui.section-hero>

        <div class="rounded-3xl border border-slate-200/80 bg-white shadow-lg shadow-slate-200/60">
            <form action="{{ $formAction }}" method="POST" class="space-y-6 px-6 py-6">
                @csrf
                @if ($isEdit)
                    @method('PUT')
                @endif

                <div class="grid gap-6 lg:grid-cols-2">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50/60 p-5 shadow-inner shadow-slate-200/50">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs uppercase tracking-[0.3em] text-slate-500">Identitas Asset</p>
                                <h3 class="text-lg font-semibold text-slate-900">Info Utama</h3>
                            </div>
                            <span class="rounded-full bg-emerald-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.25em] text-emerald-700 ring-1 ring-emerald-100">{{ $isEdit ? 'Edit' : 'Baru' }}</span>
                        </div>

                        <div class="mt-4 grid gap-4">
                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="flex flex-col gap-1">
                                    <label class="text-sm font-semibold text-slate-700">Kode Asset <span class="text-rose-500">*</span></label>
                                    <input
                                        name="asset_code"
                                        value="{{ old('asset_code', $asset?->asset_code) }}"
                                        class="h-11 rounded-xl border border-slate-200 px-3 text-sm text-slate-700 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-100"
                                        placeholder="Contoh: AS-001"
                                        required
                                    >
                                    @error('asset_code')
                                        <p class="text-xs text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="flex flex-col gap-1">
                                    <label class="text-sm font-semibold text-slate-700">Nama Perangkat <span class="text-rose-500">*</span></label>
                                    <input
                                        name="name"
                                        value="{{ old('name', $asset?->name) }}"
                                        class="h-11 rounded-xl border border-slate-200 px-3 text-sm text-slate-700 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-100"
                                        placeholder="Contoh: Laptop Dell XPS 13"
                                        required
                                    >
                                    @error('name')
                                        <p class="text-xs text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="flex flex-col gap-1">
                                    <label class="text-sm font-semibold text-slate-700">Factory</label>
                                    <select
                                        name="factory"
                                        class="h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-100"
                                    >
                                        <option value="">Pilih Factory</option>
                                        @foreach ($factoryOptions as $factory)
                                            <option value="{{ $factory }}" @selected(old('factory', $asset?->factory) === $factory)>{{ $factory }}</option>
                                        @endforeach
                                    </select>
                                    @error('factory')
                                        <p class="text-xs text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="flex flex-col gap-1">
                                    <label class="text-sm font-semibold text-slate-700">Lokasi</label>
                                    <input
                                        name="location"
                                        value="{{ old('location', $asset?->location) }}"
                                        class="h-11 rounded-xl border border-slate-200 px-3 text-sm text-slate-700 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-100"
                                        placeholder="Contoh: Warehouse 2"
                                    >
                                    @error('location')
                                        <p class="text-xs text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="flex flex-col gap-1">
                                    <label class="text-sm font-semibold text-slate-700">Departemen</label>
                                    <select
                                        name="department_id"
                                        class="h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-100"
                                    >
                                        <option value="">Pilih Departemen</option>
                                        @foreach ($departments as $department)
                                            <option value="{{ $department->id }}" @selected(old('department_id', $asset?->department_id) == $department->id)>{{ $department->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('department_id')
                                        <p class="text-xs text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="flex flex-col gap-1">
                                    <label class="text-sm font-semibold text-slate-700">Kategori <span class="text-rose-500">*</span></label>
                                    <select
                                        name="category"
                                        class="h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-100"
                                        required
                                    >
                                        <option value="" disabled @selected(! old('category', $asset?->category))>Pilih kategori</option>
                                        @foreach ($categoryOptions as $categoryOption)
                                            <option value="{{ $categoryOption['id'] }}" @selected(old('category', $asset?->category) == $categoryOption['id'])>{{ $categoryOption['name'] }}</option>
                                        @endforeach
                                    </select>
                                    @error('category')
                                        <p class="text-xs text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="flex flex-col gap-1">
                                    <label class="text-sm font-semibold text-slate-700">Status <span class="text-rose-500">*</span></label>
                                    <select
                                        name="status"
                                        class="h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-100"
                                        required
                                    >
                                        @foreach ($statusOptions as $statusOption)
                                            <option value="{{ $statusOption['value'] }}" @selected(old('status', $asset?->status) === $statusOption['value'])>{{ $statusOption['label'] }}</option>
                                        @endforeach
                                    </select>
                                    @error('status')
                                        <p class="text-xs text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="flex flex-col gap-1">
                                    <label class="text-sm font-semibold text-slate-700">Assigned To</label>
                                    <select
                                        name="user_id"
                                        class="h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-100"
                                    >
                                        <option value="">Tidak ada</option>
                                        @foreach ($users as $user)
                                            <option value="{{ $user->id }}" @selected(old('user_id', $asset?->user_id) == $user->id)>{{ $user->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('user_id')
                                        <p class="text-xs text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        @php $isAgent = ($asset->sync_source ?? null) === 'agent'; @endphp
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs uppercase tracking-[0.3em] text-slate-500">Hardware & Lifecycle</p>
                                <h3 class="text-lg font-semibold text-slate-900">Detail Perangkat</h3>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="rounded-full bg-emerald-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.25em] text-emerald-700 ring-1 ring-emerald-100">Sync Source: {{ $syncSource ? Str::title($syncSource) : 'Manual' }}</span>
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.25em] text-slate-700 ring-1 ring-slate-200">Optional</span>
                            </div>
                        </div>

                        <div class="mt-4 grid gap-4">
                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="flex flex-col gap-1">
                                    <label class="text-sm font-semibold text-slate-700">Brand</label>
                                    <input
                                        name="brand"
                                        value="{{ old('brand', $asset?->brand) }}"
                                        class="h-11 rounded-xl border border-slate-200 px-3 text-sm text-slate-700 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-100 {{ $isAgent ? 'bg-slate-50 text-slate-500 cursor-not-allowed' : '' }}"
                                        placeholder="Contoh: Dell"
                                        @if ($isAgent) readonly @endif
                                    >
                                    @error('brand')
                                        <p class="text-xs text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="flex flex-col gap-1">
                                    <label class="text-sm font-semibold text-slate-700">Model</label>
                                    <input
                                        name="model"
                                        value="{{ old('model', $asset?->model) }}"
                                        class="h-11 rounded-xl border border-slate-200 px-3 text-sm text-slate-700 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-100 {{ $isAgent ? 'bg-slate-50 text-slate-500 cursor-not-allowed' : '' }}"
                                        placeholder="Contoh: XPS 13"
                                        @if ($isAgent) readonly @endif
                                    >
                                    @error('model')
                                        <p class="text-xs text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="flex flex-col gap-1">
                                    <label class="text-sm font-semibold text-slate-700">Serial Number</label>
                                    <input
                                        name="serial_number"
                                        value="{{ old('serial_number', $asset?->serial_number) }}"
                                        class="h-11 rounded-xl border border-slate-200 px-3 text-sm text-slate-700 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-100 {{ $isAgent ? 'bg-slate-50 text-slate-500 cursor-not-allowed' : '' }}"
                                        placeholder="Isi serial number"
                                        @if ($isAgent) readonly @endif
                                    >
                                    @error('serial_number')
                                        <p class="text-xs text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="flex flex-col gap-1">
                                    <label class="text-sm font-semibold text-slate-700">Harga (IDR)</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        name="price"
                                        value="{{ old('price', $asset?->price) }}"
                                        class="h-11 rounded-xl border border-slate-200 px-3 text-sm text-slate-700 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-100"
                                        placeholder="0.00"
                                    >
                                    @error('price')
                                        <p class="text-xs text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="flex flex-col gap-1">
                                    <label class="text-sm font-semibold text-slate-700">Tanggal Pembelian</label>
                                    <input
                                        type="date"
                                        name="purchase_date"
                                        value="{{ old('purchase_date', optional($asset?->purchase_date)->format('Y-m-d')) }}"
                                        class="h-11 rounded-xl border border-slate-200 px-3 text-sm text-slate-700 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-100"
                                        placeholder="dd/mm/yyyy"
                                    >
                                    @error('purchase_date')
                                        <p class="text-xs text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="flex flex-col gap-1">
                                    <label class="text-sm font-semibold text-slate-700">Warranty End</label>
                                    <input
                                        type="date"
                                        name="warranty_expired"
                                        value="{{ old('warranty_expired', optional($asset?->warranty_expired)->format('Y-m-d')) }}"
                                        class="h-11 rounded-xl border border-slate-200 px-3 text-sm text-slate-700 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-100"
                                        placeholder="dd/mm/yyyy"
                                    >
                                    @error('warranty_expired')
                                        <p class="text-xs text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="flex flex-col gap-1">
                                <label class="text-sm font-semibold text-slate-700">Spesifikasi</label>
                                <textarea
                                    name="specs"
                                    rows="3"
                                    class="rounded-xl border border-slate-200 px-3 py-3 text-sm text-slate-700 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-100 {{ $isAgent ? 'bg-slate-50 text-slate-500 cursor-not-allowed' : '' }}"
                                    placeholder="CPU, RAM, Storage, GPU..."
                                    @if ($isAgent) readonly @endif
                                >{{ old('specs', $asset?->specs) }}</textarea>
                                <p class="text-xs text-slate-500">
                                    Informasi ini diisi otomatis oleh agent. Tidak perlu diubah manual.
                                </p>
                                @error('specs')
                                    <p class="text-xs text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="flex flex-col gap-1">
                                <label class="text-sm font-semibold text-slate-700">Catatan teknis</label>
                                <textarea
                                    name="notes"
                                    rows="3"
                                    class="rounded-xl border border-slate-200 px-3 py-3 text-sm text-slate-700 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-100"
                                    placeholder="Catatan tambahan atau kondisi khusus."
                                >{{ old('notes', $asset?->notes) }}</textarea>
                                @error('notes')
                                    <p class="text-xs text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            @if ($isAgent)
                                <p class="text-xs text-slate-500">Data ini diupdate otomatis dari agent. Untuk mengubah, jalankan sync di komputer aset.</p>
                            @endif

                            <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-xs text-slate-600">
                                @if ($asset?->last_synced_at)
                                    Last synced from agent: {{ $asset->last_synced_at }} (Source: {{ ucfirst($asset->sync_source ?? 'manual') }})
                                @elseif ($syncSource === 'manual')
                                    Belum pernah sync dari agent (Source: Manual)
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="sticky bottom-0 left-0 right-0 mt-4 -mx-6 flex items-center justify-end gap-3 border-t border-slate-200/80 bg-white/95 px-6 py-4 backdrop-blur">
                    <a
                        href="{{ $isEdit ? route('assets.show', $asset) : route('assets.index') }}"
                        class="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-700 hover:border-emerald-200 hover:text-emerald-700"
                    >
                        Batal
                    </a>
                    <button
                        type="submit"
                        class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white shadow-md shadow-emerald-400/30 transition hover:-translate-y-0.5 hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-200"
                    >
                        {{ $submitLabel }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
