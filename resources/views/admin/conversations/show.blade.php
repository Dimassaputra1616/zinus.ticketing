<x-app-layout>
    <div class="w-full pt-4 sm:pt-6 pb-8 space-y-6">
        @if (session('success'))
            <div class="flex items-start gap-3 rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-emerald-800 shadow-sm">
                <span class="mt-0.5 inline-flex h-10 w-10 items-center justify-center rounded-lg bg-white text-emerald-700 shadow-inner">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 6 9 17l-5-5" /></svg>
                </span>
                <div>
                    <p class="text-sm font-semibold">{{ __('messages.success') }}</p>
                    <p class="text-sm text-emerald-700/80">{{ session('success') }}</p>
                </div>
            </div>
        @endif

        <div class="flex items-center gap-4">
            <a href="{{ route('admin.conversations.index') }}" class="inline-flex items-center gap-2 text-sm text-slate-600 hover:text-emerald-600 transition">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5m0 0l7 7m-7-7l7-7"/></svg>
                &larr; {{ __('messages.back') }}
            </a>
        </div>

        <x-ui.section-hero
            pill="Conversation Detail"
            title="{{ __('messages.chat_with', ['name' => $conversation->user->name]) }}"
            description="{{ __('messages.chat_with_desc') }}"
        >
            <x-slot:pillIcon>
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
            </x-slot:pillIcon>
        </x-ui.section-hero>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Chat Thread -->
            <div class="lg:col-span-2">
                <div class="rounded-2xl border border-[#CFEADF] surface-card bg-white overflow-hidden flex flex-col" style="height: 600px">
                    <!-- Messages Container -->
                    <div 
                        class="flex-1 overflow-y-auto p-6 space-y-4 bg-gray-50" 
                        style="scroll-behavior: smooth;"
                        id="chat-container"
                        x-data
                        x-init="setTimeout(() => { $el.scrollTop = $el.scrollHeight; }, 100)"
                    >
                        @forelse ($conversation->messages as $message)
                            <div class="flex {{ $message->user_id ? 'justify-end' : 'justify-start' }}">
                                <div class="max-w-[75%]">
                                    <div class="
                                        {{ $message->user_id 
                                            ? 'bg-emerald-500 text-white rounded-l-lg rounded-tr-lg' 
                                            : 'bg-blue-500 text-white rounded-r-lg rounded-tl-lg' 
                                        }}
                                        px-4 py-3 shadow-sm
                                    ">
                                        @if (!$message->user_id)
                                            <p class="text-xs font-semibold mb-1 opacity-90">Admin</p>
                                        @else
                                            <p class="text-xs font-semibold mb-1 opacity-90">{{ $message->user->name }}</p>
                                        @endif
                                        <p class="text-sm break-words">{{ $message->body }}</p>
                                    </div>
                                    <p class="text-xs text-gray-400 mt-1 {{ $message->user_id ? 'text-right' : 'text-left' }}">
                                        {{ $message->created_at->format('d M Y, H:i') }}
                                    </p>
                                </div>
                            </div>
                        @empty
                            <div class="flex flex-col items-center justify-center h-full text-gray-400">
                                <svg class="h-16 w-16 mb-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
                                <p class="text-sm">{{ __('messages.no_messages') }}</p>
                            </div>
                        @endforelse
                    </div>

                    <!-- Reply Form -->
                    @if ($conversation->is_open)
                        <div class="border-t border-gray-200 p-4 bg-white">
                            <form method="POST" action="{{ route('admin.conversations.reply', $conversation) }}" class="flex gap-3">
                                @csrf
                                <input
                                    type="text"
                                    name="body"
                                    placeholder="{{ __('messages.type_reply') }}"
                                    required
                                    class="flex-1 border border-gray-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                                    autocomplete="off"
                                >
                                <button
                                    type="submit"
                                    class="bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl px-6 py-3 transition-colors duration-200 flex items-center gap-2 font-medium"
                                >
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" /></svg>
                                    {{ __('messages.send_reply') }}
                                </button>
                            </form>
                            @error('body')
                                <p class="text-red-500 text-xs mt-2">{{ $message }}</p>
                            @enderror
                        </div>
                    @else
                        <div class="border-t border-gray-200 p-4 bg-gray-50 text-center">
                            <p class="text-sm text-gray-600">{{ __('messages.conversation_closed_msg') }}</p>
                            <form method="POST" action="{{ route('admin.conversations.reopen', $conversation) }}" class="mt-3">
                                @csrf
                                <button type="submit" class="text-sm text-emerald-600 hover:text-emerald-700 font-medium">
                                    {{ __('messages.reopen') }}
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- User Info -->
                <div class="rounded-2xl border border-[#CFEADF] surface-card bg-gradient-to-br from-[#F6F9F8] via-white to-[#EDF3F2] p-6 space-y-4">
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-600">{{ __('messages.user_info') }}</h3>
                    <div class="space-y-3">
                        <div>
                            <p class="text-xs text-slate-500">{{ __('messages.full_name') }}</p>
                            <p class="text-sm font-semibold text-slate-800">{{ $conversation->user->name }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500">{{ __('messages.email') }}</p>
                            <p class="text-sm text-slate-700">{{ $conversation->user->email }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500">{{ __('messages.joined') }}</p>
                            <p class="text-sm text-slate-700">{{ $conversation->user->created_at->format('d M Y') }}</p>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="rounded-2xl border border-[#CFEADF] surface-card bg-gradient-to-br from-[#F6F9F8] via-white to-[#EDF3F2] p-6 space-y-4">
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-600">{{ __('messages.actions') }}</h3>
                    
                    @if ($conversation->is_open)
                        <form method="POST" action="{{ route('admin.conversations.close', $conversation) }}">
                            @csrf
                            <button type="submit" class="w-full btn-danger rounded-xl px-4 py-2.5 text-sm font-medium">
                                {{ __('messages.close_conversation') }}
                            </button>
                        </form>
                    @else
                        <div class="flex items-center gap-2 text-sm text-slate-600">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            {{ __('messages.conversation_closed') }}
                        </div>
                    @endif
                </div>

                <!-- Stats -->
                <div class="rounded-2xl border border-[#CFEADF] surface-card bg-gradient-to-br from-[#F6F9F8] via-white to-[#EDF3F2] p-6 space-y-4">
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-600">{{ __('messages.statistics') }}</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-slate-500">{{ __('messages.total_messages') }}</span>
                            <span class="text-sm font-semibold text-slate-800">{{ $conversation->messages->count() }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-slate-500">{{ __('messages.created_time') }}</span>
                            <span class="text-sm text-slate-700">{{ $conversation->created_at->diffForHumans() }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-slate-500">{{ __('messages.last_update_time') }}</span>
                            <span class="text-sm text-slate-700">{{ $conversation->updated_at->diffForHumans() }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        // Auto-refresh every 5 seconds
        setInterval(() => {
            location.reload();
        }, 5000);
    </script>
    @endpush
</x-app-layout>
