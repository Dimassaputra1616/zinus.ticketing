<x-app-layout>
    <div class="w-full pt-4 sm:pt-6 pb-10 space-y-8">
        <x-ui.page-hero
            pill="Form Tutorial"
            brand="Zinus Dream"
            eyebrow="Knowledge Base"
            title="{{ $tutorial->exists ? 'Edit Tutorial' : 'Tambah Tutorial' }}"
            description="Lengkapi data tutorial di bawah ini."
        />

        @if ($errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-6 py-4 text-rose-700 shadow-sm shadow-rose-100">
                <ul class="list-disc space-y-1 ps-4 text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <x-ui.panel class="max-w-4xl mx-auto p-6 md:p-8">
            <form action="{{ $tutorial->exists ? route('admin.tutorials.update', $tutorial) : route('admin.tutorials.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                @csrf
                @if($tutorial->exists)
                    @method('PUT')
                @endif

                <div class="space-y-2">
                    <label class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Judul Tutorial <span class="text-rose-500">*</span></label>
                    <input type="text" name="title" value="{{ old('title', $tutorial->title) }}" required class="w-full h-12 rounded-xl border border-slate-200 bg-slate-50/70 px-4 text-sm text-slate-800 shadow-sm focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition">
                </div>

                <div class="space-y-2">
                    <label class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Kategori</label>
                    <select name="category_id" class="w-full h-12 rounded-xl border border-slate-200 bg-slate-50/70 px-4 text-sm text-slate-800 shadow-sm focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition appearance-none">
                        <option value="">Pilih Kategori (Opsional)</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ old('category_id', $tutorial->category_id) == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="space-y-2" x-data="{ removeImage: false, previewUrl: null }">
                    <label class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Gambar Cover (Opsional)</label>

                    {{-- Current image preview --}}
                    @if($tutorial->image_path)
                        <div class="mb-4 relative group rounded-xl border border-slate-200 bg-slate-50 p-3 inline-flex flex-col gap-3" x-show="!removeImage">
                            <img src="{{ Storage::url($tutorial->image_path) }}" alt="Cover" class="h-40 max-w-sm object-contain rounded-lg">
                            <div class="flex items-center gap-3">
                                <span class="text-xs text-slate-400 truncate flex-1">{{ $tutorial->image_path }}</span>
                                <button type="button" @click="removeImage = true" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-rose-50 text-rose-600 border border-rose-200 hover:bg-rose-100 transition">
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                                    Hapus Gambar
                                </button>
                            </div>
                        </div>
                        {{-- Undo remove --}}
                        <div x-show="removeImage" class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 flex items-center justify-between">
                            <span class="text-sm text-amber-700 font-medium">Gambar akan dihapus saat disimpan.</span>
                            <button type="button" @click="removeImage = false" class="text-xs font-bold text-amber-600 hover:text-amber-800 underline transition">Batal Hapus</button>
                        </div>
                        <input type="hidden" name="remove_image" value="1" x-bind:disabled="!removeImage">
                    @endif

                    {{-- New image preview --}}
                    <template x-if="previewUrl">
                        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50/30 p-3 inline-block">
                            <img :src="previewUrl" class="h-40 max-w-sm object-contain rounded-lg">
                            <p class="text-xs text-emerald-600 font-medium mt-2">Preview gambar baru</p>
                        </div>
                    </template>

                    <input type="file" name="image" accept="image/*" @change="
                        const file = $event.target.files[0];
                        if (file) { previewUrl = URL.createObjectURL(file); removeImage = false; }
                        else { previewUrl = null; }
                    " class="w-full text-sm text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border file:border-emerald-200 file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 transition shadow-sm">
                    <p class="text-[10px] text-slate-400 mt-1">Format: JPG, PNG, WEBP. Maks 4MB.</p>
                </div>

                <div class="space-y-2">
                    <label class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Status Publikasi</label>
                    <div class="flex items-center gap-3 bg-slate-50 p-4 border border-slate-200 rounded-xl mt-1">
                        <input id="is_active" type="checkbox" name="is_active" value="1" {{ old('is_active', $tutorial->is_active) ? 'checked' : '' }} class="w-5 h-5 text-emerald-600 border-slate-300 rounded shadow-sm focus:ring-emerald-500 cursor-pointer">
                        <label for="is_active" class="text-sm font-medium text-slate-700 cursor-pointer">Tandai sebagai Aktif (Bisa dibaca user)</label>
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Konten Tutorial <span class="text-rose-500">*</span></label>
                    <textarea name="content" rows="15" required class="w-full rounded-xl border border-slate-200 bg-slate-50/70 p-4 text-sm font-mono text-slate-800 shadow-sm focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition" placeholder="Anda bisa menggunakan format standar text atau markdown disini...">{{ old('content', $tutorial->content) }}</textarea>
                </div>

                <div class="pt-6 border-t border-slate-100 flex items-center justify-end gap-4 mt-8">
                    <a href="{{ route('admin.tutorials.index') }}" class="text-sm font-semibold text-slate-500 hover:text-slate-800 transition">Batal</a>
                    <x-ui.button type="submit" variant="primary" class="shadow-md shadow-emerald-500/20">
                        {{ $tutorial->exists ? 'Update Tutorial' : 'Simpan Tutorial Baru' }}
                    </x-ui.button>
                </div>
            </form>
        </x-ui.panel>
    </div>
</x-app-layout>
