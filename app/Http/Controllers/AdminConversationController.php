<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Read-only dispute/safety oversight — an admin can open any conversation to
 * investigate a complaint, but cannot post, edit, or delete anything here.
 * Reached from the Audit Log page, not its own bottom-nav/admin_nav entry
 * (deliberately, per the app owner).
 */
class AdminConversationController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));

        $conversations = Conversation::query()
            ->with('driver')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('trip_ref_snapshot', 'like', "%{$q}%")
                        ->orWhere('route_snapshot', 'like', "%{$q}%")
                        ->orWhereHas('driver', fn ($driverQuery) => $driverQuery->where('name', 'like', "%{$q}%"))
                        ->orWhereHas('participants.user', fn ($userQuery) => $userQuery->where('name', 'like', "%{$q}%"));
                });
            })
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.conversations.index', [
            'conversations' => $conversations,
            'q' => $q,
        ]);
    }

    public function show(Conversation $conversation): View
    {
        $conversation->load(['participants.user', 'driver']);

        $messages = Message::query()
            ->where('conversation_id', $conversation->id)
            ->with('sender')
            ->orderBy('id')
            ->get();

        return view('admin.conversations.show', [
            'conversation' => $conversation,
            'messages' => $messages,
        ]);
    }
}
