<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class NotificationController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $userId   = Auth::id();
        $filter   = $request->input('filter', 'all'); // all | unread | 1 | 2 | 3 | 4

        $query = Notification::with('sender')
            ->where('notifiable_id', $userId)
            ->where('notifiable_type', \App\Models\User::class)
            ->orderBy('created_at', 'desc');

        if ($filter === 'unread') {
            $query->whereNull('read_at');
        } elseif (in_array($filter, ['1', '2', '3', '4'])) {
            $query->where('type', (int) $filter);
        }

        $paginated = $query->paginate(20);

        $notifications = $paginated->getCollection()->map(fn($n) => [
            'id'          => $n->id,
            'type'        => $n->type,
            'type_label'  => $n->getTypeLabel(),
            'title'       => $n->title,
            'message'     => $n->message,
            'icon'        => $n->icon,
            'url'         => $n->url,
            'sender_name' => $n->sender?->name,
            'read'        => $n->isRead(),
            'created_at'  => $n->created_at->toISOString(),
            'time_label'  => $n->created_at->diffForHumans(),
        ]);

        $unreadCount = Notification::where('notifiable_id', $userId)
            ->where('notifiable_type', \App\Models\User::class)
            ->whereNull('read_at')
            ->count();

        return Inertia::render('admin/Notifications/ListNotifications', [
            'notifications' => $notifications,
            'unreadCount'   => $unreadCount,
            'filter'        => $filter,
            'hasMore'       => $paginated->hasMorePages(),
            'nextPageUrl'   => $paginated->nextPageUrl(),
        ]);
    }

    public function markRead(Notification $notification)
    {
        if ((string) $notification->notifiable_id !== (string) Auth::id()) {
            abort(403);
        }

        $notification->markAsRead();

        return response()->json(['success' => true]);
    }

    public function markAllRead()
    {
        $userId = Auth::id();

        Notification::where('notifiable_id', $userId)
            ->where('notifiable_type', \App\Models\User::class)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    }
}
