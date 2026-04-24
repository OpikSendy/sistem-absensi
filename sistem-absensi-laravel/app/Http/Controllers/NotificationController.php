<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Notification;

class NotificationController extends Controller
{
    public function unread()
    {
        $user = auth()->user();
        if (!$user) return response()->json(['count' => 0, 'data' => []]);

        $notifications = Notification::where('user_id', $user->id)
                            ->where('is_read', false)
                            ->orderBy('created_at', 'desc')
                            ->get();

        return response()->json([
            'count' => $notifications->count(),
            'data' => $notifications
        ]);
    }

    public function markAsRead(Request $request)
    {
        $user = auth()->user();
        if (!$user) return response()->json(['ok' => false]);

        if ($request->has('id')) {
            Notification::where('user_id', $user->id)
                        ->where('id', $request->input('id'))
                        ->update(['is_read' => true]);
        } else {
            // Mark all as read
            Notification::where('user_id', $user->id)
                        ->update(['is_read' => true]);
        }

        return response()->json(['ok' => true]);
    }
}
