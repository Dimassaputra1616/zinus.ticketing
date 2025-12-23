<x-ui.ticket-list
    :tickets="$recentTickets->take(7)"
    title="Riwayat Tiket"
    :subtitle="min($totalTickets, 7) . ' tiket terakhir yang ' . (($isAdmin ?? false) ? 'masuk' : 'kamu buat')"
    :total="$totalTickets"
    :is-admin="(bool) ($isAdmin ?? false)"
    icon='<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M8 6h13" /><path d="M8 12h13" /><path d="M8 18h13" /><path d="M3 6h.01" /><path d="M3 12h.01" /><path d="M3 18h.01" /></svg>'
    class="min-h-[280px] h-full surface-card bg-gradient-to-br from-[#F6F9F8] via-white to-[#EDF3F2]"
    :view-all-url="route('tickets.index')"
/>
