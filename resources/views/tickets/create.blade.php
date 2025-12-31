@extends('layouts.public')

@section('content')
<div class="max-w-lg mx-auto mt-10 bg-white shadow-md rounded p-6">
    <h2 class="text-2xl font-bold mb-4">Buat Tiket IT Baru</h2>

    {{-- Notifikasi sukses --}}
    @if(session('success'))
        <div class="bg-green-100 text-green-700 p-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    <form method="POST" action="{{ route('tickets.store') }}" enctype="multipart/form-data" data-ticket-form>
        @csrf
        <input type="hidden" name="idempotency_key" value="" data-idempotency-key>

        <div class="mb-3">
            <label class="block text-sm font-medium text-gray-700 mb-1">Judul Tiket</label>
            <input type="text" name="title" class="w-full border rounded p-2" required>
        </div>

        <div class="mb-3">
            <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
            <textarea name="description" rows="4" class="w-full border rounded p-2" required></textarea>
        </div>

        <div class="mb-3">
            <label class="block text-sm font-medium text-gray-700 mb-1">Kategori</label>
            <select name="category_id" class="w-full border rounded p-2" required>
                @forelse(($categories ?? []) as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @empty
                    <option disabled>Belum ada kategori</option>
                @endforelse
            </select>
        </div>

        <div class="mb-3">
            <label class="block text-sm font-medium text-gray-700 mb-1">Departemen</label>
            <select name="department_id" class="w-full border rounded p-2" required>
                @forelse(($departments ?? []) as $department)
                    <option value="{{ $department->id }}">{{ $department->name }}</option>
                @empty
                    <option disabled>Belum ada departemen</option>
                @endforelse
            </select>
        </div>

        <div class="mb-4 space-y-2" data-file-preview>
            <label class="block text-sm font-medium text-gray-700">Lampiran (Opsional)</label>
            <div class="rounded-xl border border-dashed border-gray-300 bg-gray-50/80 px-4 py-4 text-sm text-gray-600">
                <input
                    type="file"
                    name="attachments[]"
                    multiple
                    class="block w-full text-xs text-gray-600 file:mr-4 file:rounded-lg file:border-0 file:bg-[#00bfa5] file:px-4 file:py-2 file:text-xs file:font-semibold file:uppercase file:tracking-wide file:text-white hover:file:bg-[#00a892]"
                    data-file-preview-input
                >
                <p class="mt-2 text-xs text-gray-500">Maks. 5 file, 5MB per file. Format: PDF, gambar, dokumen, ZIP.</p>
                <div class="mt-3 space-y-2" data-file-preview-list hidden></div>
            </div>
            @php $attachmentErrors = $errors->get('attachments.*'); @endphp
            @if (!empty($attachmentErrors))
                @foreach ($attachmentErrors as $messages)
                    @foreach ($messages as $message)
                        <p class="text-xs text-red-500">{{ $message }}</p>
                    @endforeach
                @endforeach
            @endif
        </div>

        <button type="submit" class="rounded bg-[#00bfa5] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#00a892] inline-flex items-center justify-center gap-2" data-submit-btn>
            <span data-submit-label>Kirim Tiket</span>
            <span class="hidden h-4 w-4 animate-spin rounded-full border-2 border-white/70 border-t-transparent" data-submit-spinner></span>
        </button>
    </form>
</div>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-ticket-form]').forEach(form => {
            const idempotencyInput = form.querySelector('[data-idempotency-key]');
            const submitBtn = form.querySelector('[data-submit-btn]');
            const submitLabel = form.querySelector('[data-submit-label]');
            const submitSpinner = form.querySelector('[data-submit-spinner]');

            const generateKey = () => {
                if (window.crypto?.randomUUID) {
                    return window.crypto.randomUUID();
                }
                return 'idemp-' + Math.random().toString(16).slice(2) + Date.now().toString(16);
            };

            if (idempotencyInput && !idempotencyInput.value) {
                idempotencyInput.value = generateKey();
            }

            form.addEventListener('submit', event => {
                if (form.dataset.submitted === 'true') {
                    event.preventDefault();
                    return;
                }
                form.dataset.submitted = 'true';
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.classList.add('opacity-70', 'cursor-not-allowed');
                }
                if (submitLabel) {
                    submitLabel.textContent = 'Mengirim...';
                }
                if (submitSpinner) {
                    submitSpinner.classList.remove('hidden');
                }
            });
        });
    });
</script>
@endsection
