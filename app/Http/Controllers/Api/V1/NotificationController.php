<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\ApiController;
use App\Models\Notification;
use App\Models\NotificationTemplate;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends ApiController
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function index(Request $request): JsonResponse
    {
        $query = Notification::query();

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('read')) {
            $query->whereNotNull('read_at');
        }

        $notifications = $query->latest()->paginate();
        return $this->paginatedResponse($notifications);
    }

    public function show(Notification $notification): JsonResponse
    {
        return $this->successResponse($notification);
    }

    public function send(Request $request): JsonResponse
    {
        $request->validate([
            'template_id' => 'required|exists:notification_templates,id',
            'recipients' => 'required|array',
            'recipients.*' => 'required|string|email',
            'variables' => 'required|array',
            'channel' => 'required|string|in:email,sms,both',
            'priority' => 'nullable|string|in:low,medium,high',
        ]);

        try {
            $template = NotificationTemplate::findOrFail($request->template_id);
            
            foreach ($request->recipients as $recipient) {
                $this->notificationService->send(
                    to: $recipient,
                    template: $template,
                    data: $request->variables,
                    channel: $request->channel,
                    priority: $request->priority ?? 'low'
                );
            }

            return $this->successResponse(null, 'Notifications queued successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to send notifications: ' . $e->getMessage());
        }
    }

    public function sendBulk(Request $request): JsonResponse
    {
        $request->validate([
            'template_id' => 'required|exists:notification_templates,id',
            'recipients' => 'required|array',
            'recipients.*' => 'required|string|email',
            'variables' => 'required|array',
            'channel' => 'required|string|in:email,sms,both',
            'priority' => 'nullable|string|in:low,medium,high',
        ]);

        try {
            $template = NotificationTemplate::findOrFail($request->template_id);
            
            $this->notificationService->sendBulk(
                recipients: $request->recipients,
                template: $template,
                data: $request->variables,
                channel: $request->channel,
                priority: $request->priority ?? 'low'
            );

            return $this->successResponse(null, 'Bulk notifications queued successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to send bulk notifications: ' . $e->getMessage());
        }
    }

    public function markAsRead(Request $request): JsonResponse
    {
        $request->validate([
            'notifications' => 'required|array',
            'notifications.*' => 'exists:notifications,id'
        ]);

        Notification::whereIn('id', $request->notifications)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return $this->successResponse(null, 'Notifications marked as read');
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        auth()->user()
            ->notifications()
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return $this->successResponse(null, 'All notifications marked as read');
    }

    public function destroy(Notification $notification): JsonResponse
    {
        $notification->delete();
        return $this->successResponse(null, 'Notification deleted successfully');
    }
}