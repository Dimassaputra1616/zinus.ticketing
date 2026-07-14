@php
    $asset = $asset ?? null;
    $inputId = $inputId ?? 'asset-assignee';
    $listId = $inputId . '-list';
    $label = $label ?? __('messages.assigned_to');
    $placeholder = $placeholder ?? 'Pilih dari master data atau isi manual';
    $inputClass = $inputClass ?? 'h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-100';
    $selectedUserId = old('user_id', $asset?->user_id);
    $selectedAssigneeName = old('assigned_to_name', $asset?->assigned_to_name);

    if (! filled($selectedAssigneeName) && filled($selectedUserId)) {
        $selectedAssigneeName = $users->firstWhere('id', (int) $selectedUserId)?->name;
    }
@endphp

<div class="flex flex-col gap-1" data-assignee-field>
    <label for="{{ $inputId }}" class="text-sm font-semibold text-slate-700">{{ $label }}</label>
    <input
        type="hidden"
        name="user_id"
        value="{{ $selectedUserId }}"
        data-assignee-user-id
    >
    <input
        id="{{ $inputId }}"
        name="assigned_to_name"
        value="{{ $selectedAssigneeName }}"
        list="{{ $listId }}"
        autocomplete="off"
        class="{{ $inputClass }} w-full"
        placeholder="{{ $placeholder }}"
        data-assignee-name
    >
    <datalist id="{{ $listId }}">
        @foreach ($users as $user)
            <option value="{{ $user->name }}" label="{{ $user->email }}" data-user-id="{{ $user->id }}"></option>
        @endforeach
    </datalist>
    <p class="text-[11px] text-slate-400">Pilih user dari master data, atau ketik nama manual kalau belum ada.</p>
    @error('user_id')
        <p class="text-xs text-rose-600">{{ $message }}</p>
    @enderror
    @error('assigned_to_name')
        <p class="text-xs text-rose-600">{{ $message }}</p>
    @enderror
</div>
