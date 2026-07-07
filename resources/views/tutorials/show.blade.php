<x-app-layout>
    <div x-data="{ lightboxOpen: false, lightboxImage: '' }">
        {{-- Lightbox Modal --}}
        <div 
            x-show="lightboxOpen" 
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @keydown.escape.window="lightboxOpen = false"
            class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/90 backdrop-blur-md"
            style="display: none;"
        >
            <button @click="lightboxOpen = false" class="absolute top-6 right-6 p-2 text-white/70 hover:text-white transition-colors duration-200">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
            <div 
                @click.away="lightboxOpen = false"
                class="relative max-w-7xl max-h-[90vh] w-full flex items-center justify-center"
            >
                <img 
                    :src="lightboxImage" 
                    class="max-w-full max-h-[90vh] object-contain rounded-xl shadow-2xl ring-1 ring-white/10"
                    x-transition:enter="transition cubic-bezier(0.4, 0, 0.2, 1) duration-500 delay-100"
                    x-transition:enter-start="scale-95 opacity-0"
                    x-transition:enter-end="scale-100 opacity-100"
                >
            </div>
        </div>

        <div class="w-full pt-4 sm:pt-6 pb-8 space-y-6">

            <div class="flex items-center gap-4 mb-2">
            <a href="{{ route('tutorials.index') }}" class="group inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-slate-600 transition hover:border-[#118A58] hover:text-[#118A58] shadow-sm">
                <svg class="h-4 w-4 transition-transform group-hover:-translate-x-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                Kembali ke Daftar Panduan
            </a>
            <div class="h-1 flex-grow border-t border-slate-100"></div>
            @if(auth()->user()?->isAdmin())
                <a href="{{ route('admin.tutorials.edit', $tutorial) }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-600 text-white text-sm font-bold shadow-lg shadow-emerald-600/20 hover:bg-emerald-700 transition">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    Edit Artikel
                </a>
            @endif
        </div>

        <article class="relative bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
            <!-- Header -->
            <header class="relative p-8 md:p-12 bg-gradient-to-br from-[#F6F9F8] via-white to-[#EDF3F2] border-b border-slate-100">
                <div class="absolute top-0 right-0 p-12 opacity-[0.03] rotate-12">
                    <svg class="h-64 w-64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                        <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-0-5H20" />
                    </svg>
                </div>

                <div class="relative z-10 space-y-6">
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-[#118A58] text-white">
                            {{ $tutorial->category?->name ?? 'Umum' }}
                        </span>
                        <span class="text-slate-400">•</span>
                        <span class="text-sm font-medium text-slate-500 flex items-center gap-1">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            {{ $tutorial->views }} Pembaca
                        </span>
                    </div>

                    <h1 class="text-3xl md:text-5xl font-bold text-[#0C1F2C] leading-tight">
                        {{ $tutorial->title }}
                    </h1>

                    <div class="flex items-center gap-4 pt-4 border-t border-slate-100">
                        <div class="h-10 w-10 rounded-full bg-emerald-100 flex items-center justify-center text-[#118A58] font-bold">
                            {{ mb_strtoupper(mb_substr($tutorial->author->name, 0, 1)) }}
                        </div>
                        <div>
                            <p class="text-sm font-bold text-[#0C1F2C] leading-none">{{ $tutorial->author->name }}</p>
                            <p class="text-[11px] text-slate-400 mt-1 uppercase tracking-wider font-semibold">Staf IT Support • {{ $tutorial->created_at->format('d M Y') }}</p>
                        </div>
                    </div>
                </div>
            </header>

            @if($tutorial->image_path)
                <div class="w-full h-64 md:h-96 px-8 pt-8 md:px-12 md:pt-12">
                    <div 
                        class="group relative w-full h-full overflow-hidden rounded-3xl shadow-sm border border-slate-100 cursor-pointer"
                        @click="lightboxImage = '{{ Storage::url($tutorial->image_path) }}'; lightboxOpen = true"
                    >
                        <img src="{{ Storage::url($tutorial->image_path) }}" alt="{{ $tutorial->title }}" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">
                        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-colors duration-300 flex items-center justify-center">
                            <div class="opacity-0 group-hover:opacity-100 translate-y-4 group-hover:translate-y-0 transition-all duration-300 bg-white/20 backdrop-blur-md rounded-full p-4 ring-1 ring-white/30 text-white shadow-2xl">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"></path></svg>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Content -->
            <div class="p-8 md:p-12 prose prose-slate max-w-none prose-headings:text-[#0C1F2C] prose-a:text-[#118A58] prose-img:rounded-2xl prose-img:shadow-lg">
                {!! nl2br(e($tutorial->content)) !!}
            </div>

            <!-- Footer -->
            <footer class="p-8 md:p-12 bg-slate-50 border-t border-slate-100">
                <div class="flex flex-col md:flex-row items-center justify-between gap-6">
                    <div class="space-y-1">
                        <h4 class="font-bold text-[#0C1F2C]">Apakah panduan ini membantu?</h4>
                        <p class="text-sm text-slate-500">Bantu kami meningkatkan kualitas layanan mandiri.</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <button class="px-6 py-2 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-slate-600 hover:bg-emerald-50 hover:border-emerald-200 hover:text-emerald-700 transition">Ya, Sangat Membantu!</button>
                        <button class="px-6 py-2 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-slate-600 hover:bg-rose-50 hover:border-rose-200 hover:text-rose-700 transition">Belum Teratasi</button>
                    </div>
                </div>
            </footer>
        </article>

        <!-- Help Section -->
        <div class="rounded-3xl bg-gradient-to-r from-[#118A58] to-[#23455D] p-8 md:p-12 text-white flex flex-col md:flex-row items-center justify-between gap-8 shadow-xl shadow-emerald-900/20">
            <div class="space-y-3">
                <h3 class="text-2xl font-bold">Masih butuh bantuan lebih lanjut?</h3>
                <p class="text-emerald-50/80">Jika panduan di atas belum menyelesaikan masalahmu, tim IT Support kami siap membantu secara langsung.</p>
            </div>
            <div class="flex items-center gap-3 shrink-0">
                <a href="{{ route('tickets.create') }}" class="px-8 py-3 rounded-2xl bg-white text-[#118A58] font-bold text-sm shadow-lg hover:-translate-y-1 transition flex items-center gap-2">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                    Buat Tiket Baru
                </a>
            </div>
        </div>
    </div>
    </div>
</x-app-layout>
