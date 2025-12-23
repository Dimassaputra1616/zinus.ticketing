@php
    use Illuminate\Support\Str;
@endphp

<div class="space-y-6 pb-6">
    <div class="flex flex-col gap-4 border-b border-ink-100 pb-4 md:flex-row md:items-center md:justify-between">
        <div class="space-y-1">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-700">Tiket kamu</p>
            <h3 class="text-lg font-semibold text-gray-700">Riwayat Tiket Kamu</h3>
            <p class="text-sm text-gray-500">Semua tiket yang kamu buat akan muncul di sini.</p>
        </div>

        <div class="flex flex-col gap-3 md:items-end">
            <div class="flex flex-wrap items-center gap-2 overflow-x-auto text-2xs font-semibold uppercase tracking-[0.22em] -mx-1 px-1">
                @php $isAll = empty($statusFilter); @endphp
                <a
                    href="{{ route('tickets.mine') }}"
                    class="rounded-full border px-3 py-1.5 transition hover:-translate-y-[1px] hover:border-emerald-200 hover:bg-emerald-50 hover:shadow-sm {{ $isAll ? 'border-emerald-500 bg-emerald-50 text-emerald-700 shadow-sm' : 'border-ink-100 bg-ink-50 text-ink-600' }}"
                >
                    Semua
                </a>
                @foreach ($statuses as $key => $label)
                    @php $active = $statusFilter === $key; @endphp
                    <a
                        href="{{ route('tickets.mine', ['status' => $key]) }}"
                        class="rounded-full border px-3 py-1.5 capitalize transition hover:-translate-y-[1px] hover:border-emerald-200 hover:bg-emerald-50 hover:shadow-sm {{ $active ? 'border-emerald-500 bg-emerald-50 text-emerald-700 shadow-sm' : 'border-ink-100 bg-ink-50 text-ink-600' }}"
                    >
                        {{ str_replace('_', ' ', $key) }}
                        <span class="ms-2 text-[0.68rem] font-bold {{ $active ? 'text-emerald-600' : 'text-ink-400' }}">{{ $statusCounts[$key] ?? 0 }}</span>
                    </a>
                @endforeach
            </div>

            <x-ui.button
                href="{{ route('dashboard') }}#buat-tiket"
                size="sm"
                variant="primary"
                class="btn-animate w-full justify-center md:w-auto md:ml-4"
                icon='<svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10 3a1 1 0 0 1 1 1v5h5a1 1 0 1 1 0 2h-5v5a1 1 0 1 1-2 0v-5H4a1 1 0 0 1 0-2h5V4a1 1 0 0 1 1-1Z" /></svg>'
            >
                Buat Tiket Baru
            </x-ui.button>
        </div>
    </div>

    <div class="space-y-5 lg:hidden">
        @forelse ($tickets as $ticket)
            <article class="rounded-[16px] border border-ink-100 bg-white/95 p-4 shadow-[0_3px_14px_rgba(0,0,0,0.04)] transition hover:-translate-y-0.5 hover:shadow-[0_8px_22px_rgba(18,130,76,0.08)] hover:border-brand-200">
                <div class="flex flex-col gap-3">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="text-[0.68rem] font-semibold uppercase tracking-[0.32em] text-ink-400">Tiket</p>
                            <a
                                href="{{ route('tickets.show', $ticket) }}"
                                class="text-base font-semibold text-ink-900 transition hover:text-brand-700 break-words"
                            >
                                #{{ $ticket->id }} • {{ $ticket->title }}
                            </a>
                        </div>
                        <x-ui.status-chip :status="$ticket->status" class="px-2.5 py-1 text-[0.65rem] tracking-[0.18em]" />
                    </div>

                    <p class="text-[11px] leading-relaxed text-ink-500 overflow-hidden text-ellipsis break-words" style="-webkit-line-clamp: 2; display: -webkit-box; -webkit-box-orient: vertical;">
                        {{ Str::limit(strip_tags($ticket->description), 160) }}
                    </p>

                    <a
                        href="{{ route('tickets.show', $ticket) }}"
                        class="inline-flex w-full items-center justify-between rounded-xl border border-ink-100 bg-gradient-to-r from-white to-ink-50 px-4 py-2 text-sm font-semibold text-ink-700 shadow-sm transition hover:-translate-y-0.5 hover:border-brand-200 hover:from-brand-50/60 hover:text-brand-800"
                    >
                        <span>Lihat detail tiket</span>
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-brand-50 text-brand-700">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M9.78 15.78a.75.75 0 0 1-1.06 0l-4.5-4.5a.75.75 0 0 1 0-1.06l4.5-4.5a.75.75 0 1 1 1.06 1.06L6.31 9.25H15a.75.75 0 0 1 0 1.5H6.31l3.47 3.47a.75.75 0 0 1 0 1.06Z" clip-rule="evenodd" />
                            </svg>
                        </span>
                    </a>

                    <dl class="grid gap-3 text-xs text-ink-600 sm:grid-cols-2">
                        <div class="space-y-1">
                            <dt class="font-semibold text-ink-500">Kategori</dt>
                            <dd>{{ optional($ticket->category)->name ?? 'Tidak ada' }}</dd>
                        </div>
                        <div class="space-y-1">
                            <dt class="font-semibold text-ink-500">Departemen</dt>
                            <dd>{{ optional($ticket->department)->name ?? 'Tidak ada' }}</dd>
                        </div>
                        <div class="space-y-1">
                            <dt class="font-semibold text-ink-500">Dibuat</dt>
                            <dd>
                                <div>{{ $ticket->created_at->format('d M Y') }}</div>
                                <div class="text-[0.7rem] text-ink-400">{{ $ticket->created_at->format('H:i') }} WIB</div>
                            </dd>
                        </div>
                        <div class="space-y-1">
                            <dt class="font-semibold text-ink-500">Lampiran</dt>
                            <dd>
                                @if ($ticket->attachments_count > 0)
                                    <div class="flex flex-wrap gap-2">
                                        @foreach ($ticket->attachments as $attachment)
                                            <a
                                                href="{{ route('tickets.attachments.download', [$ticket, $attachment]) }}"
                                                class="inline-flex items-center gap-2 rounded-full border border-ink-200 bg-ink-50 px-3 py-1 text-[0.7rem] font-semibold text-ink-700 transition hover:border-brand-200 hover:bg-brand-50"
                                            >
                                                <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                    <path fill-rule="evenodd" d="M2.5 5.75A2.75 2.75 0 0 1 5.25 3h3a.75.75 0 0 1 0 1.5h-3A1.25 1.25 0 0 0 4 5.75v8.5A1.25 1.25 0 0 0 5.25 15.5h9.5A1.25 1.25 0 0 0 16 14.25v-8.5A1.25 1.25 0 0 0 14.75 4.5h-3a.75.75 0 0 1 0-1.5h3A2.75 2.75 0 0 1 17.5 5.75v8.5A2.75 2.75 0 0 1 14.75 17h-9.5A2.75 2.75 0 0 1 2.5 14.25v-8.5Zm6.53 2.22a.75.75 0 1 0-1.06 1.06l1.22 1.22H7a.75.75 0 0 0 0 1.5h2.19l-1.22 1.22a.75.75 0 0 0 1.06 1.06l2.5-2.5a.75.75 0 0 0 0-1.06l-2.5-2.5Zm2.72 1.28a.75.75 0 0 0 0 1.5h1a.75.75 0 0 0 0-1.5h-1Zm0 2a.75.75 0 0 0 0 1.5h1a.75.75 0 0 0 0-1.5h-1Z" clip-rule="evenodd" />
                                                <span class="max-w-[140px] truncate align-middle">{{ $attachment->original_name }}</span>
                                            </a>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-[0.7rem] text-ink-400">-</span>
                                @endif
                            </dd>
                        </div>
                    </dl>
                </div>
            </article>
        @empty
            <div class="rounded-2xl border border-ink-100 bg-ink-50/60 px-6 py-8 text-center text-sm text-ink-500 shadow-sm space-y-3">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-white text-ink-400 shadow-inner shadow-ink-100">
                    <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M7 11h10M7 15h6" />
                        <path d="M4 5a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v14l-4-3-4 3-4-3-4 3z" />
                    </svg>
                </div>
                <div class="space-y-1">
                    <p class="text-base font-semibold text-ink-800">Belum ada tiket untuk ditampilkan</p>
                    <p class="text-sm text-ink-500">Buat tiket baru atau ubah filter untuk melihat riwayatmu.</p>
                </div>
            </div>
        @endforelse
    </div>

    <div class="hidden lg:block space-y-3">
        <div class="grid grid-cols-[35%_15%_15%_15%_20%] items-center gap-3 px-1 text-[11px] uppercase tracking-[0.2em] text-ink-500">
            <span class="text-left">Tiket</span>
            <span class="text-center">Kategori</span>
            <span class="text-center -ml-1">Departemen</span>
            <span class="text-center px-3">Status</span>
            <span class="text-center pr-5">Dibuat</span>
        </div>

        <div class="divide-y divide-gray-100/80 space-y-2">
            @forelse ($tickets as $ticket)
                <a
                    href="{{ route('tickets.show', $ticket) }}"
                    class="group grid grid-cols-[35%_15%_15%_15%_20%] items-center gap-3 rounded-xl border border-ink-100 bg-white/95 px-4 py-3 shadow-sm transition hover:-translate-y-0.5 hover:bg-[#F7FAF9] hover:border-brand-200 focus:outline-none focus:ring-2 focus:ring-brand-200"
                >
                    <div class="space-y-1 min-w-0">
                        <div class="truncate text-sm font-semibold text-ink-900 transition group-hover:text-brand-700 break-words">
                            #{{ $ticket->id }} • {{ $ticket->title }}
                        </div>
                        <p class="text-[11px] text-ink-400 overflow-hidden text-ellipsis break-words" style="-webkit-line-clamp: 1; display: -webkit-box; -webkit-box-orient: vertical; max-width: 95%;">
                            {{ Str::limit(strip_tags($ticket->description), 140) }}
                        </p>
                    </div>
                    <div class="flex justify-center">
                        <span class="inline-flex items-center rounded-full bg-ink-50 px-3 py-1 text-xs font-medium text-ink-600 group-hover:bg-brand-50 group-hover:text-brand-700">
                            {{ optional($ticket->category)->name ?? 'Tidak ada' }}
                        </span>
                    </div>
                    <div class="flex justify-center -ml-1">
                        <span class="inline-flex items-center rounded-full bg-ink-50 px-3 py-1 text-xs font-medium text-ink-600 group-hover:bg-brand-50 group-hover:text-brand-700">
                            {{ optional($ticket->department)->name ?? 'Tidak ada' }}
                        </span>
                    </div>
                    <div class="flex items-center justify-center gap-2 px-3 text-center">
                        <x-ui.status-chip :status="$ticket->status" class="px-2.5 py-1 text-[0.65rem] tracking-[0.18em]" data-status-tooltip />
                    </div>
                    <div class="text-center text-sm text-ink-600 pl-4 pr-5">
                        <div>{{ $ticket->created_at->format('d M Y') }}</div>
                        <div class="text-xs text-ink-400">{{ $ticket->created_at->format('H:i') }} WIB</div>
                    </div>
                </a>
            @empty
                <div class="mx-auto flex max-w-sm flex-col items-center gap-3 rounded-2xl border border-ink-100 bg-ink-50/60 px-6 py-6 text-center">
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-white text-ink-400 shadow-inner shadow-ink-100">
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path d="M10 3a7 7 0 1 0 7 7a7 7 0 0 0-7-7m0 1.5a.75.75 0 0 1 .75.75v4a.75.75 0 0 1-.22.53l-2.5 2.5a.75.75 0 0 1-1.06-1.06L9.25 9.94V5.25A.75.75 0 0 1 10 4.5" />
                        </svg>
                    </span>
                    <div class="text-sm font-semibold text-ink-700">Belum ada tiket yang kamu buat, klik tombol buat tiket baru.</div>
                    <p class="text-xs text-ink-500">Ubah filter atau buat tiket baru untuk memantau progresmu.</p>
                </div>
            @endforelse
        </div>
    </div>

    @if ($tickets->hasPages())
        <div class="border-t border-ink-100 pt-4">
            {{ $tickets->onEachSide(1)->links() }}
        </div>
    @endif
</div>
