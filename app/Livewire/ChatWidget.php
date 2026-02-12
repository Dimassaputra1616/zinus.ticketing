<?php

namespace App\Livewire;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Livewire\Component;

class ChatWidget extends Component
{
    public $body = '';
    public $messages = [];
    public $conversationId = null;
    
    // Admin multi-user chat properties
    public $selectedUserId = null;
    public $userList = [];
    public $viewMode = 'chat'; // 'list' or 'chat'
    public $searchUser = '';
    public $totalUnreadCount = 0;
    public $isOpen = false;
    
    // User admin selection properties
    public $availableAdmins = [];
    public $selectedAdminId = null;
    public $showAdminSelection = false;

    public function mount()
    {
        if (Auth::user()->isAdmin()) {
            // Admin sees user list
            $this->viewMode = 'list';
            $this->loadUserList();
        } else {
            // Regular user sees own conversation
            $this->loadConversation();
            // Load available admins for selection
            $this->loadAdmins();
        }
    }

    public function loadUserList()
    {
        $query = \App\Models\User::where(function($q) {
            $q->where('is_admin', false)
              ->where('role', '!=', 'admin');
        });
        
        if ($this->searchUser) {
            $query->where(function($q) {
                $q->where('name', 'ilike', '%' . $this->searchUser . '%')
                  ->orWhere('email', 'ilike', '%' . $this->searchUser . '%');
            });
        }
        
        // Get users with latest message info
        $users = $query->with(['conversations' => function ($q) {
                // Get latest message for sorting
                $q->where('is_open', true)
                  ->with(['messages' => function ($mq) {
                      $mq->latest()->limit(1);
                  }])
                  ->with('assignedAdmin:id,name'); // Eager load admin info
            }])
            ->get()
            ->map(function ($user) {
                // Extract latest message timestamp for sorting
                $conversation = $user->conversations->first();
                $latestMessage = $conversation?->messages->first();
                $user->latest_message_at = $latestMessage?->created_at;
                $user->latest_message_body = $latestMessage?->body;
                
                // Count UNREAD MESSAGES (not conversations!)
                $user->unread_count = 0;
                if ($conversation) {
                    // Check assignment
                    $assignedAdminId = $conversation->assigned_admin_id;
                    $currentAdminId = Auth::id();
                    
                    // Only count if unassigned OR assigned to current admin
                    if ($assignedAdminId === null || $assignedAdminId == $currentAdminId) {
                        $user->unread_count = \App\Models\Message::where('conversation_id', $conversation->id)
                            ->where('is_read', false)
                            ->whereNotNull('user_id') // Only USER messages
                            ->count();
                    }
                }
                
                return $user;
            })
            ->sortByDesc('latest_message_at') // Users with newest messages at top
            ->values(); // Reset array keys
        
        $this->userList = $users;
        $this->totalUnreadCount = $users->sum('unread_count');
    }
    
    public function selectUser($userId)
    {
        $this->selectedUserId = $userId;
        $this->viewMode = 'chat';
        $this->loadConversation();
    }
    
    public function backToUserList()
    {
        $this->viewMode = 'list';
        $this->selectedUserId = null;
        $this->messages = [];
        $this->conversationId = null;
        $this->body = '';
        $this->loadUserList();
    }

    public function loadAdmins()
    {
        // Load all IT admins (users with is_admin = true OR role = 'admin')
        $this->availableAdmins = \App\Models\User::where(function($q) {
                $q->where('is_admin', true)
                  ->orWhere('role', 'admin');
            })
            ->select('id', 'name', 'email')
            ->get()
            ->toArray();
        
        // Show admin selection if:
        // 1. User hasn't selected admin yet
        // 2. Conversation doesn't have assigned admin yet
        $conversation = Conversation::where('user_id', Auth::id())->first();
        if (!$conversation || !$conversation->assigned_admin_id) {
            $this->showAdminSelection = true;
        } else {
            // Auto-select existing assigned admin
            $this->selectedAdminId = $conversation->assigned_admin_id;
            $this->showAdminSelection = false;
        }
    }

