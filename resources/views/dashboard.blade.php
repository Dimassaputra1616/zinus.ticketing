<x-app-layout>
    <div
        class="w-full pt-4 sm:pt-6 pb-8 space-y-6"
        data-live-refresh="true"
        data-live-url="{{ request()->url() }}"
        data-live-query="{{ http_build_query(request()->except('refresh')) }}"
        data-live-interval="8000"
        data-live-checksum="{{ $checksum }}"
    >
        @if (session('success'))
            <div class="flex items-start gap-3 rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-emerald-800 shadow-sm">
                <span class="mt-0.5 inline-flex h-10 w-10 items-center justify-center rounded-lg bg-white text-emerald-700 shadow-inner">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5" /></svg>
                </span>
                <div class="leading-relaxed">
                    <p class="text-sm font-semibold">Berhasil</p>
                    <p class="text-sm text-emerald-700/80">{{ session('success') }}</p>
                </div>
            </div>
        @endif
        @if (session('error'))
            <div class="flex items-start gap-3 rounded-xl border border-rose-100 bg-rose-50 px-4 py-3 text-rose-700 shadow-sm">
                <span class="mt-0.5 inline-flex h-10 w-10 items-center justify-center rounded-lg bg-white text-rose-600 shadow-inner">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8v4" /><path d="M12 16h.01" /><circle cx="12" cy="12" r="9" /></svg>
                </span>
                <div class="leading-relaxed">
                    <p class="text-sm font-semibold">Gagal</p>
                    <p class="text-sm">{{ session('error') }}</p>
                </div>
            </div>
        @endif

        @php
            $user = Auth::user();
            $activeTickets = ($openTickets ?? 0) + ($inProgressTickets ?? 0);
        @endphp

        <x-ui.section-hero
            pill="{{ __('messages.dashboard') }}"
            title="{{ __('messages.dashboard_title') }}"
            description="{{ __('messages.dashboard_desc') }}"
        >
            <x-slot:pillIcon>
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20v-6" /><path d="M6 20v-4" /><path d="M18 20v-8" /><path d="M3 13h18" /></svg>
            </x-slot:pillIcon>
            <x-slot:icon>
                <svg class="h-7 w-7 text-[#12824C]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1" /><rect x="14" y="3" width="7" height="7" rx="1" /><rect x="14" y="14" width="7" height="7" rx="1" /><rect x="3" y="14" width="7" height="7" rx="1" /></svg>
            </x-slot:icon>
        </x-ui.section-hero>

        <section class="space-y-3 mt-0">
            <div class="space-y-3 w-full">
                <div id="tour-stats">
                    <x-ui.stats-panel
                    title="{{ __('messages.stats_title') }}"
                    subtitle="{{ __('messages.stats_subtitle') }}"
                >
                    <div data-live-slot="dashboard-stats">
                        @include('dashboard.partials.stats', [
                            'totalTickets' => $totalTickets,
                            'openTickets' => $openTickets,
                            'inProgressTickets' => $inProgressTickets,
                            'resolvedTickets' => $resolvedTickets,
                        ])
                    </div>
                </x-ui.stats-panel>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 lg:gap-7 items-start px-0">
                    <div class="space-y-6 h-full">
                        <div id="tour-create-ticket" class="rounded-2xl border border-[#CFEADF] px-7 py-7 surface-card reveal-on-scroll reveal-delay-200 bg-gradient-to-br from-[#F6F9F8] via-white to-[#EDF3F2] space-y-4 h-full">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div class="space-y-2">
                                    <p class="heading-font text-xs font-semibold uppercase tracking-[0.45em] text-[#23455D]/70 flex items-center gap-2">
                                        <svg class="h-5 w-5 text-[#12824C]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14" /><path d="M5 12h14" /></svg>
                                        {{ __('messages.create_ticket_badge') }}
                                    </p>
                                    <p class="text-2xl font-semibold text-[#0C1F2C] leading-tight">{{ __('messages.create_ticket_title') }}</p>
                                </div>
                                <span class="badge-chip inline-flex items-center gap-2 rounded-full px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.3em] text-[#12824C] border border-[#C5E5D0] bg-gradient-to-r from-[#E9F7F0] to-[#F6FFFB] shadow-inner shadow-white/60 transition hover:-translate-y-0.5 hover:shadow-[0_12px_24px_rgba(18,130,76,0.18)]">
                                    <svg class="h-4 w-4 text-[#12824C]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" stroke-linecap="round">
                                        <path d="M13 2 3 14h9l-1 8 10-12h-9z" />
                                    </svg>
                                    {{ __('messages.create_ticket_btn_fast') }}
                                </span>
                            </div>
                            <div class="border-b border-slate-200 mt-6 mb-4"></div>
                            @php
                                $titleError = $errors->first('title');
                                $categoryError = $errors->first('category_id');
                                $departmentError = $errors->first('department_id');
                                $descriptionError = $errors->first('description');
                            @endphp
                            <form
                                method="POST"
                                action="{{ route('tickets.store') }}"
                                class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-6 w-full"
                                enctype="multipart/form-data"
                                data-ticket-form
                                novalidate
                            >
                                @csrf
                                <input type="hidden" name="idempotency_key" value="" data-idempotency-key>
                                <div class="md:col-span-2 space-y-2">
                                    <label class="text-[11px] font-medium text-gray-500">{{ __('messages.create_ticket_label_title') }}</label>
                                    <input
                                        name="title"
                                        value="{{ old('title') }}"
                                        required
                                        minlength="8"
                                        placeholder="{{ __('messages.create_ticket_placeholder_title') }}"
                                        class="w-full rounded-[12px] border border-slate-200 bg-white px-5 py-3 text-sm text-slate-900 placeholder:text-slate-400 transition focus:border-emerald-400 focus:ring-1 focus:ring-emerald-100 shadow-sm"
                                        data-validate-field="title"
                                    >
                                    <p class="text-xs text-rose-500 {{ $titleError ? '' : 'hidden' }}" data-field-error="title">
                                        {{ $titleError }}
                                    </p>
                                </div>

                                <div class="space-y-2">
                                    <label class="text-[11px] font-medium text-gray-500">{{ __('messages.create_ticket_label_category') }}</label>
                                    <div class="relative">
                                        <select
                                            name="category_id"
                                            required
                                            class="w-full rounded-[12px] border border-slate-200 bg-white bg-none px-5 py-3 pr-10 text-sm text-slate-900 placeholder:text-slate-400 transition appearance-none focus:border-emerald-400 focus:ring-1 focus:ring-emerald-100 shadow-sm {{ $categoryError ? 'border-rose-400 focus:border-rose-400 focus:ring-rose-100' : '' }}"
                                            data-validate-field="category_id"
                                        >
                                            <option value="" disabled @selected(! old('category_id'))>{{ __('messages.create_ticket_placeholder_category') }}</option>
                                            @foreach ($categories as $category)
                                                <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>
                                                    {{ $category->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <span class="pointer-events-none absolute inset-y-0 right-4 flex items-center text-slate-400">
                                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="m6 9 6 6 6-6" />
                                            </svg>
                                        </span>
                                    </div>
                                    <p class="text-xs text-rose-500 {{ $categoryError ? '' : 'hidden' }}" data-field-error="category_id">
                                        {{ $categoryError }}
                                    </p>
                                </div>

                                <div class="space-y-2">
                                    <label class="text-[11px] font-medium text-gray-500">{{ __('messages.create_ticket_label_department') }}</label>
                                    <select
                                        name="department_id"
                                        required
                                        class="w-full rounded-[12px] border border-slate-200 bg-white px-5 py-3 text-sm text-slate-900 placeholder:text-slate-400 transition focus:border-emerald-400 focus:ring-1 focus:ring-emerald-100 shadow-sm {{ $departmentError ? 'border-rose-400 focus:border-rose-400 focus:ring-rose-100' : '' }}"
                                        data-validate-field="department_id"
                                    >
                                        <option value="" disabled @selected(! old('department_id'))>{{ __('messages.create_ticket_placeholder_department') }}</option>
                                        @foreach ($departments as $department)
                                            <option value="{{ $department->id }}" @selected(old('department_id') == $department->id)>
                                                {{ $department->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                        <p class="text-xs text-rose-500 {{ $departmentError ? '' : 'hidden' }}" data-field-error="department_id">
                                        {{ $departmentError }}
                                    </p>
                                </div>

                                <div class="md:col-span-2 space-y-2">
                                    <label class="text-[11px] font-medium text-gray-500">{{ __('messages.priority') }}</label>
                                    <div class="relative">
                                        <select
                                            name="priority"
                                            required
                                            class="w-full rounded-[12px] border border-slate-200 bg-white bg-none px-5 py-3 pr-10 text-sm text-slate-900 placeholder:text-slate-400 transition appearance-none focus:border-emerald-400 focus:ring-1 focus:ring-emerald-100 shadow-sm"
                                        >
                                            <option value="low">{{ __('messages.priority_low') }}</option>
                                            <option value="medium" selected>{{ __('messages.priority_medium') }}</option>
                                            <option value="high">{{ __('messages.priority_high') }}</option>
                                            <option value="critical">{{ __('messages.priority_critical') }}</option>
                                        </select>
                                        <span class="pointer-events-none absolute inset-y-0 right-4 flex items-center text-slate-400">
                                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="m6 9 6 6 6-6" />
                                            </svg>
                                        </span>
                                    </div>
                                </div>

                                <div class="md:col-span-2 space-y-2">
                                    <label class="text-[11px] font-medium text-gray-500">{{ __('messages.create_ticket_label_desc') }}</label>
                                    <textarea
                                        name="description"
                                        class="w-full rounded-[12px] border border-slate-200 bg-white px-5 py-3 text-sm text-slate-900 placeholder:text-slate-400 transition focus:border-emerald-400 focus:ring-1 focus:ring-emerald-100 h-32 shadow-sm"
                                        required
                                        minlength="20"
                                        placeholder="{{ __('messages.create_ticket_placeholder_desc') }}"
                                        data-validate-field="description"
                                    >{{ old('description') }}</textarea>
                                    <p class="text-xs text-rose-500 {{ $descriptionError ? '' : 'hidden' }}" data-field-error="description">
                                        {{ $descriptionError }}
                                    </p>
                                </div>

                                <div class="md:col-span-2 space-y-2" data-file-preview>
                                    <label class="text-[11px] font-medium text-gray-500 inline-flex items-center gap-2">
                                        <svg class="h-4 w-4 text-[#118A58]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="m21.44 11.05-8.49 8.49a5 5 0 0 1-7.07-7.07l8.49-8.49a3 3 0 0 1 4.24 4.24l-8.49 8.49a1 1 0 0 1-1.41-1.41l7.78-7.78" />
                                        </svg>
                                        {{ __('messages.create_ticket_label_attachment') }}
                                    </label>
                                    <div class="rounded-[12px] border border-slate-200 bg-white px-5 py-5 text-sm text-slate-700 flex flex-col gap-3 shadow-sm" data-file-preview-wrapper data-dropzone>
                                        <input
                                            type="file"
                                            name="attachments[]"
                                            multiple
                                            class="block w-full rounded-[12px] border border-slate-200 bg-white px-5 py-3 text-sm text-slate-800 placeholder:text-slate-400 transition file:mr-3 file:rounded-full file:border-0 file:bg-emerald-600 file:px-4 file:py-2 file:text-[0.65rem] file:font-semibold file:uppercase file:tracking-[0.28em] file:text-white hover:file:bg-emerald-500 focus:border-emerald-400 focus:ring-1 focus:ring-emerald-100"
                                            data-file-preview-input
                                        >
                                        <p class="text-xs text-slate-500">{{ __('messages.create_ticket_attachment_desc') }}</p>
                                        <div class="flex flex-wrap gap-2" data-file-preview-list hidden></div>
                                    </div>
                                    @php $attachmentErrors = $errors->get('attachments.*'); @endphp
                                    @if (!empty($attachmentErrors))
                                        @foreach ($attachmentErrors as $messages)
                                            @foreach ($messages as $message)
                                                <p class="text-xs text-rose-500">{{ $message }}</p>
                                            @endforeach
                                        @endforeach
                                    @endif
                                </div>

                                <div class="md:col-span-2 flex justify-end pt-2">
                                    <button
                                        type="submit"
                                        class="btn-primary inline-flex items-center gap-2 rounded-2xl px-6 py-2.5 text-sm font-semibold text-white shadow-lg shadow-emerald-500/20 transition hover:-translate-y-0.5 hover:shadow-emerald-500/30"
                                        data-submit-btn
                                    >
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14" /><path d="M5 12h14" /></svg>
                                        <span data-submit-label>{{ __('messages.create_ticket_btn_submit') }}</span>
                                        <span class="hidden h-4 w-4 animate-spin rounded-full border-2 border-white/70 border-t-transparent" data-submit-spinner></span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <aside id="tour-history" class="w-full reveal-on-scroll reveal-delay-100 h-full" data-live-slot="dashboard-history">
                        @include('dashboard.partials.history', [
                            'recentTickets' => $recentTickets,
                            'totalTickets' => $totalTickets,
                            'isAdmin' => $isAdmin,
                        ])
                    </aside>
                </div>
            </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (!localStorage.getItem('zinus_tour_completed')) {
                const driverObj = window.driver.js.driver({
                    showProgress: true,
                    nextBtnText: 'Lanjut ›',
                    prevBtnText: '‹ Kembali',
                    doneBtnText: 'Selesai',
                    showButtons: ['next', 'previous', 'close'],
                    steps: [
                        {
                            element: '#tour-sidebar',
                            popover: {
                                title: 'Halo! Kenali Navigasi Utama 👋',
                                description: 'Di sebelah kiri ini rumah navigasi kamu. Kamu bisa mondar-mandir buat ngecek Dashboard, daftar Tiket yang udah kamu buat, atau ngurus pinjaman alat IT.',
                                side: "right",
                                align: 'start'
                            }
                        },
                        {
                            element: '#tour-stats',
                            popover: {
                                title: 'Pantau Progres Tiketmu 📊',
                                description: 'Nah, di sini kamu bisa pantau seberapa cepet IT nanganin laporanmu. Dari yang baru masuk (Open), lagi dikerjain (In Progress), sampe yang udah beres (Resolved).',
                                side: "bottom",
                                align: 'start'
                            }
                        },
                        {
                            element: '#tour-create-ticket',
                            popover: {
                                title: 'Ada Masalah IT? Lapor Sini Aja! 🛠️',
                                description: 'Punya PC lemot atau email nyangkut? Langsung tulis keluhan kamu di form ini sedetail mungkin, biar tim IT bisa langsung turun tangan ngebantu.',
                                side: "top",
                                align: 'start'
                            }
                        },
                        {
                            element: '#tour-history',
                            popover: {
                                title: 'Jejak Tiket Kamu 🕒',
                                description: 'Kalo penasaran sama riwayat atau detail dari semua tiket yang pernah kamu bikin sebelumnya, bisa langsung ditengok di panel sebelah kanan ini ya.',
                                side: "left",
                                align: 'start'
                            }
                        },
                        {
                            element: '#tour-chat-widget',
                            popover: {
                                title: 'Butuh Bantuan Mendesak? 🚀',
                                description: 'Kalo emang darurat banget dan nggak bisa nunggu tiket, klik tombol hijau di pojok kanan bawah ini buat langsung Ngobrol (Live Chat) bareng staf IT yang lagi online!',
                                side: "top",
                                align: 'end'
                            }
                        }
                    ],
                    onDestroyStarted: () => {
                        if (!driverObj.hasNextStep() || confirm("Yakin nih mau lewatin tur panduannya dulu?")) {
                            localStorage.setItem('zinus_tour_completed', 'true');
                            driverObj.destroy();
                        }
                    },
                });
                
                setTimeout(() => {
                    driverObj.drive();
                }, 800);
            }
        });
    </script>
    @endpush
</x-app-layout>
