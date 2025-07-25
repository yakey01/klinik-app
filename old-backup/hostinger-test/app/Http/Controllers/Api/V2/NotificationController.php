<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Get user notifications
     */
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'nullable|in:reminder,alert,info,warning,success,error',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'is_read' => 'nullable|boolean',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = auth()->user();
        $filters = $request->only(['type', 'priority', 'is_read', 'per_page']);
        
        $notifications = $this->notificationService->getUserNotifications($user, $filters);

        return response()->json([
            'success' => true,
            'message' => 'Notifications retrieved',
            'data' => [
                'notifications' => $notifications->items(),
                'pagination' => [
                    'current_page' => $notifications->currentPage(),
                    'last_page' => $notifications->lastPage(),
                    'per_page' => $notifications->perPage(),
                    'total' => $notifications->total(),
                    'from' => $notifications->firstItem(),
                    'to' => $notifications->lastItem(),
                ],
                'unread_count' => $this->notificationService->getUnreadCount($user),
            ],
            'meta' => [
                'version' => '2.0',
                'timestamp' => now()->toISOString(),
                'request_id' => \Illuminate\Support\Str::uuid()->toString(),
            ]
        ]);
    }

    /**
     * Get unread notifications count
     */
    public function getUnreadCount()
    {
        $user = auth()->user();
        $count = $this->notificationService->getUnreadCount($user);

        return response()->json([
            'success' => true,
            'message' => 'Unread count retrieved',
            'data' => [
                'unread_count' => $count,
            ],
            'meta' => [
                'version' => '2.0',
                'timestamp' => now()->toISOString(),
                'request_id' => \Illuminate\Support\Str::uuid()->toString(),
            ]
        ]);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(Notification $notification)
    {
        $user = auth()->user();
        
        if ($notification->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to notification',
            ], 403);
        }

        $this->notificationService->markAsRead($notification);

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read',
            'data' => [
                'notification' => $notification->fresh(),
                'unread_count' => $this->notificationService->getUnreadCount($user),
            ],
            'meta' => [
                'version' => '2.0',
                'timestamp' => now()->toISOString(),
                'request_id' => \Illuminate\Support\Str::uuid()->toString(),
            ]
        ]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead()
    {
        $user = auth()->user();
        $count = $this->notificationService->markAllAsRead($user);

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read',
            'data' => [
                'marked_count' => $count,
                'unread_count' => 0,
            ],
            'meta' => [
                'version' => '2.0',
                'timestamp' => now()->toISOString(),
                'request_id' => \Illuminate\Support\Str::uuid()->toString(),
            ]
        ]);
    }

    /**
     * Get recent notifications (last 24 hours)
     */
    public function getRecent()
    {
        $user = auth()->user();
        
        $notifications = Notification::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subDay())
            ->notExpired()
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Recent notifications retrieved',
            'data' => [
                'notifications' => $notifications,
                'count' => $notifications->count(),
            ],
            'meta' => [
                'version' => '2.0',
                'timestamp' => now()->toISOString(),
                'request_id' => \Illuminate\Support\Str::uuid()->toString(),
            ]
        ]);
    }

    /**
     * Delete notification
     */
    public function destroy(Notification $notification)
    {
        $user = auth()->user();
        
        if ($notification->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to notification',
            ], 403);
        }

        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notification deleted',
            'data' => [
                'unread_count' => $this->notificationService->getUnreadCount($user),
            ],
            'meta' => [
                'version' => '2.0',
                'timestamp' => now()->toISOString(),
                'request_id' => \Illuminate\Support\Str::uuid()->toString(),
            ]
        ]);
    }

    /**
     * Get notification settings
     */
    public function getSettings()
    {
        $user = auth()->user();

        return response()->json([
            'success' => true,
            'message' => 'Notification settings retrieved',
            'data' => [
                'settings' => [
                    'email_notifications' => $user->email_notifications ?? true,
                    'push_notifications' => $user->push_notifications ?? true,
                    'attendance_reminders' => $user->attendance_reminders ?? true,
                    'schedule_updates' => $user->schedule_updates ?? true,
                ],
            ],
            'meta' => [
                'version' => '2.0',
                'timestamp' => now()->toISOString(),
                'request_id' => \Illuminate\Support\Str::uuid()->toString(),
            ]
        ]);
    }

    /**
     * Update notification settings
     */
    public function updateSettings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email_notifications' => 'boolean',
            'push_notifications' => 'boolean',
            'attendance_reminders' => 'boolean',
            'schedule_updates' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = auth()->user();
        $user->update($request->only([
            'email_notifications',
            'push_notifications',
            'attendance_reminders',
            'schedule_updates',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Notification settings updated',
            'data' => [
                'settings' => [
                    'email_notifications' => $user->email_notifications,
                    'push_notifications' => $user->push_notifications,
                    'attendance_reminders' => $user->attendance_reminders,
                    'schedule_updates' => $user->schedule_updates,
                ],
            ],
            'meta' => [
                'version' => '2.0',
                'timestamp' => now()->toISOString(),
                'request_id' => \Illuminate\Support\Str::uuid()->toString(),
            ]
        ]);
    }
}
