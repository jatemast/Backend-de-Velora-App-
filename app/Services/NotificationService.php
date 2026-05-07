<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;

class NotificationService
{
    /**
     * Enviar una notificación a un usuario.
     */
    public function send(int $userId, string $title, string $message, string $type = 'info', string $channel = 'in_app', ?array $data = null): Notification
    {
        return Notification::create([
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'channel' => $channel,
            'data' => $data,
            'sent_at' => now(),
        ]);
    }

    /**
     * Enviar notificación a múltiples usuarios.
     */
    public function sendBulk(array $userIds, string $title, string $message, string $type = 'info', string $channel = 'in_app', ?array $data = null): void
    {
        $notifications = [];
        $now = now();

        foreach ($userIds as $userId) {
            $notifications[] = [
                'user_id' => $userId,
                'title' => $title,
                'message' => $message,
                'type' => $type,
                'channel' => $channel,
                'data' => $data ? json_encode($data) : null,
                'sent_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        Notification::insert($notifications);
    }

    /**
     * Obtener notificaciones de un usuario.
     */
    public function userNotifications(int $userId, array $filters = [])
    {
        $query = Notification::where('user_id', $userId);

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['is_read'])) {
            $query->where('is_read', $filters['is_read'] === 'true');
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate($filters['per_page'] ?? 20);
    }

    /**
     * Marcar notificación como leída.
     */
    public function markAsRead(int $notificationId): ?Notification
    {
        $notification = Notification::findOrFail($notificationId);
        $notification->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
        return $notification->fresh();
    }

    /**
     * Marcar todas las notificaciones como leídas.
     */
    public function markAllAsRead(int $userId): void
    {
        Notification::where('user_id', $userId)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }

    /**
     * Obtener conteo de notificaciones no leídas.
     */
    public function unreadCount(int $userId): int
    {
        return Notification::where('user_id', $userId)
            ->where('is_read', false)
            ->count();
    }

    /**
     * Eliminar una notificación.
     */
    public function delete(int $notificationId): bool
    {
        $notification = Notification::findOrFail($notificationId);
        return $notification->delete();
    }

    // ============ NOTIFICACIONES PREDEFINIDAS ============

    /**
     * Notificar confirmación de reserva.
     */
    public function bookingConfirmation(int $userId, array $bookingData): Notification
    {
        return $this->send(
            $userId,
            'Reserva Confirmada',
            "Tu reserva en {$bookingData['club_name']} para el {$bookingData['date']} a las {$bookingData['time']} ha sido confirmada.",
            'booking_confirmation',
            'in_app',
            $bookingData
        );
    }

    /**
     * Notificar invitación a partido.
     */
    public function matchInvitation(int $userId, array $matchData): Notification
    {
        return $this->send(
            $userId,
            'Invitación a Partido',
            "Has sido invitado a un partido de {$matchData['match_type']} en {$matchData['club_name']}.",
            'match_invitation',
            'in_app',
            $matchData
        );
    }

    /**
     * Notificar pago recibido.
     */
    public function paymentReceived(int $userId, array $paymentData): Notification
    {
        return $this->send(
            $userId,
            'Pago Recibido',
            "Tu pago de \${$paymentData['amount']} por la reserva ha sido procesado exitosamente.",
            'payment_received',
            'in_app',
            $paymentData
        );
    }

    /**
     * Notificar recordatorio de reserva.
     */
    public function bookingReminder(int $userId, array $bookingData): Notification
    {
        return $this->send(
            $userId,
            'Recordatorio de Reserva',
            "Recuerda que tienes una reserva en {$bookingData['club_name']} mañana a las {$bookingData['time']}.",
            'reminder',
            'in_app',
            $bookingData
        );
    }
}