    public function selectAdmin($adminId)
    {
        $this->selectedAdminId = $adminId;
        $this->showAdminSelection = false;
        
        // If conversation exists, assign admin immediately
        if ($this->conversationId) {
            $conversation = Conversation::find($this->conversationId);
            if ($conversation) {
                // Force update assigned admin (useful for first selection or changing admin)
                $conversation->update(['assigned_admin_id' => $adminId]);
            }
        }
    }

    public function changeAdmin()
    {
        // Show admin selection again
        $this->showAdminSelection = true;
        $this->selectedAdminId = null;
    }


    
    public function updatedSearchUser()
    {
        $this->loadUserList();
    }
    
    public function getSelectedUserProperty()
    {
        if (!$this->selectedUserId) {
            return null;
        }
        
        return \App\Models\User::find($this->selectedUserId);
    }

    public function loadConversation()
    {
        // Skip loading conversation if admin is in list view
        if (Auth::user()->isAdmin() && $this->viewMode === 'list') {
            return;
        }

        if (Auth::user()->isAdmin() && $this->selectedUserId) {
            // Admin viewing specific user's conversation
            $conversation = Conversation::where('user_id', $this->selectedUserId)
                ->where('is_open', true)
                ->first();
                
            if (!$conversation) {
                // No conversation yet for this user
                $this->conversationId = null;
                $this->messages = [];
                return;
            }
        } else {
            // Regular user - get or load their own conversation
            $conversation = Conversation::where('user_id', Auth::id())
                ->where('is_open', true)
                ->first();
        }

        if ($conversation) {
            $this->conversationId = $conversation->id;
            
            // For regular users, calculate total unread count before loading messages (which might mark as read)
            if (!Auth::user()->isAdmin()) {
                $this->totalUnreadCount = Message::where('conversation_id', $this->conversationId)
                    ->where('user_id', '!=', Auth::id()) // Messages from admin/others
                    ->where('is_read', false)
                    ->count();
            }
            
            $this->loadMessages();
        } else {
            $this->conversationId = null;
            $this->messages = [];
            $this->totalUnreadCount = 0;
        }
    }

    public function loadMessages()
    {
        if (!$this->conversationId) {
            $this->messages = [];
            return;
        }

        // Get assigned admin name for fallback (if message user is null)
        $conversation = Conversation::with('assignedAdmin')->find($this->conversationId);
        $adminName = $conversation && $conversation->assignedAdmin ? $conversation->assignedAdmin->name : 'IT Support';

        $this->messages = Message::where('conversation_id', $this->conversationId)
            ->with('user:id,name')
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($message) use ($adminName) {
                return [
                    'id' => $message->id,
                    'body' => $message->body,
                    'user_id' => $message->user_id,
                    'user_name' => $message->user->name ?? $adminName,
                    'is_mine' => \Illuminate\Support\Facades\Auth::user()->isAdmin() 
                        ? is_null($message->user_id) 
                        : $message->user_id === \Illuminate\Support\Facades\Auth::id(),
                    'created_at' => $message->created_at->format('H:i'),
                ];
            })
            ->toArray();

        // Mark unread messages as read ONLY when user is viewing chat AND widget is open
        // Don't mark as read when admin is in list view (viewMode='list')
        $shouldMarkAsRead = Auth::user()->isAdmin()
            ? $this->viewMode === 'chat' // Admin: only when viewing specific chat
            : true; // Regular user: always mark as read IF open

