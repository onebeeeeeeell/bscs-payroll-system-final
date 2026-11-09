<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    public function __construct(
        private NotificationService $notificationService,
    ) {}

    // JSON list used by the bell in the top bar
    public function index(): JsonResponse
    {
        return response()->json(['notifications' => $this->notificationService->items()]);
    }
}