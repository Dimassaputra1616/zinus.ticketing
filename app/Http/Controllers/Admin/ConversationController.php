<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ConversationController extends Controller
{
    /**
     * Display a listing of conversations.
     */
    public function index(Request $request)
    {
        $query = Conversation::query()
            ->with(['user:id,name,email', 'latestMessage.user'])
            ->withUnreadCount()
            ->withActivity()
            ->latestActivity();

        // Filter by status
        if ($request->has('status')) {
            if ($request->status === 'open') {
                $query->where('is_open', true);
            } elseif ($request->status === 'closed') {
                $query->where('is_open', false);
            }
        }

        // Search by user name
        if ($request->filled('search')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%');
            });
        }

        $conversations = $query->paginate(20);

        return view('admin.conversations.index', compact('conversations'));
    }

    /**
     * Display a specific conversation.
     */
    public function show(Conversation $conversation)
    {
        $conversation->load(['user:id,name,email,created_at', 'messages.user:id,name']);
        
        // Mark all messages as read
        $conversation->markAsRead();

        return view('admin.conversations.show', compact('conversation'));
    }

    /**
     * Send a reply to a conversation.
     */
    public function reply(Request $request, Conversation $conversation)
    {
        $request->validate([
            'body' => 'required|string|max:1000',
        ]);

        // Admin reply - user_id is null for admin messages
        Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => null, // null indicates admin message
            'body' => $request->body,
            'is_read' => false, // User hasn't read admin reply yet
        ]);

        // Admin is now handling this chat: assign admin and disable bot handoff.
        $conversation->update([
            'assigned_admin_id' => Auth::id(),
            'is_bot_active' => false,
        ]);

        return redirect()
            ->route('admin.conversations.show', $conversation)
            ->with('success', 'Balasan berhasil dikirim.');
    }

    /**
     * Close a conversation.
     */
    public function close(Conversation $conversation)
    {
        $conversation->close();

        return redirect()
            ->route('admin.conversations.index')
            ->with('success', 'Percakapan berhasil ditutup.');
    }

    /**
     * Reopen a conversation.
     */
    public function reopen(Conversation $conversation)
    {
        $conversation->reopen();

        return redirect()
            ->route('admin.conversations.show', $conversation)
            ->with('success', 'Percakapan berhasil dibuka kembali.');
    }
}
