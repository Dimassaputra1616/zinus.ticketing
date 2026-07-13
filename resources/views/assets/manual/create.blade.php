@php
    $initialCategory = old('category', request('category', ''));
    $categoryProfiles = \App\Support\AssetCategoryProfile::formProfiles();
    $categoryKeys = collect($categories)
        ->mapWithKeys(fn ($category) => [$category => \App\Support\AssetCategoryProfile::key($category)]);
    $initialProfileKey = \App\Support\AssetCategoryProfile::key($initialCategory);
    $initialProfile = $categoryProfiles[$initialProfileKey];
@endphp

<x-app-layout>
    <div
        class="w-full space-y-6 pb-12 pt-6"
        x-data="{
            category: @js($initialCategory),
            categoryKeys: @js($categoryKeys),
            profiles: @js($categoryProfiles),
            get profileKey() {
                return this.categoryKeys[this.category] || 'other';
            },
            get profile() {
                return this.profiles[this.profileKey];
            },
        }"
    >
        <!-- Hero Header -->
        <section class="rounded-3xl border border-slate-200/80 bg-gradient-to-br from-white via-white to-emerald-50/60 p-4 shadow-md shadow-emerald-500/10 lg:p-6">
            <div class="flex items-center justify-between">
                <div class="space-y-2">
                    <p class="text-xs uppercase tracking-[0.35em] text-emerald-600/80">Asset Management Center</p>
                    <h1
                        class="text-3xl font-semibold text-slate-900"
                        x-text="profile.create_title"
                    >{{ $initialProfile['create_title'] }}</h1>
                    <p
                        class="text-sm text-slate-600"
                        x-text="profile.description"
                    >{{ $initialProfile['description'] }}</p>
                </div>
                <a
                    href="{{ route('admin.assets.manual.index') }}"
                    class="inline-flex items-center gap-2 text-sm font-semibold text-slate-600 hover:text-indigo-600"
                >
                    &larr; Back to list
                </a>
            </div>
        </section>

        <!-- Form Card -->
        <div class="rounded-3xl border border-slate-200/80 bg-white shadow-lg shadow-slate-200/60">
            <form action="{{ route('admin.assets.manual.store') }}" method="POST" class="space-y-6 px-6 py-6">
                @csrf

                <div class="grid gap-6 lg:grid-cols-2">
                    <!-- Identity Section -->
                    <div class="rounded-2xl border border-slate-200 bg-slate-50/60 p-5 shadow-inner shadow-slate-200/50">
                        <div class="flex items-center justify-between border-b border-slate-200 pb-3 mb-4">
                            <div>
                                <p class="text-xs uppercase tracking-[0.3em] text-slate-500">Asset Identity</p>
                                <h3 class="text-lg font-semibold text-slate-900">General Information</h3>
                            </div>
                            <span class="rounded-full bg-emerald-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.25em] text-emerald-700 ring-1 ring-emerald-100">NEW</span>
                        </div>

                        <div class="space-y-4">
                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="flex flex-col gap-1">
                                    <label class="text-sm font-semibold text-slate-700">Asset Code</label>
                                    <input
                                        name="asset_code"
                                        value="{{ old('asset_code') }}"
                                        class="h-11 rounded-xl border border-slate-200 px-3 text-sm text-slate-700 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                                        placeholder="Leave empty to auto-generate"
                                    >
                                    <p class="text-[10px] text-slate-400">e.g. AST-MAN-XXXXX</p>
                                    @error('asset_code')
                                        <p class="text-xs text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="flex flex-col gap-1">
                                    <label class="text-sm font-semibold text-slate-700">Device/Asset Name <span class="text-rose-500">*</span></label>
                                    <input
                                        name="name"
                                        value="{{ old('name') }}"
                                        class="h-11 rounded-xl border border-slate-200 px-3 text-sm text-slate-700 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                                        placeholder="{{ $initialProfile['name_placeholder'] }}"
                                        :placeholder="profile.name_placeholder"
                                        required
                                    >
                                    @error('name')
                                        <p class="text-xs text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="flex flex-col gap-1">
                                    <label class="text-sm font-semibold text-slate-700">Category <span class="text-rose-500">*</span></label>
                                    <select
                                        name="category"
                                        x-model="category"
                                        class="h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700 focus:border-indigo-400 focus:outline-none"
                                        required
                                    >
                                        <option value="" disabled selected>Select category</option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category }}" @selected(old('category', request('category')) === $category)>{{ $category }}</option>
                                        @endforeach
                                    </select>
                                    @error('category')
                                        <p class="text-xs text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="flex flex-col gap-1">
                                    <label class="text-sm font-semibold text-slate-700">
                                        <span x-text="profile.sub_label">{{ $initialProfile['sub_label'] }}</span>
                                    </label>
                                    <input
                                        name="sub_category"
                                        value="{{ old('sub_category') }}"
                                        class="h-11 rounded-xl border border-slate-200 px-3 text-sm text-slate-700 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                                        placeholder="{{ $initialProfile['sub_placeholder'] }}"
                                        :placeholder="profile.sub_placeholder"
                                    >
                                    @error('sub_category')
                                        <p class="text-xs text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="flex flex-col gap-1">
                                    <label class="text-sm font-semibold text-slate-700">Factory / Branch</label>
                                    <select
                                        name="factory"
                                        class="h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700 focus:border-indigo-400 focus:outline-none"
                                    >
                                        <option value="">Select factory location</option>
                                        <option value="Zinus F1 Bogor" @selected(old('factory') === 'Zinus F1 Bogor')>Zinus F1 Bogor</option>
                                        <option value="Zinus F2 Karawang" @selected(old('factory') === 'Zinus F2 Karawang')>Zinus F2 Karawang</option>
                                        <option value="Zinus F3 Tangerang" @selected(old('factory') === 'Zinus F3 Tangerang')>Zinus F3 Tangerang</option>
                                    </select>
                                    @error('factory')
                                        <p class="text-xs text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="flex flex-col gap-1">
                                    <label class="text-sm font-semibold text-slate-700">Department</label>
                                    <select
                                        name="department_id"
                                        class="h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700 focus:border-indigo-400 focus:outline-none"
                                    >
                                        <option value="">None / Shared</option>
                                        @foreach($departments as $dept)
                                            <option value="{{ $dept->id }}" @selected(old('department_id') == $dept->id)>{{ $dept->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('department_id')
                                        <p class="text-xs text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="flex flex-col gap-1">
                                    <label class="text-sm font-semibold text-slate-700">Specific Location / Room</label>
                                    <input
                                        name="location"
                                        value="{{ old('location') }}"
                                        class="h-11 rounded-xl border border-slate-200 px-3 text-sm text-slate-700 focus:border-indigo-400 focus:outline-none"
                                        placeholder="{{ $initialProfile['location_placeholder'] }}"
                                        :placeholder="profile.location_placeholder"
                                    >
                                    @error('location')
                                        <p class="text-xs text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="flex flex-col gap-1">
                                    <label class="text-sm font-semibold text-slate-700">Status <span class="text-rose-500">*</span></label>
                                    <select
                                        name="status"
                                        class="h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700 focus:border-indigo-400 focus:outline-none"
                                        required
                                    >
                                        <option value="available" @selected(old('status') === 'available')>Spare / Available</option>
                                        <option value="in_use" @selected(old('status', 'in_use') === 'in_use')>In Use</option>
                                        <option value="maintenance" @selected(old('status') === 'maintenance')>Under Maintenance</option>
                                        <option value="broken" @selected(old('status') === 'broken')>Retired / Broken</option>
                                    </select>
                                    @error('status')
                                        <p class="text-xs text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="flex flex-col gap-1">
                                    <label class="text-sm font-semibold text-slate-700">Assigned To User</label>
                                    <select
                                        name="user_id"
                                        class="h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700 focus:border-indigo-400 focus:outline-none"
                                    >
                                        <option value="">None</option>
                                        @foreach($users as $usr)
                                            <option value="{{ $usr->id }}" @selected(old('user_id') == $usr->id)>{{ $usr->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('user_id')
                                        <p class="text-xs text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Specifications / Lifecycle Section -->
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
                            <div>
                                <p class="text-xs uppercase tracking-[0.3em] text-slate-500">Lifecycle Details</p>
                                <h3
                                    class="text-lg font-semibold text-slate-900"
                                    x-text="profile.detail_heading"
                                >{{ $initialProfile['detail_heading'] }}</h3>
                            </div>
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.25em] text-slate-600">OPTIONAL</span>
                        </div>

                        <div class="space-y-4">
                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="flex flex-col gap-1">
                                    <label class="text-sm font-semibold text-slate-700">Brand</label>
                                    <input
                                        name="brand"
                                        value="{{ old('brand') }}"
                                        class="h-11 rounded-xl border border-slate-200 px-3 text-sm text-slate-700 focus:border-indigo-400 focus:outline-none"
                                        placeholder="{{ $initialProfile['brand_placeholder'] }}"
                                        :placeholder="profile.brand_placeholder"
                                    >
                                    @error('brand')
                                        <p class="text-xs text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="flex flex-col gap-1">
                                    <label class="text-sm font-semibold text-slate-700">Model</label>
                                    <input
                                        name="model"
                                        value="{{ old('model') }}"
                                        class="h-11 rounded-xl border border-slate-200 px-3 text-sm text-slate-700 focus:border-indigo-400 focus:outline-none"
                                        placeholder="{{ $initialProfile['model_placeholder'] }}"
                                        :placeholder="profile.model_placeholder"
                                    >
                                    @error('model')
                                        <p class="text-xs text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="flex flex-col gap-1">
                                    <label class="text-sm font-semibold text-slate-700" x-text="profile.serial_label">{{ $initialProfile['serial_label'] }}</label>
                                    <input
                                        name="serial_number"
                                        value="{{ old('serial_number') }}"
                                        class="h-11 rounded-xl border border-slate-200 px-3 text-sm text-slate-700 focus:border-indigo-400 focus:outline-none"
                                        placeholder="{{ $initialProfile['serial_placeholder'] }}"
                                        :placeholder="profile.serial_placeholder"
                                    >
                                    @error('serial_number')
                                        <p class="text-xs text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="flex flex-col gap-1">
                                    <label class="text-sm font-semibold text-slate-700">Price (IDR)</label>
                                    <input
                                        type="number"
                                        name="price"
                                        value="{{ old('price') }}"
                                        class="h-11 rounded-xl border border-slate-200 px-3 text-sm text-slate-700 focus:border-indigo-400 focus:outline-none"
                                        placeholder="e.g. 5000000"
                                    >
                                    @error('price')
                                        <p class="text-xs text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div
                                x-cloak
                                x-show="profile.show_ip || profile.show_specs"
                                class="grid gap-4 md:grid-cols-2"
                            >
                                <div x-show="profile.show_ip" class="flex flex-col gap-1">
                                    <label class="text-sm font-semibold text-slate-700">IP Address</label>
                                    <input
                                        name="ip_address"
                                        value="{{ old('ip_address') }}"
                                        :disabled="!profile.show_ip"
                                        inputmode="decimal"
                                        class="h-11 rounded-xl border border-slate-200 px-3 font-mono text-sm text-slate-700 focus:border-indigo-400 focus:outline-none"
                                        placeholder="e.g. 192.168.10.25"
                                    >
                                    @error('ip_address')
                                        <p class="text-xs text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div x-show="profile.show_specs" class="flex flex-col gap-1">
                                    <label class="text-sm font-semibold text-slate-700" x-text="profile.specs_label">{{ $initialProfile['specs_label'] }}</label>
                                    <input
                                        name="specs"
                                        value="{{ old('specs') }}"
                                        :disabled="!profile.show_specs"
                                        class="h-11 rounded-xl border border-slate-200 px-3 text-sm text-slate-700 focus:border-indigo-400 focus:outline-none"
                                        placeholder="{{ $initialProfile['specs_placeholder'] }}"
                                        :placeholder="profile.specs_placeholder"
                                    >
                                    @error('specs')
                                        <p class="text-xs text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div x-cloak x-show="profile.show_computer" class="space-y-4">
                                <div class="grid gap-4 md:grid-cols-2">
                                    <div class="flex flex-col gap-1">
                                        <label class="text-sm font-semibold text-slate-700">Hostname</label>
                                        <input
                                            name="hostname"
                                            value="{{ old('hostname') }}"
                                            :disabled="!profile.show_computer"
                                            class="h-11 rounded-xl border border-slate-200 px-3 font-mono text-sm text-slate-700 focus:border-indigo-400 focus:outline-none"
                                            placeholder="e.g. PC-FIN-001"
                                        >
                                        @error('hostname')
                                            <p class="text-xs text-rose-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <label class="text-sm font-semibold text-slate-700">AnyDesk ID</label>
                                        <input
                                            name="anydesk_id"
                                            value="{{ old('anydesk_id') }}"
                                            :disabled="!profile.show_computer"
                                            class="h-11 rounded-xl border border-slate-200 px-3 font-mono text-sm text-slate-700 focus:border-indigo-400 focus:outline-none"
                                            placeholder="e.g. 123456789"
                                        >
                                        @error('anydesk_id')
                                            <p class="text-xs text-rose-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                                <div class="grid gap-4 md:grid-cols-2">
                                    <div class="flex flex-col gap-1">
                                        <label class="text-sm font-semibold text-slate-700">CPU</label>
                                        <input
                                            name="cpu"
                                            value="{{ old('cpu') }}"
                                            :disabled="!profile.show_computer"
                                            class="h-11 rounded-xl border border-slate-200 px-3 text-sm text-slate-700 focus:border-indigo-400 focus:outline-none"
                                            placeholder="e.g. Intel Core i5-12400"
                                        >
                                        @error('cpu')
                                            <p class="text-xs text-rose-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <label class="text-sm font-semibold text-slate-700">RAM (GB)</label>
                                        <input
                                            type="number"
                                            min="0"
                                            name="ram_gb"
                                            value="{{ old('ram_gb') }}"
                                            :disabled="!profile.show_computer"
                                            class="h-11 rounded-xl border border-slate-200 px-3 text-sm text-slate-700 focus:border-indigo-400 focus:outline-none"
                                            placeholder="e.g. 16"
                                        >
                                        @error('ram_gb')
                                            <p class="text-xs text-rose-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                                <div class="grid gap-4 md:grid-cols-2">
                                    <div class="flex flex-col gap-1">
                                        <label class="text-sm font-semibold text-slate-700">Storage (GB)</label>
                                        <input
                                            type="number"
                                            min="0"
                                            name="storage_gb"
                                            value="{{ old('storage_gb') }}"
                                            :disabled="!profile.show_computer"
                                            class="h-11 rounded-xl border border-slate-200 px-3 text-sm text-slate-700 focus:border-indigo-400 focus:outline-none"
                                            placeholder="e.g. 512"
                                        >
                                        @error('storage_gb')
                                            <p class="text-xs text-rose-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <label class="text-sm font-semibold text-slate-700">Operating System</label>
                                        <input
                                            name="os_name"
                                            value="{{ old('os_name') }}"
                                            :disabled="!profile.show_computer"
                                            class="h-11 rounded-xl border border-slate-200 px-3 text-sm text-slate-700 focus:border-indigo-400 focus:outline-none"
                                            placeholder="e.g. Windows 11 Pro"
                                        >
                                        @error('os_name')
                                            <p class="text-xs text-rose-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="grid gap-4 md:grid-cols-3">
                                <div class="flex flex-col gap-1">
                                    <label class="text-sm font-semibold text-slate-700">Condition</label>
                                    <select
                                        name="condition"
                                        class="h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700 focus:border-indigo-400 focus:outline-none"
                                    >
                                        <option value="good" @selected(old('condition', 'good') === 'good')>Good</option>
                                        <option value="minor_issue" @selected(old('condition') === 'minor_issue')>Minor Issue</option>
                                        <option value="damaged" @selected(old('condition') === 'damaged')>Damaged</option>
                                        <option value="repair" @selected(old('condition') === 'repair')>In Repair</option>
                                        <option value="disposed" @selected(old('condition') === 'disposed')>Disposed</option>
                                        <option value="lost" @selected(old('condition') === 'lost')>Lost</option>
                                    </select>
                                    @error('condition')
                                        <p class="text-xs text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="flex flex-col gap-1">
                                    <label class="text-sm font-semibold text-slate-700">Lifecycle Status</label>
                                    <select
                                        name="lifecycle_status"
                                        class="h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700 focus:border-indigo-400 focus:outline-none"
                                    >
                                        <option value="active" @selected(old('lifecycle_status', 'active') === 'active')>Active</option>
                                        <option value="in_repair" @selected(old('lifecycle_status') === 'in_repair')>In Repair</option>
                                        <option value="spare" @selected(old('lifecycle_status') === 'spare')>Spare</option>
                                        <option value="assigned" @selected(old('lifecycle_status') === 'assigned')>Assigned</option>
                                        <option value="disposed" @selected(old('lifecycle_status') === 'disposed')>Disposed</option>
                                        <option value="lost" @selected(old('lifecycle_status') === 'lost')>Lost</option>
                                        <option value="replaced" @selected(old('lifecycle_status') === 'replaced')>Replaced</option>
                                    </select>
                                    @error('lifecycle_status')
                                        <p class="text-xs text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="flex flex-col gap-1">
                                    <label
                                        class="text-sm font-semibold text-slate-700"
                                        x-text="profileKey === 'software' ? 'License Expiry' : 'Warranty Until'"
                                    >{{ $initialProfileKey === 'software' ? 'License Expiry' : 'Warranty Until' }}</label>
                                    <input
                                        type="date"
                                        name="warranty_until"
                                        value="{{ old('warranty_until') }}"
                                        class="h-11 rounded-xl border border-slate-200 px-3 text-sm text-slate-700 focus:border-indigo-400 focus:outline-none"
                                    >
                                    @error('warranty_until')
                                        <p class="text-xs text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="flex flex-col gap-1">
                                <label class="text-sm font-semibold text-slate-700">
                                    <span x-text="profile.notes_label">{{ $initialProfile['notes_label'] }}</span>
                                </label>
                                <textarea
                                    name="notes"
                                    rows="4"
                                    class="rounded-xl border border-slate-200 px-3 py-3 text-sm text-slate-700 focus:border-indigo-400 focus:outline-none"
                                    placeholder="{{ $initialProfile['notes_placeholder'] }}"
                                    :placeholder="profile.notes_placeholder"
                                >{{ old('notes') }}</textarea>
                                @error('notes')
                                    <p class="text-xs text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-6">
                    <a
                        href="{{ route('admin.assets.manual.index') }}"
                        class="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                    >
                        Cancel
                    </a>
                    <button
                        type="submit"
                        class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-6 py-2.5 text-sm font-semibold text-white shadow-md shadow-indigo-500/20 hover:bg-indigo-700"
                    >
                        Save Asset
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
