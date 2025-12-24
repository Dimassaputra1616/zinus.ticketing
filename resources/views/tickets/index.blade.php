<x-app-layout>
    <div
        class="w-full pt-4 sm:pt-6 pb-8 space-y-7"
        data-live-refresh="true"
        data-live-url="{{ request()->url() }}"
        data-live-query="{{ http_build_query(request()->except('refresh')) }}"
        data-live-interval="10000"
        data-live-checksum="{{ $checksum }}"
    >
        @php
            $user = Auth::user();
            $closedTickets = ($statusCounts['resolved'] ?? 0) + ($statusCounts['closed'] ?? 0);
        @endphp

        <x-ui.section-hero
            pill="Dashboard Ticketing"
            title="Monitoring Tiket IT"
            description="Pantau progres tim secara menyeluruh dan pastikan setiap permintaan tertangani dengan baik."
        >
            <x-slot:pillIcon>
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 20v-6" />
                    <path d="M6 20v-4" />
                    <path d="M18 20v-8" />
                    <path d="M3 13h18" />
                </svg>
            </x-slot:pillIcon>
            <x-slot:icon>
                <svg class="h-7 w-7 text-[#12824C]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="7" height="7" rx="1" />
                    <rect x="14" y="3" width="7" height="7" rx="1" />
                    <rect x="14" y="14" width="7" height="7" rx="1" />
                    <rect x="3" y="14" width="7" height="7" rx="1" />
                </svg>
            </x-slot:icon>
        </x-ui.section-hero>

        @if(session('ok'))
            <div class="rounded-2xl border border-[#00bfa5]/30 bg-[#00bfa5]/10 px-6 py-4 text-[#007a6a] shadow-sm shadow-[0_10px_25px_-18px_rgba(0,191,165,0.4)]">
                {{ session('ok') }}
            </div>
        @endif

        <section class="space-y-6 mt-0">
            <div class="space-y-4 w-full">
                <div data-live-slot="ticket-summary">
                    @include('tickets.partials.summary', [
                        'statuses' => $statuses,
                        'statusCounts' => $statusCounts,
                        'totalTickets' => $totalTickets,
                    ])
                </div>
            </div>

            <div data-live-slot="ticket-table">
                @include('tickets.partials.table', [
                    'tickets' => $tickets,
                    'statuses' => $statuses,
                    'statusCounts' => $statusCounts,
                    'statusFilter' => $statusFilter,
                    'departments' => $departments,
                    'departmentFilter' => $departmentFilter,
                    'searchTerm' => $searchTerm,
                    'startDate' => $startDate ?? null,
                    'endDate' => $endDate ?? null,
                ])
            </div>
        </section>
    </div>
</x-app-layout>
