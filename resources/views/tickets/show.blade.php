<x-app-layout>
    <x-slot name="header">
        {{ __('messages.title_ticket_detail') }}
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

        $reporterName = $ticket->user?->name ?? $ticket->reporter_name;
        $reporterEmail = $ticket->user?->email ?? $ticket->reporter_email;
        $reporterMissing = ! $reporterName && ! $reporterEmail;
        $reporterDisplayName = $reporterName ?? $reporterEmail ?? __('messages.unregistered');
        $reporterDisplayEmail = $reporterEmail ?? ($reporterMissing ? __('messages.unregistered') : '—');
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
                {{ __('messages.back') }}
            </x-ui.button>

            <div class="flex flex-wrap items-center gap-3 text-xs font-medium text-ink-500">
                @if ($createdAt)
                    <span>{{ __('messages.created_at') }} {{ $createdAt->format('d M Y • H:i') }} {{ __('messages.time_wib') }}</span>
                @endif
                @if ($createdAt && $updatedAt)
                    <span class="text-ink-300">•</span>
                @endif
                @if ($updatedAt)
                    <span>{{ __('messages.updated_at') }} {{ $updatedAt->diffForHumans() }}</span>
                @endif
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,3fr),minmax(0,2fr)]">
            <div class="space-y-6">
                <x-ui.panel>
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div class="space-y-2">
                            <div class="inline-flex items-center gap-2 rounded-full border border-brand-100 bg-brand-50 px-3 py-1 text-2xs font-semibold uppercase tracking-[0.24em] text-brand-800">
                                {{ __('messages.ticket_number', ['id' => $ticket->id]) }}
                            </div>
                            <h1 class="text-2xl font-semibold leading-tight text-ink-900 sm:text-3xl">{{ $ticket->title }}</h1>
                            <p class="text-sm text-ink-500">
                                {{ __('messages.reported_by', ['name' => $reporterDisplayName]) }}
                                @if ($ticket->department)
                                    • {{ __('messages.department') }} {{ optional($ticket->department)->name }}
                                @endif
                            </p>
                        </div>
                        <x-ui.status-chip :status="$ticket->status" :label="$statusLabel" />
                    </div>

                    <div class="mt-4 flex flex-wrap items-stretch gap-4 text-sm text-ink-700">
                        <div class="rounded-2xl border border-ink-100 bg-ink-50/50 p-4 w-full sm:flex-1 sm:min-w-[200px]">
                            <p class="text-2xs font-semibold uppercase tracking-[0.22em] text-ink-500">{{ __('messages.category') }}</p>
                            <p class="mt-2 text-base font-semibold text-ink-900">{{ optional($ticket->category)->name ?? __('messages.none') }}</p>
                        </div>
                        <div class="rounded-2xl border border-ink-100 bg-ink-50/50 p-4 w-full sm:flex-1 sm:min-w-[200px]">
                            <p class="text-2xs font-semibold uppercase tracking-[0.22em] text-ink-500">{{ __('messages.department') }}</p>
                            <p class="mt-2 text-base font-semibold text-ink-900">{{ optional($ticket->department)->name ?? __('messages.none') }}</p>
                        </div>
                        <div class="rounded-2xl border border-ink-100 bg-ink-50/50 p-4 w-full sm:flex-1 sm:min-w-[200px]">
                            <p class="text-2xs font-semibold uppercase tracking-[0.22em] text-ink-500">{{ __('messages.reporter_email') }}</p>
                            <p class="mt-2 text-sm font-semibold text-ink-900 break-words">{{ $reporterDisplayEmail }}</p>
                        </div>
                        <div class="rounded-2xl border border-ink-100 bg-ink-50/50 p-4 w-full sm:flex-1 sm:min-w-[160px]">
                            <p class="text-2xs font-semibold uppercase tracking-[0.22em] text-ink-500">{{ __('messages.priority') }}</p>
                            @php
                                $priorityColors = [
                                    'low' => 'text-slate-700 bg-slate-100 border-slate-200',
                                    'medium' => 'text-amber-700 bg-amber-100 border-amber-200',
                                    'high' => 'text-orange-700 bg-orange-100 border-orange-200',
                                    'critical' => 'text-rose-700 bg-rose-100 border-rose-200 font-bold',
                                ];
                                $pColor = $priorityColors[$ticket->priority] ?? $priorityColors['medium'];
                            @endphp
                            <p class="mt-2 text-sm font-semibold">
                                <span class="inline-flex items-center gap-1 rounded px-2 py-0.5 text-xs uppercase tracking-widest border {{ $pColor }}">
                                    {{ __('messages.priority_' . $ticket->priority) }}
                                </span>
                            </p>
                        </div>
                        <div class="rounded-2xl border border-ink-100 bg-ink-50/50 p-4 w-full sm:flex-1 sm:min-w-[160px]">
                            <p class="text-2xs font-semibold uppercase tracking-[0.22em] text-ink-500">{{ __('messages.attachment') }}</p>
                            <p class="mt-2 text-base font-semibold text-ink-900">{{ $ticket->attachments_count }} file</p>
                        </div>
                    </div>
                </x-ui.panel>

                <x-ui.panel title="{{ __('messages.problem_description') }}" subtitle="{{ __('messages.problem_description_sub') }}">
                    <div class="text-sm leading-relaxed text-ink-700">
                        {!! nl2br(e($ticket->description)) !!}
                    </div>
                </x-ui.panel>
            </div>

            <div class="space-y-6">
                <x-ui.panel title="{{ __('messages.status_and_action') }}" subtitle="{{ __('messages.status_and_action_sub') }}">
                    <div class="space-y-4 text-sm text-ink-600">
                        <div class="flex flex-wrap items-center gap-3">
                            <x-ui.status-chip :status="$ticket->status" :label="$statusLabel" />
                            @if ($updatedAt)
                                <span class="text-xs text-ink-400">Update {{ $updatedAt->format('d M Y • H:i') }} {{ __('messages.time_wib') }}</span>
                            @endif
                        </div>

                        @if ($isAdmin)
                            <form class="space-y-3" method="POST" action="{{ route('tickets.updateStatus', $ticket) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="redirect_to" value="{{ route('tickets.show', $ticket) }}">
                                <label class="block text-2xs font-semibold uppercase tracking-[0.22em] text-ink-500" for="status">
                                    {{ __('messages.change_ticket_status') }}
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
                                    {{ __('messages.save_changes') }}
                                </x-ui.button>
                            </form>
                            <p class="text-xs {{ $statusTone }}">{{ __('messages.notify_status_update') }}</p>
                        @else
                            <p class="text-sm text-ink-500">
                                {{ __('messages.waiting_it_update') }}
                            </p>
                        @endif
                    </div>
                </x-ui.panel>

                <x-ui.panel title="{{ __('messages.status_history') }}" subtitle="{{ __('messages.status_history_sub') }}">
                    @if ($statusLogs->isNotEmpty())
                        <ul class="space-y-4 text-sm text-ink-700">
                            @foreach ($statusLogs as $log)
                                @php
                                    $actorName = $log->actor_name ?? $log->user?->name ?? 'System';
                                    $actorEmail = $log->actor_email ?? $log->user?->email;
                                    $oldLabel = $statuses[$log->old_value] ?? \Illuminate\Support\Str::title(str_replace('_', ' ', $log->old_value ?? ''));
                                    $newLabel = $statuses[$log->new_value] ?? \Illuminate\Support\Str::title(str_replace('_', ' ', $log->new_value ?? ''));
                                    $loggedAt = $log->created_at?->timezone($timezone);
                                @endphp
                                <li class="rounded-2xl border border-ink-100 bg-white px-4 py-3 shadow-sm shadow-ink-100/60">
                                    <div class="flex flex-wrap items-center justify-between gap-2 text-xs text-ink-400">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="inline-flex items-center rounded-full bg-ink-50 px-2.5 py-1 text-2xs font-semibold uppercase tracking-[0.2em] text-ink-500">
                                                Status Updated
                                            </span>
                                            <span class="inline-flex items-center rounded-full bg-brand-50 px-2.5 py-1 text-2xs font-semibold uppercase tracking-[0.2em] text-brand-700">
                                                {{ $newLabel }}
                                            </span>
                                        </div>
                                        <span>{{ $loggedAt?->format('d M Y • H:i') }} {{ __('messages.time_wib') }}</span>
                                    </div>
                                    <p class="mt-2 text-sm text-ink-700">
                                        <span class="font-semibold text-ink-900">{{ $actorName }}</span>
                                        @if ($actorEmail)
                                            <span class="text-ink-400">({{ $actorEmail }})</span>
                                        @endif
                                        {{ __('messages.changed_status_from') }}
                                        <span class="inline-flex items-center rounded-full bg-ink-50 px-2 py-0.5 text-2xs font-semibold uppercase tracking-[0.18em] text-ink-500">
                                            {{ $oldLabel }}
                                        </span>
                                        {{ __('messages.to') }}
                                        <span class="inline-flex items-center rounded-full bg-brand-50 px-2 py-0.5 text-2xs font-semibold uppercase tracking-[0.18em] text-brand-700">
                                            {{ $newLabel }}
                                        </span>
                                    </p>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-sm text-ink-500">{{ __('messages.no_status_change') }}</p>
                    @endif
                </x-ui.panel>

                <x-ui.panel title="{{ __('messages.ticket_summary') }}">
                    <dl class="space-y-4 text-sm text-ink-700">
                        <div class="flex items-start justify-between gap-4">
                            <dt class="text-ink-500">{{ __('messages.reporter') }}</dt>
                            <dd class="text-right text-ink-900">
                                <div>{{ $reporterDisplayName }}</div>
                                <div class="text-xs text-ink-400">{{ $reporterDisplayEmail }}</div>
                            </dd>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-ink-500">{{ __('messages.category') }}</dt>
                            <dd class="text-right font-medium text-ink-900">{{ optional($ticket->category)->name ?? __('messages.none') }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-ink-500">{{ __('messages.department') }}</dt>
                            <dd class="text-right font-medium text-ink-900">{{ optional($ticket->department)->name ?? __('messages.none') }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-ink-500">{{ __('messages.created_at') }}</dt>
                            <dd class="text-right text-ink-900">{{ $createdAt?->format('d M Y • H:i') }} {{ __('messages.time_wib') }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-ink-500">{{ __('messages.updated_at') }}</dt>
                            <dd class="text-right text-ink-900">{{ $updatedAt?->format('d M Y • H:i') }} {{ __('messages.time_wib') }}</dd>
                        </div>
                    </dl>
                </x-ui.panel>

                <x-ui.panel title="{{ __('messages.timeline_progress') }}">
                    <x-ticket-timeline :logs="$logs" />
                </x-ui.panel>

                <x-ui.panel title="{{ __('messages.attachment') }}" subtitle="{{ __('messages.download_attachment_sub') }}">
                    @if ($ticket->attachments_count > 0)
                        <ul class="space-y-3">
                            @foreach ($ticket->attachments as $attachment)
                                @php
                                    $attachmentName = $attachment->original_name ?? $attachment->file_name ?? 'Attachment';
                                    $extension = \Illuminate\Support\Str::lower(pathinfo($attachmentName, PATHINFO_EXTENSION) ?: 'file');
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
                                            <p class="text-sm font-semibold text-ink-900">{{ $attachmentName }}</p>
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
                                        {{ __('messages.download') }}
                                    </x-ui.button>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-sm font-light text-ink-500">{{ __('messages.no_attachment') }}</p>
                    @endif
                </x-ui.panel>

                <x-ui.panel title="{{ __('messages.ticket_activity') }}" subtitle="{{ __('messages.ticket_activity_sub') }}">
                    <ul class="space-y-5 text-sm text-ink-700">
                        <li class="flex items-start gap-3">
                            <span class="mt-1.5 h-2.5 w-2.5 rounded-full bg-brand-500"></span>
                            <div>
                                <p class="font-semibold text-ink-900">{{ __('messages.ticket_created') }}</p>
                                <p class="text-xs text-ink-400">{{ $createdAt?->format('d M Y • H:i') }} {{ __('messages.time_wib') }} oleh {{ $reporterDisplayName }}</p>
                            </div>
                        </li>
                        @if ($createdDifferentFromUpdated)
                            <li class="flex items-start gap-3">
                                <span class="mt-1.5 h-2.5 w-2.5 rounded-full bg-brand-400"></span>
                                <div>
                                    <p class="font-semibold text-ink-900">{{ __('messages.status_updated') }}</p>
                                    <p class="text-xs text-ink-400">
                                        {{ $updatedAt?->format('d M Y • H:i') }} {{ __('messages.time_wib') }} — {{ __('messages.status_now', ['status' => $statusLabel]) }}
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
