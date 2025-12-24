<x-app-layout>
    <div
        class="w-full pt-4 sm:pt-6 pb-8 space-y-8"
        data-live-refresh="true"
        data-live-url="{{ request()->url() }}"
        data-live-query="{{ http_build_query(request()->except('refresh')) }}"
        data-live-interval="10000"
        data-live-checksum="{{ $checksum }}"
    >
        <x-ui.section-hero
            pill="Tiket Saya"
            title="Tiket Saya"
            description="Pantau dan kelola seluruh tiket yang kamu buat di sistem ini."
        >
            <x-slot:pillIcon>
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 7h18" />
                    <path d="M3 12h18" />
                    <path d="M3 17h18" />
                    <path d="M7 7v10" />
                    <path d="M12 7v10" />
                    <path d="M17 7v10" />
                </svg>
            </x-slot:pillIcon>
            <x-slot:icon>
                <svg class="h-7 w-7 text-[#12824C]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="4" width="18" height="5" rx="1" />
                    <rect x="3" y="11" width="18" height="5" rx="1" />
                    <rect x="3" y="18" width="10" height="2" rx="1" />
                </svg>
            </x-slot:icon>
        </x-ui.section-hero>

        <section class="space-y-3 mt-0">
            <div class="space-y-4 w-full">
                <x-ui.stats-panel
                    title="Ringkasan Tiket"
                    subtitle="Statistik cepat tiket yang kamu buat."
                >
                    <div data-live-slot="my-ticket-cards">
                        @include('tickets.partials.mine-cards', [
                            'totalTickets' => $totalTickets,
                            'statusCounts' => $statusCounts,
                        ])
                    </div>
                </x-ui.stats-panel>
            </div>

            <x-ui.panel class="shadow-md border-ink-100/80 bg-gradient-to-br from-[#F6F9F8] via-white to-[#EDF3F2] surface-card">
                <div data-live-slot="my-ticket-table">
                    @include('tickets.partials.mine-table', [
                        'tickets' => $tickets,
                        'statuses' => $statuses,
                        'statusFilter' => $statusFilter,
                        'statusCounts' => $statusCounts,
                    ])
                </div>
            </x-ui.panel>
        </section>
    </div>
</x-app-layout>
