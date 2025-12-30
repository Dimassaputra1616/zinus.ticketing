@php
    $isSuperAdmin = auth()->user()?->is_super_admin;
@endphp

<div class="space-y-4">
    <div class="md:hidden space-y-3">
        @forelse ($users as $u)
            <article class="rounded-2xl border border-slate-100 bg-white p-4 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-400">User</p>
                        <p class="text-sm font-semibold text-slate-900 truncate">{{ $u->name }}</p>
                        <p class="text-xs text-slate-500 truncate">{{ $u->email }}</p>
                    </div>
                    <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-2 py-1 text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-600">
                        <span class="h-2 w-2 rounded-full {{ $u->role === 'admin' ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                        {{ strtoupper($u->role) }}
                    </span>
                </div>

                <div class="mt-3 space-y-2">
                    <label class="text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">Role</label>
                    @if ($isSuperAdmin)
                        <select
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold uppercase tracking-[0.2em] text-slate-700 shadow-sm focus:border-emerald-400 focus:ring-1 focus:ring-emerald-100"
                            x-on:change="quickUpdateRole({ id: {{ $u->id }}, role: $event.target.value, action: {{ Js::from(route('users.updateRole', $u)) }} })"
                            x-bind:value="{{ Js::from($u->role) }}"
                            x-bind:disabled="authId === {{ $u->id }}"
                        >
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                    @else
                        <div class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold uppercase tracking-[0.2em] text-slate-700 shadow-sm">
                            {{ strtoupper($u->role) }}
                        </div>
                    @endif
                </div>

                @if ($isSuperAdmin)
                    <div class="mt-3 flex flex-wrap gap-2">
                        <x-ui.button
                            type="button"
                            size="sm"
                            class="w-full sm:w-auto border-emerald-300 text-emerald-600 hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-700"
                            @click.prevent="openUpdate({ id: {{ $u->id }}, name: {{ Js::from($u->name) }}, email: {{ Js::from($u->email) }}, role: {{ Js::from($u->role) }}, action: {{ Js::from(route('users.updateRole', $u)) }} })"
                        >
                            Update
                        </x-ui.button>
                        <x-ui.button
                            type="button"
                            size="sm"
                            class="w-full sm:w-auto bg-emerald-500 text-white shadow-sm hover:bg-emerald-600"
                            @click.prevent="openReset({{ Js::from($u->name) }}, {{ Js::from(route('users.resetPassword', $u)) }})"
                            x-bind:disabled="authId === {{ $u->id }}"
                        >
                            Reset Password
                        </x-ui.button>
                        <x-ui.button
                            type="button"
                            size="sm"
                            class="w-full sm:w-auto border border-red-200 bg-[#ffe4e6] text-red-600 hover:border-red-200 hover:bg-[#fecdd3]"
                            @click.prevent="confirmDelete({{ Js::from($u->name) }}, {{ Js::from(route('users.destroy', $u)) }})"
                            x-bind:disabled="authId === {{ $u->id }}"
                        >
                            Hapus
                        </x-ui.button>
                    </div>
                @endif
            </article>
        @empty
            <div class="px-3 py-4 text-center text-sm text-slate-500">Belum ada user ditemukan.</div>
        @endforelse
    </div>

    <div class="hidden md:block -mx-2 px-2">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm align-middle">
                <thead class="bg-slate-50/70 text-2xs uppercase tracking-[0.2em] text-slate-500">
                    <tr>
                        <th class="px-3 py-3 text-left font-semibold">#</th>
                        <th class="px-3 py-3 text-left font-semibold">Nama</th>
                        <th class="px-3 py-3 text-left font-semibold">Email</th>
                        <th class="px-3 py-3 text-left font-semibold">Role</th>
                        <th class="px-3 py-3 text-left font-semibold">Aksi</th>
                    </tr>
                </thead>
                <tbody class="text-slate-800">
                    @forelse ($users as $u)
                        <tr class="transition-colors duration-200 ease-[cubic-bezier(.22,.61,.36,1)] border-b border-gray-200 hover:bg-slate-50 align-middle">
                            <td class="px-3 py-2.5">{{ ($users->firstItem() ?? 0) + $loop->index }}</td>
                            <td class="px-3 py-2.5">
                                <p class="font-semibold text-slate-900">{{ $u->name }}</p>
                            </td>
                            <td class="px-3 py-2.5 text-slate-700 max-w-[240px] truncate">{{ $u->email }}</td>
                            <td class="px-3 py-2.5">
                                <div class="inline-flex items-center gap-2">
                                    <span class="h-2 w-2 rounded-full {{ $u->role === 'admin' ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                    @if ($isSuperAdmin)
                                        <select
                                            class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-slate-700 shadow-sm focus:border-emerald-400 focus:ring-1 focus:ring-emerald-100"
                                            x-on:change="quickUpdateRole({ id: {{ $u->id }}, role: $event.target.value, action: {{ Js::from(route('users.updateRole', $u)) }} })"
                                            x-bind:value="{{ Js::from($u->role) }}"
                                            x-bind:disabled="authId === {{ $u->id }}"
                                        >
                                            <option value="user">User</option>
                                            <option value="admin">Admin</option>
                                        </select>
                                    @else
                                        <span class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-700">{{ strtoupper($u->role) }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-3 py-2.5 align-middle">
                                @if ($isSuperAdmin)
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <x-ui.button
                                            type="button"
                                            size="sm"
                                            class="border-emerald-300 text-emerald-600 hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-700"
                                            @click.prevent="openUpdate({ id: {{ $u->id }}, name: {{ Js::from($u->name) }}, email: {{ Js::from($u->email) }}, role: {{ Js::from($u->role) }}, action: {{ Js::from(route('users.updateRole', $u)) }} })"
                                        >
                                            Update
                                        </x-ui.button>
                                        <x-ui.button
                                            type="button"
                                            size="sm"
                                            class="w-28 bg-emerald-500 text-white shadow-sm hover:bg-emerald-600"
                                            @click.prevent="openReset({{ Js::from($u->name) }}, {{ Js::from(route('users.resetPassword', $u)) }})"
                                            x-bind:disabled="authId === {{ $u->id }}"
                                        >
                                            Reset Password
                                        </x-ui.button>
                                        <x-ui.button
                                            type="button"
                                            size="sm"
                                            class="w-28 border border-red-200 bg-[#ffe4e6] text-red-600 hover:border-red-200 hover:bg-[#fecdd3]"
                                            @click.prevent="confirmDelete({{ Js::from($u->name) }}, {{ Js::from(route('users.destroy', $u)) }})"
                                            x-bind:disabled="authId === {{ $u->id }}"
                                        >
                                            Hapus
                                        </x-ui.button>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-3 py-4 text-center text-sm text-slate-500">Belum ada user ditemukan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($users->hasPages())
        <div class="pt-2">
            {{ $users->onEachSide(1)->links() }}
        </div>
    @endif
</div>