        // Only mark as read if the widget is actually OPEN in the frontend
        if ($shouldMarkAsRead && $this->isOpen) {
            Message::where('conversation_id', $this->conversationId)
                ->where('user_id', '!=', Auth::id())
                ->where('is_read', false)
                ->update(['is_read' => true]);
                
            // Update counts immediately after reading
            if (Auth::user()->isAdmin()) {
                // Admin in chat view: we don't recalculate total list count here efficiently, 
                // but since we are in chat view, total count should decrease by the number of messages read?
                // Actually, if admin is in chat view, the list is hidden.
                // But for badge correctness when they close, we might want to update.
                // Let's leave totalUnreadCount as is for now, it will refresh when they go back to list.
            } else {
                $this->totalUnreadCount = 0;
            }
        }
    }

    public function sendMessage()
    {
        // Atomic lock to prevent double submission race conditions
        $lockKey = 'chat_send_message_' . Auth::id();
        $lock = \Illuminate\Support\Facades\Cache::lock($lockKey, 5); // 5 seconds lock

        if (!$lock->get()) {
            // If locked, it means another request is processing. Ignore this one.
            return;
        }

        try {
            $this->validate([
                'body' => 'required|string|max:1000',
            ]);
    
            // Rate limiting: 5 messages per minute
            $key = 'chat-message:' . Auth::id();
            $maxAttempts = 5;
            $decayMinutes = 1;
    
            if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($key, $maxAttempts)) {
                $seconds = \Illuminate\Support\Facades\RateLimiter::availableIn($key);
                $this->addError('body', "Terlalu banyak pesan. Silakan tunggu {$seconds} detik.");
                return;
            }
    
            // Determine target user ID
            $targetUserId = Auth::user()->isAdmin() && $this->selectedUserId 
                ? $this->selectedUserId 
                : Auth::id();
    
            // Check if conversation exists
            $conversation = Conversation::where('user_id', $targetUserId)
                ->where('is_open', true)
                ->first();
    
            // If no conversation exists, create one
            if (!$conversation) {
                $conversation = Conversation::create([
                    'user_id' => $targetUserId,
                    'is_open' => true,
                ]);
                $this->conversationId = $conversation->id;
            }
    
            // Assign admin if this is regular user's first message and admin is selected
            if (!Auth::user()->isAdmin() && $this->selectedAdminId && !$conversation->assigned_admin_id) {
                $conversation->update(['assigned_admin_id' => $this->selectedAdminId]);
            }
    
    
            // Check for duplicate message (same as last message)
            $lastMessageQuery = Message::where('conversation_id', $conversation->id)
                ->latest()
                ->first();
                
            // For admin, check last message from admin (user_id is null)
            // For regular user, check last message from user
            if (Auth::user()->isAdmin()) {
                $lastMessage = Message::where('conversation_id', $conversation->id)
                    ->whereNull('user_id')
                    ->latest()
                    ->first();
            } else {
                $lastMessage = Message::where('conversation_id', $conversation->id)
                    ->where('user_id', Auth::id())
                    ->latest()
                    ->first();
            }
    
            if ($lastMessage && $lastMessage->body === $this->body) {
                $this->addError('body', 'Pesan duplikat. Silakan kirim pesan yang berbeda.');
                return;
            }
    
            // Save the message
            // Admin messages have user_id = null, regular user messages have user_id = auth id
            $message = Message::create([
                'conversation_id' => $conversation->id,
                'user_id' => Auth::user()->isAdmin() ? null : Auth::id(),
                'body' => $this->body,
                'is_read' => false,
            ]);
    
            // Dispatch event for n8n integration (only for user messages)
            if (!Auth::user()->isAdmin()) {
                \App\Events\MessageCreated::dispatch($message, 'user');
            }
    
            // Hit the rate limiter
            \Illuminate\Support\Facades\RateLimiter::hit($key, $decayMinutes * 60);
    
            // Reset input and reload messages
            $this->body = '';
            $this->loadMessages();
    
            // Dispatch browser event to scroll to bottom
            $this->dispatch('message-sent');

        } finally {
            $lock->release();
        }
    }

    public function render()
    {
        if ($this->viewMode === 'chat') {
            // Chat view: reload messages to keep chat fresh
            $this->loadMessages();
        } else {
            // List view: reload user list to refresh unread counts
            $this->loadUserList();
        }

        return view('livewire.chat-widget');
    }
}
