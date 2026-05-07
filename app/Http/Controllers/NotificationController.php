<?php

namespace App\Http\Controllers;

use App\Http\Resources\NotificationResource;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    /**
     * Listar notificaciones del usuario autenticado.
     */
    public function index(Request $request): JsonResponse
    {
        $notifications = $this->notificationService->userNotifications(
            auth()->id(),
            $request->all()
        );

        return response()->json([
            'success' => true,
            'data' => NotificationResource::collection($notifications),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
            ],
        ]);
    }

    /**
     * Marcar notificación como leída.
     */
    public function markAsRead(int $id): JsonResponse
    {
        $notification = $this->notificationService->markAsRead($id);

        return response()->json([
            'success' => true,
            'message' => 'Notificación marcada como leída.',
            'data' => new NotificationResource($notification),
        ]);
    }

    /**
     * Marcar todas las notificaciones como leídas.
     */
    public function markAllAsRead(): JsonResponse
    {
        $this->notificationService->markAllAsRead(auth()->id());

        return response()->json([
            'success' => true,
            'message' => 'Todas las notificaciones marcadas como leídas.',
        ]);
    }

    /**
     * Obtener conteo de notificaciones no leídas.
     */
    public function unreadCount(): JsonResponse
    {
        $count = $this->notificationService->unreadCount(auth()->id());

        return response()->json([
            'success' => true,
            'data' => ['unread_count' => $count],
        ]);
    }

    /**
     * Eliminar una notificación.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->notificationService->delete($id);

        return response()->json([
            'success' => true,
            'message' => 'Notificación eliminada.',
        ]);
    }
}
