@php
    $isSuperAdmin = auth()->user()?->is_super_admin;
    $isAdmin = auth()->user()?->isAdmin();
@endphp

<div class="space-y-4">
    <div class="md:hidden space-y-3">
        @forelse ($users as $u)
            @php
                $approvalStatus = $u->approval_status ?: \App\Models\User::APPROVAL_APPROVED;
                $approvalMeta = match ($approvalStatus) {
                    \App\Models\User::APPROVAL_PENDING => ['label' => 'Menunggu Approval', 'class' => 'border-amber-200 bg-amber-50 text-amber-700', 'dot' => 'bg-amber-500'],
                    \App\Models\User::APPROVAL_REJECTED => ['label' => 'Ditolak', 'class' => 'border-rose-200 bg-rose-50 text-rose-700', 'dot' => 'bg-rose-500'],
                    default => ['label' => 'Approved', 'class' => 'border-emerald-200 bg-emerald-50 text-emerald-700', 'dot' => 'bg-emerald-500'],
                };
                $roleMeta = match ($u->role) {
                    'admin' => ['label' => 'Admin', 'dot' => 'bg-emerald-500', 'class' => 'border-emerald-100 bg-emerald-50 text-emerald-700'],
                    'technician' => ['label' => 'Technician', 'dot' => 'bg-sky-500', 'class' => 'border-sky-100 bg-sky-50 text-sky-700'],
                    default => ['label' => 'User', 'dot' => 'bg-slate-400', 'class' => 'border-slate-200 bg-slate-50 text-slate-700'],
                };
            @endphp
            <article class="rounded-2xl border border-slate-100 bg-white p-4 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-400">User</p>
                        <p class="text-sm font-semibold text-slate-900 truncate">{{ $u->name }}</p>
                        <p class="text-xs text-slate-500 truncate">{{ $u->email }}</p>
                    </div>
                    <span class="inline-flex items-center gap-2 rounded-full border px-2 py-1 text-[10px] font-semibold uppercase tracking-[0.2em] {{ $roleMeta['class'] }}">
                        <span class="h-2 w-2 rounded-full {{ $roleMeta['dot'] }}"></span>
                        {{ $roleMeta['label'] }}
                    </span>
                </div>

                <div class="mt-3 inline-flex items-center gap-2 rounded-full border px-3 py-1 text-[10px] font-semibold uppercase tracking-[0.18em] {{ $approvalMeta['class'] }}">
                    <span class="h-2 w-2 rounded-full {{ $approvalMeta['dot'] }}"></span>
                    {{ $approvalMeta['label'] }}
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
                            <option value="technician">Technician</option>
                        </select>
                    @else
                        <div class="w-full rounded-xl border px-3 py-2 text-xs font-semibold uppercase tracking-[0.2em] shadow-sm {{ $roleMeta['class'] }}">
                            {{ $roleMeta['label'] }}
                        </div>
                    @endif
                </div>

                @if ($isAdmin)
                    <div class="mt-3 flex flex-wrap gap-2">
                        @if ($approvalStatus !== \App\Models\User::APPROVAL_APPROVED)
                            <x-ui.button
                                type="button"
                                size="sm"
                                class="w-full sm:w-auto bg-emerald-500 text-white shadow-sm hover:bg-emerald-600"
                                @click.prevent="submitApproval({{ Js::from(route('users.approve', $u)) }}, 'Akun disetujui')"
                            >
                                Approve
                            </x-ui.button>
                        @endif
                        @if ($approvalStatus !== \App\Models\User::APPROVAL_REJECTED)
                            <x-ui.button
                                type="button"
                                size="sm"
                                class="w-full sm:w-auto border border-amber-200 bg-amber-50 text-amber-700 hover:border-amber-300 hover:bg-amber-100"
                                @click.prevent="submitApproval({{ Js::from(route('users.reject', $u)) }}, 'Akun ditolak')"
                                x-bind:disabled="authId === {{ $u->id }}"
                            >
                                Reject
                            </x-ui.button>
                        @endif
                    </div>
                @endif

                @if ($isSuperAdmin)
                    <div class="mt-3 flex flex-wrap gap-2">
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

    <div class="hidden md:block">
        <div class="overflow-x-auto rounded-2xl border border-slate-100 bg-white shadow-sm">
            <table class="min-w-[1040px] w-full table-fixed text-sm align-middle">
                <colgroup>
                    <col class="w-16">
                    <col class="w-[34%]">
                    <col class="w-[17%]">
                    <col class="w-[20%]">
                    <col class="w-[29%]">
                </colgroup>
                <thead class="bg-slate-50/90 text-[0.68rem] uppercase tracking-[0.22em] text-slate-500">
                    <tr>
                        <th class="px-5 py-4 text-left font-bold">#</th>
                        <th class="px-5 py-4 text-left font-bold">User</th>
                        <th class="px-5 py-4 text-left font-bold">Role</th>
                        <th class="px-5 py-4 text-left font-bold">Approval</th>
                        <th class="px-5 py-4 text-left font-bold">Actions</th>
                    </tr>
                </thead>
                <tbody class="text-slate-800">
                    @forelse ($users as $u)
                        @php
                            $approvalStatus = $u->approval_status ?: \App\Models\User::APPROVAL_APPROVED;
                            $approvalMeta = match ($approvalStatus) {
                                \App\Models\User::APPROVAL_PENDING => ['label' => 'Menunggu Approval', 'class' => 'border-amber-200 bg-amber-50 text-amber-700', 'dot' => 'bg-amber-500'],
                                \App\Models\User::APPROVAL_REJECTED => ['label' => 'Ditolak', 'class' => 'border-rose-200 bg-rose-50 text-rose-700', 'dot' => 'bg-rose-500'],
                                default => ['label' => 'Approved', 'class' => 'border-emerald-200 bg-emerald-50 text-emerald-700', 'dot' => 'bg-emerald-500'],
                            };
                            $roleMeta = match ($u->role) {
                                'admin' => ['label' => 'Admin', 'dot' => 'bg-emerald-500', 'class' => 'border-emerald-100 bg-emerald-50 text-emerald-700'],
                                'technician' => ['label' => 'Technician', 'dot' => 'bg-sky-500', 'class' => 'border-sky-100 bg-sky-50 text-sky-700'],
                                default => ['label' => 'User', 'dot' => 'bg-slate-400', 'class' => 'border-slate-200 bg-slate-50 text-slate-700'],
                            };
                            $nameParts = preg_split('/\s+/', trim((string) $u->name)) ?: [];
                            $initials = collect($nameParts)
                                ->filter()
                                ->take(2)
                                ->map(fn ($part) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($part, 0, 1)))
                                ->implode('') ?: 'U';
                        @endphp
                        <tr class="border-t border-slate-100 transition-colors duration-200 first:border-t-0 hover:bg-emerald-50/30">
                            <td class="px-5 py-4 text-xs font-semibold text-slate-400">{{ ($users->firstItem() ?? 0) + $loop->index }}</td>
                            <td class="px-5 py-4">
                                <div class="flex min-w-0 items-center gap-3">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-100 to-sky-100 text-xs font-black uppercase tracking-wider text-emerald-800 ring-1 ring-white">
                                        {{ $initials }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="truncate font-bold text-slate-900">{{ $u->name }}</p>
                                        <p class="mt-0.5 truncate text-xs text-slate-500">{{ $u->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="inline-flex max-w-full items-center gap-2">
                                    @if ($isSuperAdmin)
                                        <select
                                            class="h-9 max-w-[150px] rounded-full border border-slate-200 bg-white px-3 text-[11px] font-bold uppercase tracking-[0.18em] text-slate-700 shadow-sm transition focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100"
                                            x-on:change="quickUpdateRole({ id: {{ $u->id }}, role: $event.target.value, action: {{ Js::from(route('users.updateRole', $u)) }} })"
                                            x-bind:value="{{ Js::from($u->role) }}"
                                            x-bind:disabled="authId === {{ $u->id }}"
                                        >
                                            <option value="user">User</option>
                                            <option value="admin">Admin</option>
                                            <option value="technician">Technician</option>
                                        </select>
                                    @else
                                        <span class="inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-[11px] font-bold uppercase tracking-[0.18em] {{ $roleMeta['class'] }}">
                                            <span class="h-2 w-2 rounded-full {{ $roleMeta['dot'] }}"></span>
                                            {{ $roleMeta['label'] }}
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <span class="inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-[10px] font-bold uppercase tracking-[0.18em] {{ $approvalMeta['class'] }}">
                                    <span class="h-2 w-2 rounded-full {{ $approvalMeta['dot'] }}"></span>
                                    {{ $approvalMeta['label'] }}
                                </span>
                            </td>
                            <td class="px-5 py-4 align-middle">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    @if ($isAdmin)
                                        @if ($approvalStatus !== \App\Models\User::APPROVAL_APPROVED)
                                            <button
                                                type="button"
                                                class="inline-flex h-8 items-center justify-center rounded-full bg-emerald-500 px-3 text-[11px] font-bold text-white shadow-sm shadow-emerald-100 transition hover:bg-emerald-600 disabled:cursor-not-allowed disabled:opacity-50"
                                                @click.prevent="submitApproval({{ Js::from(route('users.approve', $u)) }}, 'Akun disetujui')"
                                            >
                                                Approve
                                            </button>
                                        @endif
                                        @if ($approvalStatus !== \App\Models\User::APPROVAL_REJECTED)
                                            <button
                                                type="button"
                                                class="inline-flex h-8 items-center justify-center rounded-full border border-amber-200 bg-amber-50 px-3 text-[11px] font-bold text-amber-700 transition hover:border-amber-300 hover:bg-amber-100 disabled:cursor-not-allowed disabled:opacity-50"
                                                @click.prevent="submitApproval({{ Js::from(route('users.reject', $u)) }}, 'Akun ditolak')"
                                                x-bind:disabled="authId === {{ $u->id }}"
                                            >
                                                Reject
                                            </button>
                                        @endif
                                    @endif
                                    @if ($isSuperAdmin)
                                        <button
                                            type="button"
                                            class="inline-flex h-8 items-center justify-center rounded-full border border-slate-200 bg-white px-3 text-[11px] font-bold text-slate-600 transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700 disabled:cursor-not-allowed disabled:opacity-50"
                                            @click.prevent="openUpdate({ id: {{ $u->id }}, name: {{ Js::from($u->name) }}, email: {{ Js::from($u->email) }}, role: {{ Js::from($u->role) }}, action: {{ Js::from(route('users.updateRole', $u)) }} })"
                                        >
                                            Edit
                                        </button>
                                        <button
                                            type="button"
                                            class="inline-flex h-8 items-center justify-center rounded-full bg-emerald-600 px-3 text-[11px] font-bold text-white shadow-sm shadow-emerald-100 transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50"
                                            @click.prevent="openReset({{ Js::from($u->name) }}, {{ Js::from(route('users.resetPassword', $u)) }})"
                                            x-bind:disabled="authId === {{ $u->id }}"
                                        >
                                            Reset
                                        </button>
                                        <button
                                            type="button"
                                            class="inline-flex h-8 items-center justify-center rounded-full border border-rose-200 bg-rose-50 px-3 text-[11px] font-bold text-rose-600 transition hover:border-rose-300 hover:bg-rose-100 disabled:cursor-not-allowed disabled:opacity-50"
                                            @click.prevent="confirmDelete({{ Js::from($u->name) }}, {{ Js::from(route('users.destroy', $u)) }})"
                                            x-bind:disabled="authId === {{ $u->id }}"
                                        >
                                            Hapus
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-8 text-center text-sm text-slate-500">Belum ada user ditemukan.</td>
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
