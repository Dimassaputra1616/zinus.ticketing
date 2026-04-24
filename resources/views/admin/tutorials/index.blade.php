<x-app-layout>
    <div class="w-full pt-4 sm:pt-6 pb-10 space-y-8">
        <x-ui.page-hero
            pill="Manajemen Tutorial"
            brand="Zinus Dream"
            eyebrow="Knowledge Base"
            title="Kelola Tutorial IT"
            description="Tambah, ubah, atau hapus tutorial IT Support untuk pengguna."
        >
            <x-slot:side>
                <div class="space-y-3">
                    <x-ui.button
                        variant="primary"
                        class="w-full justify-center"
                        href="{{ route('admin.tutorials.create') }}"
                        icon='<svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>'
                    >
                        Tambah Tutorial
                    </x-ui.button>
                </div>
            </x-slot:side>
        </x-ui.page-hero>

        @if (session('success'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-6 py-4 text-emerald-700 shadow-sm shadow-emerald-100">
                {{ session('success') }}
            </div>
        @endif

        <x-ui.panel title="Daftar Tutorial" class="p-0 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600">
                    <thead class="bg-slate-50/80 text-2xs font-semibold uppercase tracking-wider text-slate-500 border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-4">Judul</th>
                            <th class="px-6 py-4">Kategori</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse($tutorials as $tutorial)
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="px-6 py-4 font-medium text-slate-900">{{ $tutorial->title }}</td>
                            <td class="px-6 py-4">{{ $tutorial->category->name ?? '-' }}</td>
                            <td class="px-6 py-4">
                                @if($tutorial->is_active)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-800">Aktif</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-semibold bg-rose-100 text-rose-800">Draft</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right space-x-3">
                                <a href="{{ route('tutorials.show', $tutorial->slug) }}" target="_blank" class="text-indigo-600 hover:text-indigo-800 font-medium">Lihat</a>
                                <a href="{{ route('admin.tutorials.edit', $tutorial) }}" class="text-emerald-600 hover:text-emerald-800 font-medium">Edit</a>
                                <form action="{{ route('admin.tutorials.destroy', $tutorial) }}" method="POST" class="inline" onsubmit="return confirm('Hapus tutorial ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-rose-600 hover:text-rose-800 font-medium">Hapus</button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-slate-500">Belum ada tutorial.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($tutorials->hasPages())
                <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/30">
                    {{ $tutorials->links() }}
                </div>
            @endif
        </x-ui.panel>
    </div>
</x-app-layout>
