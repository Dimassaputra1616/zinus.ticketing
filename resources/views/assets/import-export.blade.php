<x-app-layout>
    @if (session('success'))
        <div
            x-data="{ open: true }"
            x-init="setTimeout(() => open = false, 4000)"
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
                <p class="text-xs text-emerald-50">{!! session('success') !!}</p>
            </div>
        </div>
    @endif

    @if (session('error'))
        <div
            x-data="{ open: true }"
            x-init="setTimeout(() => open = false, 4000)"
            x-show="open"
            x-transition
            class="fixed right-4 top-4 z-50 flex items-start gap-3 rounded-2xl bg-rose-600 px-4 py-3 text-white shadow-xl shadow-rose-500/30"
        >
            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-white/15">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10" />
                    <line x1="12" y1="8" x2="12" y2="12" />
                    <line x1="12" y1="16" x2="12.01" y2="16" />
                </svg>
            </div>
            <div>
                <p class="text-sm font-semibold">Gagal</p>
                <p class="text-xs text-rose-50">{{ session('error') }}</p>
            </div>
        </div>
    @endif

    <div class="min-h-screen bg-slate-50 pb-10 pt-5">
        <div class="mx-auto w-full space-y-6">
            <!-- Header -->
            <section class="rounded-3xl border border-slate-200/80 bg-gradient-to-br from-white via-white to-emerald-50/60 p-4 shadow-md shadow-emerald-500/10 lg:p-6">
                <div class="flex flex-col gap-4">
                    <div class="space-y-2">
                        <p class="text-xs uppercase tracking-[0.35em] text-emerald-600/80">Asset Management Center</p>
                        <h1 class="text-3xl font-semibold text-slate-900">Import / Export Asset Data</h1>
                        <p class="text-sm text-slate-600">Sync bulk lists of manual and agent assets using CSV formats.</p>
                    </div>
                </div>
            </section>

            <div class="grid gap-6 md:grid-cols-2">
                <!-- Export Section -->
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm flex flex-col justify-between">
                    <div class="space-y-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-slate-900">Export All Assets</h3>
                        <p class="text-sm text-slate-600 leading-relaxed">
                            Generate and download a normalized CSV spreadsheet of all registered IT hardware and manual office assets. Perfect for local backups, audits, and spreadsheet sorting.
                        </p>
                    </div>
                    <div class="mt-8">
                        <a
                            href="{{ route('admin.assets.export') }}"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-indigo-600 py-3 text-sm font-semibold text-white shadow-md hover:bg-indigo-700 transition"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Export to CSV
                        </a>
                    </div>
                </div>

                <!-- Import Section -->
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <form action="{{ route('admin.assets.import') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-slate-900">Import Bulk Assets</h3>
                        <p class="text-sm text-slate-600 leading-relaxed">
                            Upload a `.csv` file to create new assets or update existing ones by their code constraint.
                        </p>

                        <div class="border-2 border-dashed border-slate-200 rounded-2xl p-4 flex flex-col items-center justify-center bg-slate-50 hover:bg-slate-100 transition cursor-pointer relative">
                            <input
                                type="file"
                                name="file"
                                accept=".csv"
                                required
                                class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                            >
                            <svg class="h-8 w-8 text-slate-400 mb-2" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <span class="text-xs font-semibold text-slate-700">Choose CSV File</span>
                            <span class="text-[10px] text-slate-400 mt-1">Maximum file size: 5MB</span>
                        </div>

                        <button
                            type="submit"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-emerald-600 py-3 text-sm font-semibold text-white shadow-md hover:bg-emerald-700 transition"
                        >
                            Upload & Import CSV
                        </button>
                    </form>
                </div>
            </div>

            <!-- Guidelines / Formatting Documentation -->
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
                <h3 class="text-lg font-bold text-slate-900">CSV Template Guidelines</h3>
                <p class="text-sm text-slate-600">To ensure correct data ingestion, make sure your CSV contains the following headers precisely:</p>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs font-mono border-collapse border border-slate-200 rounded-xl">
                        <thead>
                            <tr class="bg-slate-50 text-slate-700 border-b border-slate-200">
                                <th class="p-3 border-r border-slate-200">Header Column</th>
                                <th class="p-3 border-r border-slate-200">Required</th>
                                <th class="p-3 border-r border-slate-200">Sample Value</th>
                                <th class="p-3">Description</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-600">
                            <tr>
                                <td class="p-3 border-r border-slate-200 font-bold text-slate-800">asset_code</td>
                                <td class="p-3 border-r border-slate-200 text-rose-500 font-bold">No</td>
                                <td class="p-3 border-r border-slate-200">AST-MAN-9A2X1</td>
                                <td class="p-3">Unique identifier code. Left empty will auto-generate one.</td>
                            </tr>
                            <tr>
                                <td class="p-3 border-r border-slate-200 font-bold text-slate-800">name</td>
                                <td class="p-3 border-r border-slate-200 text-emerald-600 font-bold">Yes</td>
                                <td class="p-3 border-r border-slate-200">Epson Printer Finance L3</td>
                                <td class="p-3">Descriptive title of the asset hardware.</td>
                            </tr>
                            <tr>
                                <td class="p-3 border-r border-slate-200 font-bold text-slate-800">category</td>
                                <td class="p-3 border-r border-slate-200 text-emerald-600 font-bold">Yes</td>
                                <td class="p-3 border-r border-slate-200">Printer</td>
                                <td class="p-3">E.g. Printer, Router, Switch, UPS, Scanner.</td>
                            </tr>
                            <tr>
                                <td class="p-3 border-r border-slate-200 font-bold text-slate-800">source_type</td>
                                <td class="p-3 border-r border-slate-200 text-rose-500 font-bold">No</td>
                                <td class="p-3 border-r border-slate-200">manual</td>
                                <td class="p-3">Can be `manual` or `agent`. Defaults to `import_excel` if unspecified.</td>
                            </tr>
                            <tr>
                                <td class="p-3 border-r border-slate-200 font-bold text-slate-800">condition</td>
                                <td class="p-3 border-r border-slate-200 text-rose-500 font-bold">No</td>
                                <td class="p-3 border-r border-slate-200">good</td>
                                <td class="p-3">`good`, `minor_issue`, `damaged`, `repair`, `disposed`, `lost`.</td>
                            </tr>
                            <tr>
                                <td class="p-3 border-r border-slate-200 font-bold text-slate-800">lifecycle_status</td>
                                <td class="p-3 border-r border-slate-200 text-rose-500 font-bold">No</td>
                                <td class="p-3 border-r border-slate-200">active</td>
                                <td class="p-3">`active`, `in_repair`, `spare`, `assigned`, `disposed`, `lost`, `replaced`.</td>
                            </tr>
                            <tr>
                                <td class="p-3 border-r border-slate-200 font-bold text-slate-800">warranty_until</td>
                                <td class="p-3 border-r border-slate-200 text-rose-500 font-bold">No</td>
                                <td class="p-3 border-r border-slate-200">2027-12-31</td>
                                <td class="p-3">Date format: YYYY-MM-DD.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
