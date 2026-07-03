@php
    $initialCategory = old('category', request('category', ''));
    $initialPrinterMode = in_array($initialCategory, ['Printer & Scanner', 'Printer', 'Scanner'], true);
@endphp

<x-app-layout>
    <div
        class="w-full space-y-6 pb-12 pt-6"
        x-data="{
            category: @js($initialCategory),
            get printerMode() {
                return ['Printer & Scanner', 'Printer', 'Scanner'].includes(this.category);
            }
        }"
    >
        <!-- Hero Header -->
        <section class="rounded-3xl border border-slate-200/80 bg-gradient-to-br from-white via-white to-emerald-50/60 p-4 shadow-md shadow-emerald-500/10 lg:p-6">
            <div class="flex items-center justify-between">
                <div class="space-y-2">
                    <p class="text-xs uppercase tracking-[0.35em] text-emerald-600/80">Asset Management Center</p>
                    <h1
                        class="text-3xl font-semibold text-slate-900"
                        x-text="printerMode ? 'Add Printer / Scanner' : 'Add Manual Asset'"
                    >{{ $initialPrinterMode ? 'Add Printer / Scanner' : 'Add Manual Asset' }}</h1>
                    <p
                        class="text-sm text-slate-600"
                        x-text="printerMode ? 'Record printer identity, network information, and operational details.' : 'Manually record and manage a non-agent device or hardware accessory.'"
                    >{{ $initialPrinterMode ? 'Record printer identity, network information, and operational details.' : 'Manually record and manage a non-agent device or hardware accessory.' }}</p>
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
                                        placeholder="{{ $initialPrinterMode ? 'e.g. Printer Finance, Scanner Warehouse' : 'e.g. Printer Finance, Core Switch L3' }}"
                                        :placeholder="printerMode ? 'e.g. Printer Finance, Scanner Warehouse' : 'e.g. Printer Finance, Core Switch L3'"
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
                                        <span x-text="printerMode ? 'Device Type' : 'Sub Category'">{{ $initialPrinterMode ? 'Device Type' : 'Sub Category' }}</span>
                                    </label>
                                    <input
                                        name="sub_category"
                                        value="{{ old('sub_category') }}"
                                        class="h-11 rounded-xl border border-slate-200 px-3 text-sm text-slate-700 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                                        placeholder="{{ $initialPrinterMode ? 'e.g. Printer, Scanner, Multifunction' : 'e.g. HDMI Monitor, Office PC, Barcode Scanner' }}"
                                        :placeholder="printerMode ? 'e.g. Printer, Scanner, Multifunction' : 'e.g. HDMI Monitor, Office PC, Barcode Scanner'"
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
                                        placeholder="{{ $initialPrinterMode ? 'e.g. Finance Room, Print Area' : 'e.g. Server Room, Finance Room' }}"
                                        :placeholder="printerMode ? 'e.g. Finance Room, Print Area' : 'e.g. Server Room, Finance Room'"
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
                                    x-text="printerMode ? 'Printer Details' : 'Technical Details'"
                                >{{ $initialPrinterMode ? 'Printer Details' : 'Technical Details' }}</h3>
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
                                        placeholder="{{ $initialPrinterMode ? 'e.g. Epson, HP, Canon, Brother' : 'e.g. Epson, HP, Cisco' }}"
                                        :placeholder="printerMode ? 'e.g. Epson, HP, Canon, Brother' : 'e.g. Epson, HP, Cisco'"
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
                                        placeholder="{{ $initialPrinterMode ? 'e.g. L3110, M404dn, G4010' : 'e.g. L3110, SG350-28' }}"
                                        :placeholder="printerMode ? 'e.g. L3110, M404dn, G4010' : 'e.g. L3110, SG350-28'"
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
                                        value="{{ old('serial_number') }}"
                                        class="h-11 rounded-xl border border-slate-200 px-3 text-sm text-slate-700 focus:border-indigo-400 focus:outline-none"
                                        placeholder="e.g. S/N or Service Tag"
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
                                x-show="printerMode"
                                class="grid gap-4 md:grid-cols-2"
                            >
                                <div class="flex flex-col gap-1">
                                    <label class="text-sm font-semibold text-slate-700">IP Address</label>
                                    <input
                                        name="ip_address"
                                        value="{{ old('ip_address') }}"
                                        :disabled="!printerMode"
                                        inputmode="decimal"
                                        class="h-11 rounded-xl border border-slate-200 px-3 font-mono text-sm text-slate-700 focus:border-indigo-400 focus:outline-none"
                                        placeholder="e.g. 192.168.10.25"
                                    >
                                    @error('ip_address')
                                        <p class="text-xs text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="flex flex-col gap-1">
                                    <label class="text-sm font-semibold text-slate-700">Printer Specifications</label>
                                    <input
                                        name="specs"
                                        value="{{ old('specs') }}"
                                        :disabled="!printerMode"
                                        class="h-11 rounded-xl border border-slate-200 px-3 text-sm text-slate-700 focus:border-indigo-400 focus:outline-none"
                                        placeholder="Connection: LAN | Print Type: Color | Paper: A4"
                                    >
                                    @error('specs')
                                        <p class="text-xs text-rose-600">{{ $message }}</p>
                                    @enderror
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
                                    <label class="text-sm font-semibold text-slate-700">Warranty Until</label>
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
                                    <span x-text="printerMode ? 'Maintenance / Supply Notes' : 'Technical Notes / Specs'">{{ $initialPrinterMode ? 'Maintenance / Supply Notes' : 'Technical Notes / Specs' }}</span>
                                </label>
                                <textarea
                                    name="notes"
                                    rows="4"
                                    class="rounded-xl border border-slate-200 px-3 py-3 text-sm text-slate-700 focus:border-indigo-400 focus:outline-none"
                                    placeholder="{{ $initialPrinterMode ? 'e.g. Toner condition, maintenance history, or known print issues...' : 'Enter physical condition details, IP address, specs or serial numbers of accessories...' }}"
                                    :placeholder="printerMode ? 'e.g. Toner condition, maintenance history, or known print issues...' : 'Enter physical condition details, IP address, specs or serial numbers of accessories...'"
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
