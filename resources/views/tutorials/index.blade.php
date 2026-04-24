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

            <x-ui.section-hero
            pill="{{ __('messages.tutorials') }}"
            title="Pusat Panduan Mandiri"
            description="Cari solusi cepat untuk kendala IT umum tanpa harus menunggu bantuan teknisi."
        >
            <x-slot:pillIcon>
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-0-5H20" />
                    <path d="M8 7h6" />
                    <path d="M8 11h8" />
                </svg>
            </x-slot:pillIcon>
            <x-slot:icon>
                <svg class="h-7 w-7" style="color: var(--zinus-green);" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10" />
                    <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3" />
                    <path d="M12 17h.01" />
                </svg>
            </x-slot:icon>
        </x-ui.section-hero>

        <section class="space-y-6 mt-6">
            <!-- Search and Filter -->
            <div class="flex flex-col md:flex-row items-center gap-4 bg-white/50 backdrop-blur-sm p-4 rounded-2xl border border-[#CFEADF] shadow-sm">
                <form action="{{ route('tutorials.index') }}" method="GET" class="flex-1 w-full relative">
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Cari tutorial (contoh: Outlook, Driver, WiFi)..."
                        class="w-full rounded-xl border border-slate-200 bg-white px-5 py-2.5 pl-11 text-sm text-slate-900 placeholder:text-slate-400 transition-all focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100/50 hover:border-emerald-300 shadow-sm"
                    >
                    <svg class="absolute left-4 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8" />
                        <path d="m21 21-4.3-4.3" />
                    </svg>
                </form>
                
                <div class="flex items-center gap-2 w-full md:w-auto">
                    @foreach($categories as $category)
                        @php
                            $isActive = request('category') == $category->id;
                        @endphp
                        <a 
                            href="{{ route('tutorials.index', ['category' => $category->id]) }}"
                            @class([
                                'px-4 py-2 rounded-xl text-xs font-bold transition-all border shadow-sm flex items-center gap-2',
                                'text-white shadow-emerald-200/50' => $isActive,
                                'bg-white text-slate-600 border-slate-200 hover:border-emerald-400 hover:bg-emerald-50 hover:text-emerald-700' => ! $isActive,
                            ])
                            style="{{ $isActive ? 'background-color: #118A58; border-color: #118A58;' : '' }}"
                        >
                            @if($isActive)
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                            @endif
                            {{ $category->name }}
                        </a>
                    @endforeach
                    @if(request('category') || request('search'))
                        <a href="{{ route('tutorials.index') }}" class="text-xs text-rose-500 font-semibold hover:underline px-2">Reset</a>
                    @endif
                </div>
            </div>

            <!-- Grid -->
            @if($tutorials->isEmpty())
                <div class="flex flex-col items-center justify-center py-20 bg-white/40 rounded-3xl border border-dashed border-slate-300">
                    <div class="h-20 w-20 rounded-full bg-slate-100 flex items-center justify-center mb-4">
                        <svg class="h-10 w-10 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-0-5H20" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-[#0C1F2C]">Tutorial Tidak Ditemukan</h3>
                    <p class="text-sm text-slate-500 max-w-sm text-center mt-1">Kami belum memiliki panduan untuk topik ini. Silakan hubungi IT jika kendala berlanjut.</p>
                    <a href="{{ route('tickets.create') }}" class="mt-6 text-sm font-semibold text-[#118A58] hover:underline flex items-center gap-2">
                        Buat Tiket IT Baru
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    </a>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($tutorials as $tutorial)
                        <div class="group relative flex flex-col p-6 rounded-2xl border border-slate-200 bg-white shadow-sm transition-all hover:shadow-xl hover:-translate-y-1.5 hover:border-emerald-400 hover:ring-4 hover:ring-emerald-50 overflow-hidden h-full">
                            <a href="{{ route('tutorials.show', $tutorial->slug) }}" class="absolute inset-0 z-0"></a>
                            
                            <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-25 transition-opacity z-10 pointer-events-none">
                                <svg class="h-16 w-16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                                    <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-0-5H20" />
                                </svg>
                            </div>
                            
                            @if($tutorial->image_path)
                                <div class="w-[calc(100%+3rem)] -mx-6 -mt-6 mb-6 aspect-video bg-slate-100 relative z-10 overflow-hidden">
                                    <img src="{{ Storage::url($tutorial->image_path) }}" alt="{{ $tutorial->title }}" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">
                                    <button 
                                        @click="lightboxImage = '{{ Storage::url($tutorial->image_path) }}'; lightboxOpen = true"
                                        class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-all duration-300 flex items-center justify-center opacity-0 group-hover:opacity-100"
                                    >
                                        <div class="bg-white/20 backdrop-blur-md rounded-full p-3 ring-1 ring-white/30 text-white shadow-2xl scale-90 group-hover:scale-100 transition-transform duration-300">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"></path></svg>
                                        </div>
                                    </button>
                                </div>
                            @endif

                            <div class="mb-4 relative z-20 pointer-events-none">
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-100">
                                    {{ $tutorial->category?->name ?? 'Umum' }}
                                </span>
                            </div>

                            <a href="{{ route('tutorials.show', $tutorial->slug) }}" class="relative z-20 block">
                                <h3 class="text-xl font-bold text-[#0C1F2C] mb-3 group-hover:text-[#118A58] transition-colors line-clamp-2">
                                    {{ $tutorial->title }}
                                </h3>
                            </a>

                            <p class="text-sm text-slate-500 line-clamp-3 mb-6 flex-grow relative z-20 pointer-events-none">
                                {{ Str::limit(strip_tags($tutorial->content), 120) }}
                            </p>

                            <div class="flex items-center justify-between mt-auto pt-4 border-t border-slate-50 relative z-20 pointer-events-none">
                                <div class="flex items-center gap-4 text-[11px] text-slate-400 font-medium">
                                    <span class="flex items-center gap-1">
                                        <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                        {{ $tutorial->views }} Dilihat
                                    </span>
                                    <span>{{ $tutorial->created_at->diffForHumans() }}</span>
                                </div>
                                <svg class="h-5 w-5 text-emerald-400 -translate-x-2 opacity-0 group-hover:translate-x-0 group-hover:opacity-100 transition-all" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="m9 18 6-6-6-6" />
                                </svg>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-8">
                    {{ $tutorials->links() }}
                </div>
            @endif
        </section>
    </div>
    </div>
</x-app-layout>
