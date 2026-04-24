    <div class="w-full pt-4 sm:pt-6 pb-8 space-y-6">
        <x-ui.section-hero
            pill="{{ __('messages.master_config') }}"
            title="{{ __('messages.title_master_config') }}"
            description="{{ __('messages.desc_master_config') }}"
        >
            <x-slot:pillIcon>
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.38a2 2 0 0 0-.73-2.73l-.15-.1a2 2 0 0 1-1-1.72v-.51a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/></svg>
            </x-slot:pillIcon>
        </x-ui.section-hero>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-[24px] border border-slate-200">
            <div class="p-8 text-gray-900">
                <h2 class="text-xl font-bold mb-8 text-slate-800 flex items-center gap-3">
                    <span class="p-2 bg-slate-50 rounded-lg">
                        <svg class="h-6 w-6 text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                    </span>
                    Edit Master Parameters
                </h2>
                
                <form wire:submit="save" class="space-y-8">
                    
                    <!-- General Settings Section -->
                    <div class="space-y-6">
                        <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400">General Configuration</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div class="space-y-2">
                                <label class="text-xs font-bold text-slate-500 uppercase tracking-wide">Application Name</label>
                                <input type="text" wire:model="app_name" class="w-full rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500 transition-all px-4 py-3 shadow-sm text-sm" placeholder="Contoh: Zinus Dream">
                                @error('app_name') <span class="text-rose-500 text-xs font-semibold">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-slate-100"></div>

                    <!-- Theme Branding Section -->
                    <div class="space-y-6">
                        <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400">{{ __('messages.theme_branding') }}</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                            <!-- Primary Color -->
                            <div class="space-y-2">
                                <label class="text-xs font-bold text-slate-500 uppercase tracking-wide">{{ __('messages.primary_color_label') }}</label>
                                <div class="flex items-center gap-3">
                                    <input type="color" wire:model.live="theme_color" class="h-12 w-12 rounded-xl border-slate-200 cursor-pointer shadow-sm">
                                    <input type="text" wire:model="theme_color" class="flex-1 rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500 transition-all px-4 py-3 shadow-sm font-mono text-sm uppercase">
                                </div>
                            </div>

                            <!-- Primary Hover Color -->
                            <div class="space-y-2">
                                <label class="text-xs font-bold text-slate-500 uppercase tracking-wide">{{ __('messages.hover_color_label') }}</label>
                                <div class="flex items-center gap-3">
                                    <input type="color" wire:model.live="theme_color_strong" class="h-12 w-12 rounded-xl border-slate-200 cursor-pointer shadow-sm">
                                    <input type="text" wire:model="theme_color_strong" class="flex-1 rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500 transition-all px-4 py-3 shadow-sm font-mono text-sm uppercase">
                                </div>
                            </div>

                            <!-- Secondary Color -->
                            <div class="space-y-2">
                                <label class="text-xs font-bold text-slate-500 uppercase tracking-wide">{{ __('messages.secondary_color_label') }}</label>
                                <div class="flex items-center gap-3">
                                    <input type="color" wire:model.live="theme_color_secondary" class="h-12 w-12 rounded-xl border-slate-200 cursor-pointer shadow-sm">
                                    <input type="text" wire:model="theme_color_secondary" class="flex-1 rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500 transition-all px-4 py-3 shadow-sm font-mono text-sm uppercase">
                                </div>
                            </div>

                            <!-- Sidebar Background -->
                            <div class="space-y-2">
                                <label class="text-xs font-bold text-slate-500 uppercase tracking-wide">{{ __('messages.sidebar_bg_label') }}</label>
                                <div class="flex items-center gap-3">
                                    <input type="color" wire:model.live="sidebar_color" class="h-12 w-12 rounded-xl border-slate-200 cursor-pointer shadow-sm">
                                    <input type="text" wire:model="sidebar_color" class="flex-1 rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500 transition-all px-4 py-3 shadow-sm font-mono text-sm uppercase">
                                </div>
                            </div>

                            <!-- Sidebar Text/Icon -->
                            <div class="space-y-2">
                                <label class="text-xs font-bold text-slate-500 uppercase tracking-wide">{{ __('messages.sidebar_text_label') }}</label>
                                <div class="flex items-center gap-3">
                                    <input type="color" wire:model.live="sidebar_text_color" class="h-12 w-12 rounded-xl border-slate-200 cursor-pointer shadow-sm">
                                    <input type="text" wire:model="sidebar_text_color" class="flex-1 rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500 transition-all px-4 py-3 shadow-sm font-mono text-sm uppercase">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Welcome Message -->
                    <div class="space-y-2">
                        <label for="welcome_message" class="text-sm font-semibold text-slate-700">Dashboard Welcome Message</label>
                        <textarea wire:model="welcome_message" id="welcome_message" rows="4" class="w-full rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500 transition-all px-4 py-3 shadow-sm" placeholder="Isi pesan sapaan untuk dashboard..."></textarea>
                        @error('welcome_message') <span class="text-rose-500 text-xs font-semibold">{{ $message }}</span> @enderror
                    </div>

                    <!-- App Logo -->
                    <div class="space-y-4 bg-slate-50 p-6 rounded-2xl border border-dashed border-slate-300">
                        <label for="app_logo" class="text-sm font-semibold text-slate-700">Application Brand Logo</label>
                        <div class="flex flex-col sm:flex-row items-center gap-8">
                            <div class="relative group">
                                @if ($app_logo)
                                    <img src="{{ $app_logo->temporaryUrl() }}" class="h-32 w-32 object-contain rounded-2xl border bg-white shadow-xl shadow-slate-200/50">
                                @elseif (setting('app_logo'))
                                    <img src="{{ asset('storage/' . setting('app_logo')) }}" class="h-32 w-32 object-contain rounded-2xl border bg-white shadow-xl shadow-slate-200/50">
                                @else
                                    <div class="h-32 w-32 rounded-2xl border border-dashed border-slate-300 bg-white flex flex-col items-center justify-center text-slate-400 gap-2">
                                        <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2" /><circle cx="8.5" cy="8.5" r="1.5" /><path d="m21 15-5-5L5 21" /></svg>
                                        <span class="text-[10px] font-medium uppercase tracking-wider">No Logo</span>
                                    </div>
                                @endif
                            </div>
                            
                            <div class="flex-1 space-y-3">
                                <input type="file" wire:model="app_logo" id="app_logo" class="block w-full text-xs text-slate-500 file:mr-4 file:py-2.5 file:px-6 file:rounded-full file:border-0 file:text-xs file:font-bold file:bg-slate-900 file:text-white hover:file:bg-slate-800 transition-all cursor-pointer">
                                <p class="text-[11px] text-slate-500 leading-relaxed font-medium">Format: PNG, JPG, or WEBP. Max size: 2MB. Untuk hasil terbaik, gunakan gambar dengan latar belakang transparan.</p>
                                <div wire:loading wire:target="app_logo" class="flex items-center gap-2 text-emerald-600 font-bold text-[11px] animate-pulse uppercase tracking-wider">
                                    <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
                                    Sedang mengupload...
                                </div>
                            </div>
                        </div>
                        @error('app_logo') <span class="text-rose-500 text-xs font-semibold">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex justify-end items-center gap-4 pt-8 border-t border-slate-100">
                        <a href="{{ route('dashboard') }}" 
                            class="inline-flex items-center gap-2 rounded-xl px-8 py-3 text-sm font-bold text-slate-400 hover:text-slate-600 hover:bg-slate-50 transition-all active:scale-95"
                            wire:navigate
                        >
                            {{ __('messages.cancel') ?? 'Batal' }}
                        </a>
                        <button type="submit" 
                            class="group relative inline-flex items-center gap-3 rounded-xl px-10 py-4 text-sm font-black text-white shadow-xl shadow-emerald-500/20 transition-all hover:scale-[1.02] active:scale-95 disabled:opacity-50 overflow-hidden"
                            style="background: linear-gradient(135deg, {{ $theme_color }}, {{ $sidebar_color }});"
                            wire:loading.attr="disabled"
                        >
                            <div class="absolute inset-0 bg-white/20 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                            
                            <svg wire:loading.remove wire:target="save" class="h-5 w-5 transition-transform group-hover:rotate-12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                                <polyline points="17 21 17 13 7 13 7 21"/>
                                <polyline points="7 3 7 8 15 8"/>
                            </svg>
                            
                            <svg wire:loading wire:target="save" class="h-5 w-5 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 12a9 9 0 1 1-6.219-8.56" />
                            </svg>
                            
                            <span wire:loading.remove wire:target="save" class="tracking-wide uppercase">Save Master Config</span>
                            <span wire:loading wire:target="save" class="tracking-wide uppercase">Saving Changes...</span>
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
