<x-app-layout>
    <x-slot name="header">
        Detail Tiket
    </x-slot>

    @php
        $statusStyles = [
            'open' => [
                'note' => 'text-amber-600',
            ],
            'in_progress' => [
                'note' => 'text-sky-600',
            ],
            'resolved' => [
                'note' => 'text-brand-700',
            ],
            'closed' => [
                'note' => 'text-ink-600',
            ],
        ];

        $currentStatus = $ticket->status;
        $statusLabel = $statuses[$currentStatus] ?? \Illuminate\Support\Str::title(str_replace('_', ' ', $currentStatus));
        $statusTone = $statusStyles[$currentStatus]['note'] ?? 'text-ink-500';

        $timezone = config('app.timezone');
        $createdAt = $ticket->created_at?->timezone($timezone);
        $updatedAt = $ticket->updated_at?->timezone($timezone);

        $backRouteName = $isAdmin ? 'tickets.index' : 'tickets.mine';

        $createdDifferentFromUpdated = $createdAt && $updatedAt && ! $createdAt->equalTo($updatedAt);
    @endphp

    <div class="space-y-8">
        @if (session('ok'))
            <div class="rounded-2xl border border-brand-100 bg-brand-50 px-5 py-3 text-sm text-brand-800 shadow-sm shadow-brand-100/80">
                {{ session('ok') }}
            </div>
        @endif

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <x-ui.button
                href="{{ route($backRouteName) }}"
                variant="ghost"
                size="sm"
                icon='<svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M9.78 15.78a.75.75 0 0 1-1.06 0l-4.5-4.5a.75.75 0 0 1 0-1.06l4.5-4.5a.75.75 0 1 1 1.06 1.06L6.31 9.25H15a.75.75 0 0 1 0 1.5H6.31l3.47 3.47a.75.75 0 0 1 0 1.06Z" clip-rule="evenodd" /></svg>'
            >
                Kembali
            </x-ui.button>

            <div class="flex flex-wrap items-center gap-3 text-xs font-medium text-ink-500">
                @if ($createdAt)
                    <span>Dibuat {{ $createdAt->format('d M Y • H:i') }} WIB</span>
                @endif
                @if ($createdAt && $updatedAt)
                    <span class="text-ink-300">•</span>
                @endif
                @if ($updatedAt)
                    <span>Terakhir diperbarui {{ $updatedAt->diffForHumans() }}</span>
                @endif
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,3fr),minmax(0,2fr)]">
            <div class="space-y-6">
                <x-ui.panel>
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div class="space-y-2">
                            <div class="inline-flex items-center gap-2 rounded-full border border-brand-100 bg-brand-50 px-3 py-1 text-2xs font-semibold uppercase tracking-[0.24em] text-brand-800">
                                Tiket #{{ $ticket->id }}
                            </div>
                            <h1 class="text-2xl font-semibold leading-tight text-ink-900 sm:text-3xl">{{ $ticket->title }}</h1>
                            <p class="text-sm text-ink-500">
                                Dilaporkan oleh {{ optional($ticket->user)->name ?? 'User eksternal' }}
                                @if ($ticket->department)
                                    • Departemen {{ optional($ticket->department)->name }}
                                @endif
                            </p>
                        </div>
                        <x-ui.status-chip :status="$ticket->status" :label="$statusLabel" />
                    </div>

                    <div class="mt-4 flex flex-wrap items-stretch gap-4 text-sm text-ink-700">
                        <div class="rounded-2xl border border-ink-100 bg-ink-50/50 p-4 w-full sm:flex-1 sm:min-w-[200px]">
                            <p class="text-2xs font-semibold uppercase tracking-[0.22em] text-ink-500">Kategori</p>
                            <p class="mt-2 text-base font-semibold text-ink-900">{{ optional($ticket->category)->name ?? 'Tidak ada' }}</p>
                        </div>
                        <div class="rounded-2xl border border-ink-100 bg-ink-50/50 p-4 w-full sm:flex-1 sm:min-w-[200px]">
                            <p class="text-2xs font-semibold uppercase tracking-[0.22em] text-ink-500">Departemen</p>
                            <p class="mt-2 text-base font-semibold text-ink-900">{{ optional($ticket->department)->name ?? 'Tidak ada' }}</p>
                        </div>
                        <div class="rounded-2xl border border-ink-100 bg-ink-50/50 p-4 w-full sm:flex-1 sm:min-w-[200px]">
                            <p class="text-2xs font-semibold uppercase tracking-[0.22em] text-ink-500">Email Pelapor</p>
                            <p class="mt-2 text-sm font-semibold text-ink-900 break-words">{{ optional($ticket->user)->email ?? 'Tidak terdaftar' }}</p>
                        </div>
                        <div class="rounded-2xl border border-ink-100 bg-ink-50/50 p-4 w-full sm:flex-1 sm:min-w-[160px]">
                            <p class="text-2xs font-semibold uppercase tracking-[0.22em] text-ink-500">Lampiran</p>
                            <p class="mt-2 text-base font-semibold text-ink-900">{{ $ticket->attachments_count }} file</p>
                        </div>
                    </div>
                </x-ui.panel>

                <x-ui.panel title="Deskripsi Permasalahan" subtitle="Detail laporan dari pelapor, ditampilkan persis seperti yang mereka kirimkan.">
                    <div class="text-sm leading-relaxed text-ink-700">
                        {!! nl2br(e($ticket->description)) !!}
                    </div>
                </x-ui.panel>
            </div>

            <div class="space-y-6">
                <x-ui.panel title="Status & Tindakan" subtitle="Pantau progres tiket dan update status jika perlu.">
                    <div class="space-y-4 text-sm text-ink-600">
                        <div class="flex flex-wrap items-center gap-3">
                            <x-ui.status-chip :status="$ticket->status" :label="$statusLabel" />
                            @if ($updatedAt)
                                <span class="text-xs text-ink-400">Update {{ $updatedAt->format('d M Y • H:i') }} WIB</span>
                            @endif
                        </div>

                        @if ($isAdmin)
                            <form class="space-y-3" method="POST" action="{{ route('tickets.updateStatus', $ticket) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="redirect_to" value="{{ route('tickets.show', $ticket) }}">
                                <label class="block text-2xs font-semibold uppercase tracking-[0.22em] text-ink-500" for="status">
                                    Ubah status tiket
                                </label>
                                <select
                                    id="status"
                                    name="status"
                                    class="w-full rounded-xl border border-ink-200 bg-white px-4 py-2.5 text-sm font-medium text-ink-700 focus:border-brand-300 focus:ring focus:ring-brand-200/50"
                                >
                                    @foreach ($statuses as $value => $label)
                                        <option value="{{ $value }}" @selected($ticket->status === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <x-ui.button type="submit" size="lg" class="w-full">
                                    Simpan Perubahan
                                </x-ui.button>
                            </form>
                            <p class="text-xs {{ $statusTone }}">Perubahan status otomatis mengirimkan notifikasi ke tim terkait.</p>
                        @else
                            <p class="text-sm text-ink-500">
                                Status tiket akan diperbarui oleh tim IT. Kamu akan menerima pemberitahuan ketika ada perubahan.
                            </p>
                        @endif
                    </div>
                </x-ui.panel>

                <x-ui.panel title="Riwayat Perubahan Status" subtitle="Catatan perubahan status terbaru untuk tiket ini.">
                    @if ($statusLogs->isNotEmpty())
                        <ul class="space-y-4 text-sm text-ink-700">
                            @foreach ($statusLogs as $log)
                                @php
                                    $actorName = $log->user?->name ?? 'System';
                                    $actorEmail = $log->user?->email;
                                    $oldLabel = $statuses[$log->old_value] ?? \Illuminate\Support\Str::title(str_replace('_', ' ', $log->old_value ?? ''));
                                    $newLabel = $statuses[$log->new_value] ?? \Illuminate\Support\Str::title(str_replace('_', ' ', $log->new_value ?? ''));
                                    $loggedAt = $log->created_at?->timezone($timezone);
                                @endphp
                                <li class="rounded-2xl border border-ink-100 bg-white px-4 py-3 shadow-sm shadow-ink-100/60">
                                    <div class="flex flex-wrap items-center justify-between gap-2 text-xs text-ink-400">
                                        <span>
                                            Admin: {{ $actorName }}@if ($actorEmail) <span class="text-ink-300">({{ $actorEmail }})</span>@endif
                                        </span>
                                        <span>{{ $loggedAt?->format('d M Y • H:i') }} WIB</span>
                                    </div>
                                    <div class="mt-2 flex flex-wrap items-center gap-2">
                                        <span class="inline-flex items-center rounded-full bg-ink-50 px-2.5 py-1 text-2xs font-semibold uppercase tracking-[0.2em] text-ink-500">
                                            {{ $oldLabel }}
                                        </span>
                                        <span class="text-xs font-semibold text-ink-300">-&gt;</span>
                                        <span class="inline-flex items-center rounded-full bg-brand-50 px-2.5 py-1 text-2xs font-semibold uppercase tracking-[0.2em] text-brand-700">
                                            {{ $newLabel }}
                                        </span>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-sm text-ink-500">Belum ada perubahan status.</p>
                    @endif
                </x-ui.panel>

                <x-ui.panel title="Ringkasan Tiket">
                    <dl class="space-y-4 text-sm text-ink-700">
                        <div class="flex items-start justify-between gap-4">
                            <dt class="text-ink-500">Pelapor</dt>
                            <dd class="text-right text-ink-900">
                                <div>{{ optional($ticket->user)->name ?? 'User eksternal' }}</div>
                                <div class="text-xs text-ink-400">{{ optional($ticket->user)->email ?? 'Tidak terdaftar' }}</div>
                            </dd>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-ink-500">Kategori</dt>
                            <dd class="text-right font-medium text-ink-900">{{ optional($ticket->category)->name ?? 'Tidak ada' }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-ink-500">Departemen</dt>
                            <dd class="text-right font-medium text-ink-900">{{ optional($ticket->department)->name ?? 'Tidak ada' }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-ink-500">Dibuat</dt>
                            <dd class="text-right text-ink-900">{{ $createdAt?->format('d M Y • H:i') }} WIB</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-ink-500">Terakhir diperbarui</dt>
                            <dd class="text-right text-ink-900">{{ $updatedAt?->format('d M Y • H:i') }} WIB</dd>
                        </div>
                    </dl>
                </x-ui.panel>

                <x-ui.panel title="Timeline Progress">
                    <x-ticket-timeline :logs="$logs" />
                </x-ui.panel>

                <x-ui.panel title="Lampiran" subtitle="Unduh file pendukung dari pelapor.">
                    @if ($ticket->attachments_count > 0)
                        <ul class="space-y-3">
                            @foreach ($ticket->attachments as $attachment)
                                @php
                                    $extension = \Illuminate\Support\Str::lower(pathinfo($attachment->original_name, PATHINFO_EXTENSION) ?: 'file');
                                    $extensionBadge = match ($extension) {
                                        'pdf' => 'bg-rose-100 text-rose-700',
                                        'xls', 'xlsx' => 'bg-brand-50 text-brand-800 border border-brand-100',
                                        'doc', 'docx' => 'bg-brand-50 text-brand-800 border border-brand-100',
                                        'zip', 'rar' => 'bg-ink-100 text-ink-700',
                                        'jpg', 'jpeg', 'png' => 'bg-amber-50 text-amber-700 border border-amber-100',
                                        default => 'bg-ink-50 text-ink-700',
                                    };

                                    $size = (int) ($attachment->file_size ?? 0);
                                    $units = ['B', 'KB', 'MB', 'GB'];
                                    $sizeIndex = 0;

                                    while ($size >= 1024 && $sizeIndex < count($units) - 1) {
                                        $size /= 1024;
                                        $sizeIndex++;
                                    }

                                    $formattedSize = $sizeIndex === 0 ? $size . ' ' . $units[$sizeIndex] : number_format($size, 1) . ' ' . $units[$sizeIndex];
                                @endphp

                                <li class="flex items-center justify-between gap-4 rounded-2xl border border-ink-100 px-4 py-3 shadow-sm shadow-ink-100/60">
                                    <div class="flex items-center gap-3">
                                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl {{ $extensionBadge }} text-xs font-bold uppercase">
                                            {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::limit($extension, 4, '')) }}
                                        </span>
                                        <div>
                                            <p class="text-sm font-semibold text-ink-900">{{ $attachment->original_name }}</p>
                                            <p class="text-xs text-ink-400">{{ $formattedSize }}</p>
                                        </div>
                                    </div>
                                    <x-ui.button
                                        href="{{ route('tickets.attachments.download', [$ticket, $attachment]) }}"
                                        size="sm"
                                        variant="ghost"
                                        iconPosition="right"
                                        icon='<svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 3.25a.75.75 0 0 1 .75.75v6.614l1.97-1.97a.75.75 0 1 1 1.06 1.061l-3.25 3.25a.75.75 0 0 1-1.06 0l-3.25-3.25a.75.75 0 0 1 1.06-1.06l1.97 1.97V4a.75.75 0 0 1 .75-.75Z" clip-rule="evenodd" /><path d="M3.75 12.5a.75.75 0 0 1 .75.75v1.25h11v-1.25a.75.75 0 0 1 1.5 0v2a.75.75 0 0 1-.75.75h-12a.75.75 0 0 1-.75-.75v-2a.75.75 0 0 1 .75-.75Z" /></svg>'
                                    >
                                        Download
                                    </x-ui.button>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-sm font-light text-ink-500">Tidak ada lampiran yang disertakan pada tiket ini.</p>
                    @endif
                </x-ui.panel>

                <x-ui.panel title="Aktivitas Tiket" subtitle="Chronology singkat perubahan tiket.">
                    <ul class="space-y-5 text-sm text-ink-700">
                        <li class="flex items-start gap-3">
                            <span class="mt-1.5 h-2.5 w-2.5 rounded-full bg-brand-500"></span>
                            <div>
                                <p class="font-semibold text-ink-900">Tiket dibuat</p>
                                <p class="text-xs text-ink-400">{{ $createdAt?->format('d M Y • H:i') }} WIB oleh {{ optional($ticket->user)->name ?? 'User eksternal' }}</p>
                            </div>
                        </li>
                        @if ($createdDifferentFromUpdated)
                            <li class="flex items-start gap-3">
                                <span class="mt-1.5 h-2.5 w-2.5 rounded-full bg-brand-400"></span>
                                <div>
                                    <p class="font-semibold text-ink-900">Status diperbarui</p>
                                    <p class="text-xs text-ink-400">
                                        {{ $updatedAt?->format('d M Y • H:i') }} WIB — sekarang {{ $statusLabel }}
                                    </p>
                                </div>
                            </li>
                        @endif
                    </ul>
                </x-ui.panel>
            </div>
        </div>
    </div>
</x-app-layout>
