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
                <div class="bg-emerald-600 text-white px-4 py-3 rounded-t-lg flex items-center justify-between">
                    <h3 class="font-semibold">Select User to Chat</h3>
                    <button @click="open = false" class="text-white hover:text-gray-200 transition">
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
            <div class="h-full flex flex-col relative bg-gray-50 rounded-lg overflow-hidden">
                @if(!auth()->user()->isAdmin() && $showAdminSelection)
                    <!-- USER - ADMIN SELECTION CARD -->
                    <div class="flex flex-col h-full bg-white">
                        <!-- Header -->
                        <div class="bg-gradient-to-r from-emerald-600 to-emerald-500 text-white px-5 py-4 shadow-md z-10">
                            <h2 class="font-bold text-base tracking-wide">Pilih Admin IT</h2>
                            <p class="text-xs text-emerald-100 mt-1">Siapa yang ingin Anda hubungi?</p>
                        </div>

                        <!-- Admin List -->
                        <div class="flex-1 overflow-y-auto p-4 space-y-3">
                            @forelse($availableAdmins as $admin)
                                <button 
                                    wire:click="selectAdmin({{ $admin['id'] }})"
                                    class="w-full bg-white border border-gray-200 hover:border-emerald-500 hover:shadow-md rounded-xl p-4 text-left transition-all duration-300 group relative overflow-hidden"
                                >
                                    <div class="absolute inset-0 bg-emerald-50 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                                    <div class="relative flex items-center gap-4">
                                        <div class="flex-shrink-0 w-12 h-12 bg-gradient-to-br from-emerald-100 to-emerald-200 rounded-full flex items-center justify-center text-emerald-700 font-bold text-lg shadow-sm border-2 border-white ring-2 ring-emerald-50">
                                            {{ strtoupper(substr($admin['name'], 0, 1)) }}
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="font-bold text-gray-900 text-sm truncate group-hover:text-emerald-700 transition-colors">{{ $admin['name'] }}</div>
                                            <div class="text-xs text-gray-500 truncate">{{ $admin['email'] }}</div>
                                        </div>
                                        <div class="w-8 h-8 rounded-full bg-white border border-gray-100 flex items-center justify-center text-gray-400 group-hover:text-emerald-600 group-hover:border-emerald-200 transition-all shadow-sm">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                            </svg>
                                        </div>
                                    </div>
                                </button>
                            @empty
                                <div class="text-center py-10 text-gray-400">
                                    <div class="bg-gray-50 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-3">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                        </svg>
                                    </div>
                                    <p class="text-sm font-medium text-gray-500">Belum ada admin tersedia</p>
                                    <p class="text-xs mt-1">Silakan coba lagi nanti</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                @else
                    <!-- NORMAL CHAT INTERFACE -->
                
                <!-- Modern Sticky Header with Glass Effect -->
                <div class="absolute top-0 left-0 right-0 z-20 bg-emerald-600/90 backdrop-blur-sm text-white px-4 py-3 shadow-md flex items-center justify-between transition-all duration-300">
                    <div class="absolute inset-0 bg-gradient-to-r from-emerald-600 to-emerald-500 opacity-90"></div>
                    
                    <div class="relative flex items-center justify-between w-full">
                        @if(auth()->user()->isAdmin() && $selectedUserId)
                            <!-- Admin - Show back button and selected user info -->
                            <div class="flex items-center gap-3 flex-1 overflow-hidden">
                                <button 
                                    wire:click="backToUserList" 
                                    class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-white/20 transition-colors focus:outline-none"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                    </svg>
                                </button>
                                <div class="flex items-center gap-2 overflow-hidden">
                                    <div class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center text-xs font-bold ring-2 ring-white/30">
                                        {{ strtoupper(substr($selectedUser->name ?? 'U', 0, 1)) }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="font-bold text-sm truncate leading-tight">{{ $selectedUser->email ?? 'No Email' }}</div>
                                        <div class="text-[10px] text-emerald-100 truncate opacity-90">{{ $selectedUser->name ?? 'User' }}</div>
                                    </div>
                                </div>
                            </div>
                        @else
                            <!-- Regular user - Show standard header with branding and back button -->
                            <div class="flex items-center justify-between flex-1">
                                <div class="flex items-center gap-2">
                                    <div class="w-2 h-2 rounded-full bg-green-400 animate-pulse shadow-[0_0_8px_rgba(74,222,128,0.6)]"></div>
                                    <h2 class="font-bold text-sm tracking-wide">Live Support</h2>
                                </div>
                                
                                @if($selectedAdminId)
                                    <!-- Back button to change admin -->
                                    <button 
                                        wire:click="changeAdmin" 
                                        class="px-2 py-1 rounded-md bg-white/10 hover:bg-white/20 text-white transition-colors flex items-center gap-1.5 text-[10px] border border-white/10"
                                        title="Ganti Admin"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                        </svg>
                                        Ganti
                                    </button>
                                @endif
                            </div>
                        @endif
                        
                        <button @click="open = false" class="ml-2 w-8 h-8 flex items-center justify-center rounded-full hover:bg-white/20 transition-colors text-white/90 hover:text-white">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Messages Container -->
                <!-- Added pt-16 for header space and pb-20 for floating input space -->
                <div
                    x-ref="chatContainer"
                    class="flex-1 overflow-y-auto w-full pt-16 pb-24 px-4 space-y-4 bg-gray-50/50 custom-scrollbar"
                    style="scroll-behavior: smooth;"
                >
                    <style>
                        .custom-scrollbar::-webkit-scrollbar {
                            width: 5px;
                        }
                        .custom-scrollbar::-webkit-scrollbar-track {
                            background: transparent;
                        }
                        .custom-scrollbar::-webkit-scrollbar-thumb {
                            background: rgba(16, 185, 129, 0.2);
                            border-radius: 10px;
                        }
                        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
                            background: rgba(16, 185, 129, 0.5);
                        }
                    </style>

                    @if(count($messages) === 0)
                        <div class="flex flex-col items-center justify-center h-full text-gray-400 pb-10">
                            <div class="w-16 h-16 bg-gray-100 rounded-2xl flex items-center justify-center mb-4 shadow-inner">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                </svg>
                            </div>
                            <p class="text-sm font-medium text-gray-600">Belum ada pesan</p>
                            <p class="text-xs mt-1 text-gray-400">Mulai percakapan{{ auth()->user()->isAdmin() ? '' : ' dengan support' }} sekarang</p>
                        </div>
                    @else
                        @foreach($messages as $date => $dateMessages)
                            <!-- Date Separator -->
                            <div class="flex justify-center my-4 sticky top-0 z-10">
                                <span class="bg-gray-200/80 backdrop-blur-sm text-gray-500 text-[10px] px-3 py-1 rounded-full font-medium shadow-sm border border-white">
                                    {{ $date }}
                                </span>
                            </div>

                            @foreach($dateMessages as $message)
                                <div class="flex w-full {{ $message['is_mine'] ? 'justify-end' : 'justify-start' }} animate-fade-in-up">
                                    <div class="max-w-[80%] min-w-[30%]">
                                        <!-- Message Bubble -->
                                        <div class="relative group
                                            {{ $message['is_mine'] 
                                                ? 'bg-gradient-to-br from-emerald-500 to-emerald-600 text-white rounded-2xl rounded-tr-none shadow-md shadow-emerald-500/20 border border-emerald-500/10' 
                                                : 'bg-white text-gray-800 rounded-2xl rounded-tl-none shadow-sm border border-gray-100' 
                                            }}
                                            px-4 py-3 transition-all duration-200 hover:shadow-lg
                                        ">
                                        <!-- Message Content -->
                                        <div class="relative z-10">
                                            <p class="text-[10px] font-bold mb-1 uppercase tracking-wider {{ $message['is_mine'] ? 'text-emerald-100 text-right' : 'text-emerald-600 text-left' }}">
                                                {{ $message['user_name'] }}
                                            </p>
                                            <p class="text-sm break-words leading-relaxed {{ $message['is_mine'] ? 'text-white' : 'text-gray-700' }}">
                                                {{ $message['body'] }}
                                                
                                                <!-- WhatsApp Style Timestamp & Status (Float Right/Bottom) -->
                                                <span class="float-right flex items-center gap-1 ml-3 mt-2 h-3 text-[10px] {{ $message['is_mine'] ? 'text-emerald-50' : 'text-gray-400' }}">
                                                    <span>{{ $message['created_at'] }}</span>
                                                    @if($message['is_mine'])
                                                        <span class="{{ $message['is_read'] ? 'text-blue-200' : 'text-emerald-200' }}"> <!-- Blue-200 for read on emerald bg, Emerald-200 for sent -->
                                                            @if($message['is_read'])
                                                                <!-- Double Tick -->
                                                                <span class="flex -space-x-1">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                                                </span>
                                                            @else
                                                                <!-- Single Tick -->
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                                            @endif
                                                        </span>
                                                    @endif
                                                </span>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        @endforeach
                    @endif
                </div>

                <!-- Floating Input Area -->
                <div class="absolute bottom-4 left-4 right-4 z-20">
                    <div class="bg-white rounded-full shadow-lg shadow-gray-200/50 border border-gray-100 p-1.5 pl-4 flex items-center gap-2 ring-1 ring-gray-50 focus-within:ring-2 focus-within:ring-emerald-500/30 focus-within:border-emerald-500 transition-all duration-300">
                        <form wire:submit.prevent="sendMessage" class="flex-1 flex gap-2">
                            <input
                                type="text"
                                wire:model="body"
                                placeholder="Ketik pesan..."
                                class="flex-1 bg-transparent border-none text-sm text-gray-700 placeholder-gray-400 focus:ring-0 focus:outline-none py-2"
                                autocomplete="off"
                            >
                            <button
                                type="submit"
                                wire:loading.attr="disabled"
                                wire:target="sendMessage"
                                class="w-9 h-9 flex-shrink-0 bg-emerald-600 hover:bg-emerald-700 text-white rounded-full transition-all duration-200 flex items-center justify-center disabled:opacity-50 disabled:cursor-not-allowed hover:shadow-md hover:scale-105 active:scale-95"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transform rotate-90 ml-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
                
                @error('body')
                    <div class="absolute bottom-16 left-0 right-0 px-6 text-center animate-bounce">
                        <span class="bg-red-500/90 backdrop-blur-sm text-white text-[10px] px-3 py-1 rounded-full shadow-md">
                            {{ $message }}
                        </span>
                    </div>
                @enderror
                @endif
            </div>
        @endif
    </div>
</div>
