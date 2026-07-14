@php
    $context = $context ?? 'desktop';
    $listId = 'inline-assignee-' . $context . '-' . $asset->id . '-list';
    $assignedName = $asset->assigned_to_display_name;
    $buttonClass = $assignedName
        ? ($asset->user_id
            ? 'bg-emerald-50 text-emerald-700 ring-emerald-200 hover:bg-emerald-100'
            : 'bg-sky-50 text-sky-700 ring-sky-200 hover:bg-sky-100')
        : 'bg-slate-50 text-slate-500 ring-slate-200 hover:bg-slate-100';
@endphp

<div class="relative mt-1" x-data="{ open: false }" @keydown.escape.window="open = false">
    <button
        type="button"
        class="group inline-flex max-w-full items-center gap-1.5 rounded-md px-2 py-1 text-xs font-semibold ring-1 ring-inset transition {{ $buttonClass }}"
        title="Edit assigned user"
        @click.stop="open = !open"
    >
        <svg class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
            <path d="M20 21a8 8 0 0 0-16 0" stroke-linecap="round" />
            <circle cx="12" cy="7" r="4" />
        </svg>
        <span class="truncate">{{ $assignedName ?: 'Unassigned' }}</span>
        <svg class="h-3 w-3 shrink-0 opacity-60 transition group-hover:opacity-100" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
            <path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5Z" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
    </button>

    <div
        x-cloak
        x-show="open"
        x-transition
        @click.away="open = false"
        class="absolute left-0 z-40 mt-2 w-64 rounded-lg border border-slate-200 bg-white p-3 text-left shadow-lg shadow-slate-200"
    >
        <form method="POST" action="{{ route('assets.assignee.update', $asset) }}" class="space-y-2">
            @csrf
            @method('PATCH')
            <input type="hidden" name="redirect_to" value="{{ request()->fullUrl() }}">

            <label for="{{ $listId }}-input" class="block text-xs font-semibold text-slate-600">Assigned to</label>
            <input
                id="{{ $listId }}-input"
                name="assigned_to_name"
                value="{{ $assignedName }}"
                list="{{ $listId }}"
                autocomplete="off"
                placeholder="Master user atau manual"
                class="h-9 w-full rounded-lg border-slate-300 text-sm text-slate-800 placeholder:text-slate-400 focus:border-emerald-500 focus:ring-emerald-500"
            >
            <datalist id="{{ $listId }}">
                @foreach ($users as $user)
                    <option value="{{ $user->name }}" label="{{ $user->email }}"></option>
                @endforeach
            </datalist>

            <div class="flex items-center justify-end gap-2 pt-1">
                <button
                    type="button"
                    class="inline-flex h-8 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-600 hover:bg-slate-50"
                    @click="open = false"
                >
                    Cancel
                </button>
                <button
                    type="submit"
                    class="inline-flex h-8 items-center rounded-lg bg-emerald-600 px-3 text-xs font-semibold text-white hover:bg-emerald-700"
                >
                    Save
                </button>
            </div>
        </form>

        @if ($assignedName)
            <form method="POST" action="{{ route('assets.assignee.update', $asset) }}" class="mt-2 border-t border-slate-100 pt-2">
                @csrf
                @method('PATCH')
                <input type="hidden" name="redirect_to" value="{{ request()->fullUrl() }}">
                <input type="hidden" name="assigned_to_name" value="">
                <button type="submit" class="text-xs font-semibold text-rose-600 hover:text-rose-700">
                    Clear assignment
                </button>
            </form>
        @endif
    </div>
</div>
