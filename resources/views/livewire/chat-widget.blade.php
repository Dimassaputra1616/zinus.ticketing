<div x-data="{ open: @entangle('isOpen') }" class="fixed bottom-6 right-6 z-50" wire:poll.2s>
    <!-- Floating Action Button -->
    <button
        @click="open = !open"
        class="relative flex items-center justify-center w-14 h-14 bg-emerald-600 hover:bg-emerald-700 text-white rounded-full shadow-lg transition-all duration-200 hover:scale-110"
        :class="{ 'scale-0': open }"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
        </svg>
        @if($totalUnreadCount > 0)
            <span class="absolute top-0 right-0 -mt-1 -mr-1 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-[10px] font-bold text-white shadow-md ring-2 ring-white z-10">
                {{ $totalUnreadCount }}
            </span>
        @endif
    </button>

    <!-- Chat Box -->
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 transform scale-95"
        x-transition:enter-end="opacity-100 transform scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 transform scale-100"
        x-transition:leave-end="opacity-0 transform scale-95"
        @click.away="open = false"
        class="absolute bottom-0 right-0 mb-20 w-80 sm:w-96 bg-white rounded-lg shadow-2xl flex flex-col"
        style="height: 500px; display: none;"
        style="height: 500px; display: none;"
        x-init="
            $watch('open', value => {
                if (value) {
                    setTimeout(() => {
                        const container = $refs.chatContainer;
                        if (container) container.scrollTop = container.scrollHeight;
                    }, 100);
                }
            });
            
            window.addEventListener('message-sent', () => {
                setTimeout(() => {
                    const container = $refs.chatContainer;
                    if (container) container.scrollTop = container.scrollHeight;
                }, 100);
            });
        "
    >
        @if(auth()->user()->isAdmin() && $viewMode === 'list')
            <!-- ADMIN - USER LIST VIEW -->
            <div class="h-full flex flex-col">
                <!-- Header -->
                <div class="bg-white border-b border-gray-100 px-5 py-4 rounded-t-lg flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-[0.2em] bg-slate-100 text-[#23455D]/60">
                                ADMIN
                            </span>
                        </div>
                        <h3 class="font-semibold text-gray-900">Select User to Chat</h3>
                    </div>
                    <button @click="open = false" class="text-gray-400 hover:text-emerald-600 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Search Bar -->
                <div class="p-3 border-b bg-gray-50">
                    <input 
                        type="text" 
                        wire:model.live="searchUser" 
                        placeholder="Search users by name or email..." 
                        class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500"
                    >
                </div>

                <!-- User List -->
                <div class="flex-1 overflow-y-auto">
                    @forelse($userList as $user)
                        <div 
                            wire:click="selectUser({{ $user->id }})" 
                            class="p-4 border-b hover:bg-emerald-50 cursor-pointer transition-colors duration-150 flex items-center justify-between"
                        >
                            <div class="flex-1 min-w-0 pr-2">
                                <div class="text-sm font-semibold text-gray-900 truncate" title="{{ $user->email }}">{{ $user->email }}</div>
                                <div class="text-xs text-gray-500 truncate" title="{{ $user->latest_message_body }}">
                                    {{ $user->latest_message_body ?? 'Belum ada pesan' }}
                                </div>
                                @php
                                    $conversation = $user->conversations->first();
                                    $assignedToOther = $conversation && $conversation->assigned_admin_id && $conversation->assigned_admin_id != auth()->id();
                                @endphp
                                @if($assignedToOther)
                                    <div class="mt-1 flex items-center gap-1 text-[10px] text-gray-500 bg-gray-100 rounded px-1.5 py-0.5 w-fit border border-gray-200" title="Assigned to {{ $conversation->assignedAdmin->name }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                        </svg>
                                        <span class="truncate max-w-[100px] font-medium">
                                            {{ explode(' ', $conversation->assignedAdmin->name)[0] }}
                                        </span>
                                    </div>
                                @endif
                            </div>
                            @if($user->unread_count > 0)
                                <span class="bg-red-500 text-white text-xs px-2 py-1 rounded-full font-semibold">
                                    {{ $user->unread_count }}
                                </span>
                            @endif
                        </div>
                    @empty
                        <div class="p-8 text-center text-gray-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto mb-2 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                            </svg>
                            <p class="text-sm font-medium">No users found</p>
                            <p class="text-xs mt-1">Try adjusting your search</p>
                        </div>
                    @endforelse
                </div>
            </div>
        @else
            <!-- CHAT VIEW (Admin with selected user OR Regular user) -->
            <div class="h-full flex flex-col">
                @if(!auth()->user()->isAdmin() && $showAdminSelection)
                    <!-- USER - ADMIN SELECTION CARD -->
                    <div class="flex flex-col h-full">
                        <!-- Header -->
                        <!-- Header -->
                        <div class="bg-white border-b border-gray-100 px-5 py-4 rounded-t-lg">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-[0.2em] bg-slate-100 text-[#23455D]/60">
                                    IT SUPPORT
                                </span>
                            </div>
                            <h2 class="font-semibold text-sm text-gray-900">Pilih Admin IT</h2>
                            <p class="text-xs text-gray-500 mt-1">Silakan pilih admin untuk membantu Anda</p>
                        </div>

                        <!-- Admin List -->
                        <div class="flex-1 overflow-y-auto p-4 space-y-2">
                            @forelse($availableAdmins as $admin)
                                <button 
                                    wire:click="selectAdmin({{ $admin['id'] }})"
                                    class="w-full bg-white border-2 border-gray-200 hover:border-emerald-500 hover:bg-emerald-50 rounded-lg p-3 text-left transition-all duration-200 group"
                                >
                                    <div class="flex items-center gap-3">
                                        <div class="flex-shrink-0 w-10 h-10 bg-emerald-100 group-hover:bg-emerald-200 rounded-full flex items-center justify-center text-emerald-700 font-semibold">
                                            {{ strtoupper(substr($admin['name'], 0, 1)) }}
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="font-medium text-gray-900 text-sm truncate">{{ $admin['name'] }}</div>
                                            <div class="text-xs text-gray-500 truncate">{{ $admin['email'] }}</div>
                                        </div>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 group-hover:text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </div>
                                </button>
                            @empty
                                <div class="text-center py-8 text-gray-500">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto mb-3 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                    </svg>
                                    <p class="text-sm">Tidak ada admin tersedia</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                @else
                    <!-- NORMAL CHAT INTERFACE -->
                <!-- Header -->
                <div class="bg-white border-b border-gray-100 px-5 py-4 rounded-t-lg flex items-center justify-between">
                    @if(auth()->user()->isAdmin() && $selectedUserId)
                        <!-- Admin - Show back button and selected user info -->
                        <div class="flex items-center gap-2 flex-1">
                            <button 
                                wire:click="backToUserList" 
                                class="text-gray-400 hover:text-emerald-600 transition-colors"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                </svg>
                            </button>
                            <div class="flex-1">
                                <div class="font-semibold text-sm text-gray-900">{{ $selectedUser->name ?? 'User' }}</div>
                                <div class="text-xs text-gray-500">{{ $selectedUser->email ?? '' }}</div>
                            </div>
                        </div>
                    @else
                        <!-- Regular user - Show standard header with back button -->
                        <div class="flex items-center justify-between flex-1">
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-[0.2em] bg-slate-100 text-[#23455D]/60">
                                        IT SUPPORT
                                    </span>
                                </div>
                                <h2 class="font-semibold text-sm text-gray-900">Live Chat Support</h2>
                            </div>
                            
                            @if($selectedAdminId)
                                <!-- Back button to change admin -->
                                <button 
                                    wire:click="changeAdmin" 
                                    class="text-gray-400 hover:text-emerald-600 transition-colors flex items-center gap-1 text-xs"
                                    title="Ganti Admin"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    Ganti Admin
                                </button>
                            @endif
                        </div>
                    @endif
                    <button @click="open = false" class="text-gray-400 hover:text-emerald-600 transition ml-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Messages Container -->
                <div
                    x-ref="chatContainer"
                    class="flex-1 overflow-y-auto p-4 space-y-3 bg-gray-50"
                    style="scroll-behavior: smooth;"
                >
                    @if(count($messages) === 0)
                        <div class="flex flex-col items-center justify-center h-full text-gray-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                            <p class="text-sm">No messages yet</p>
                            <p class="text-xs">Start a conversation{{ auth()->user()->isAdmin() ? '' : ' with support' }}</p>
                        </div>
                    @else
                        @foreach($messages as $message)
                            <div class="flex {{ $message['is_mine'] ? 'justify-end' : 'justify-start' }}">
                                <div class="max-w-[75%]">
                                    <!-- Message Bubble -->
                                    <div class="
                                        {{ $message['is_mine'] 
                                            ? 'bg-emerald-500 text-white rounded-l-lg rounded-tr-lg' 
                                            : 'bg-gray-300 text-gray-800 rounded-r-lg rounded-tl-lg' 
                                        }}
                                        px-4 py-2 shadow-sm
                                    ">
                                        @if(!$message['is_mine'])
                                            <p class="text-xs font-semibold mb-1 opacity-75">{{ $message['user_name'] }}</p>
                                        @endif
                                        <p class="text-sm break-words">{{ $message['body'] }}</p>
                                    </div>
                                    
                                    <!-- Timestamp -->
                                    <p class="text-xs text-gray-400 mt-1 {{ $message['is_mine'] ? 'text-right' : 'text-left' }}">
                                        {{ $message['created_at'] }}
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>

                <!-- Input Area -->
                <div class="border-t border-gray-200 p-3 bg-white rounded-b-lg">
                    <form wire:submit.prevent="sendMessage" class="flex gap-2">
                        <input
                            type="text"
                            wire:model="body"
                            placeholder="Type your message..."
                            class="flex-1 border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                            autocomplete="off"
                        >
                        <button
                            type="submit"
                            class="bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg px-4 py-2 transition-colors duration-200 flex items-center justify-center"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                            </svg>
                        </button>
                    </form>
                    
                    @error('body')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                @endif
            </div>
        @endif
    </div>
</div>
