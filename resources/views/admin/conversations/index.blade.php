<x-app-layout>
    <div class="w-full pt-4 sm:pt-6 pb-8 space-y-6">
        @if (session('success'))
            <div class="flex items-start gap-3 rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-emerald-800 shadow-sm">
                <span class="mt-0.5 inline-flex h-10 w-10 items-center justify-center rounded-lg bg-white text-emerald-700 shadow-inner">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 6 9 17l-5-5" /></svg>
                </span>
                <div>
                    <p class="text-sm font-semibold">Berhasil</p>
                    <p class="text-sm text-emerald-700/80">{{ session('success') }}</p>
                </div>
            </div>
        @endif

        <x-ui.section-hero
            pill="Admin"
            title="Live Chat Conversations"
            description="Kelola dan balas percakapan dengan pengguna."
        >
            <x-slot:pillIcon>
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
            </x-slot:pillIcon>
            <x-slot:icon>
                <svg class="h-7 w-7 text-[#12824C]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
            </x-slot:icon>
        </x-ui.section-hero>

        <div class="rounded-2xl border border-[#CFEADF] surface-card bg-gradient-to-br from-[#F6F9F8] via-white to-[#EDF3F2] p-6 space-y-4">
            <!-- Filters -->
            <div class="flex flex-wrap gap-4 items-end">
                <form method="GET" class="flex gap-3 flex-1">
                    <div class="flex-1">
                        <input 
                            type="text" 
                            name="search" 
                            value="{{ request('search') }}"
                            placeholder="Cari berdasarkan nama user..."
                            class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm focus:border-emerald-400 focus:ring-1 focus:ring-emerald-100"
                        >
                    </div>
                    <div>
                        <select 
                            name="status" 
                            class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm focus:border-emerald-400 focus:ring-1 focus:ring-emerald-100"
                        >
                            <option value="">Semua Status</option>
                            <option value="open" @selected(request('status') === 'open')>Terbuka</option>
                            <option value="closed" @selected(request('status') === 'closed')>Tertutup</option>
                        </select>
                    </div>
                    <button type="submit" class="btn-primary px-5 py-2 text-sm rounded-xl">
                        Filter
                    </button>
                </form>
            </div>

            <!-- Conversations Table -->
            @if ($conversations->isEmpty())
                <div class="text-center py-12 text-gray-400">
                    <svg class="h-16 w-16 mx-auto mb-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
                    <p class="text-lg font-medium">Belum ada percakapan</p>
                    <p class="text-sm mt-1">Percakapan akan muncul ketika pengguna mengirim pesan.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-slate-200">
                                <th class="text-left py-3 px-4 text-xs font-semibold uppercase tracking-wider text-slate-600">User</th>
                                <th class="text-left py-3 px-4 text-xs font-semibold uppercase tracking-wider text-slate-600">Pesan Terakhir</th>
                                <th class="text-left py-3 px-4 text-xs font-semibold uppercase tracking-wider text-slate-600">Waktu</th>
                                <th class="text-center py-3 px-4 text-xs font-semibold uppercase tracking-wider text-slate-600">Status</th>
                                <th class="text-center py-3 px-4 text-xs font-semibold uppercase tracking-wider text-slate-600">Unread</th>
                                <th class="text-center py-3 px-4 text-xs font-semibold uppercase tracking-wider text-slate-600">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($conversations as $conversation)
                                <tr class="border-b border-slate-100 hover:bg-emerald-50/30 transition">
                                    <td class="py-4 px-4">
                                        <div>
                                            <p class="font-semibold text-sm text-slate-800">{{ $conversation->user->name }}</p>
                                            <p class="text-xs text-slate-500">{{ $conversation->user->email }}</p>
                                        </div>
                                    </td>
                                    <td class="py-4 px-4">
                                        <p class="text-sm text-slate-700 truncate max-w-xs">
                                            {{ $conversation->latestMessage?->body ?? '-' }}
                                        </p>
                                    </td>
                                    <td class="py-4 px-4 text-sm text-slate-600">
                                        {{ $conversation->updated_at->diffForHumans() }}
                                    </td>
                                    <td class="py-4 px-4 text-center">
                                        @if ($conversation->is_open)
                                            <span class="inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-semibold bg-emerald-100 text-emerald-700">
                                                <span class="h-1.5 w-1.5 bg-emerald-500 rounded-full"></span>
                                                Terbuka
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-semibold bg-slate-100 text-slate-600">
                                                Tertutup
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-4 px-4 text-center">
                                        @if ($conversation->unread_count > 0)
                                            <span class="inline-flex items-center justify-center h-6 w-6 rounded-full bg-rose-500 text-white text-xs font-bold">
                                                {{ $conversation->unread_count }}
                                            </span>
                                        @else
                                            <span class="text-slate-400">-</span>
                                        @endif
                                    </td>
                                    <td class="py-4 px-4 text-center">
                                        <a 
                                            href="{{ route('admin.conversations.show', $conversation) }}" 
                                            class="inline-flex items-center gap-1 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-medium transition"
                                        >
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                                            Lihat
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-6">
                    {{ $conversations->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
