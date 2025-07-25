<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\ApiController;
use App\Models\NotificationTemplate;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationTemplateController extends ApiController
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function index(Request $request): JsonResponse
    {
        $query = NotificationTemplate::query();

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $templates = $query->latest()->paginate();
        return $this->paginatedResponse($templates);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'email_template' => 'required_if:type,email,both|string',
            'sms_template' => 'required_if:type,sms,both|string',
            'variables' => 'required|array',
            'type' => 'required|in:email,sms,both',
        ]);

        $template = NotificationTemplate::create($request->all());
        return $this->successResponse($template, 'Template created successfully', 201);
    }

    public function show(NotificationTemplate $template): JsonResponse
    {
        return $this->successResponse($template);
    }

    public function update(Request $request, NotificationTemplate $template): JsonResponse
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'subject' => 'sometimes|string|max:255',
            'email_template' => 'required_if:type,email,both|string',
            'sms_template' => 'required_if:type,sms,both|string',
            'variables' => 'sometimes|array',
            'type' => 'sometimes|in:email,sms,both',
        ]);

        $template->update($request->all());
        return $this->successResponse($template, 'Template updated successfully');
    }

    public function destroy(NotificationTemplate $template): JsonResponse
    {
        $template->delete();
        return $this->successResponse(null, 'Template deleted successfully');
    }

    public function preview(Request $request, NotificationTemplate $template): JsonResponse
    {
        $request->validate([
            'variables' => 'required|array',
        ]);

        $preview = $this->notificationService->preview(
            template: $template,
            data: $request->variables
        );

        return $this->successResponse($preview);
    }
}