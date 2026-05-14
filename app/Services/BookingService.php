<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingPlayer;
use App\Models\Court;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BookingService
{
    /**
     * Listar reservas del usuario autenticado.
     */
    public function userBookings(int $userId, array $filters = [])
    {
        $query = Booking::with(['court.club', 'players.user', 'payment'])
            ->where('user_id', $userId);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['from_date'])) {
            $query->where('booking_date', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->where('booking_date', '<=', $filters['to_date']);
        }

        return $query->orderBy('booking_date', 'desc')
            ->orderBy('start_time', 'desc')
            ->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Listar reservas de una cancha específica.
     */
    public function courtBookings(int $courtId, ?string $date = null)
    {
        $query = Booking::with('user')
            ->where('court_id', $courtId)
            ->whereIn('status', ['pending', 'confirmed']);

        if ($date) {
            $query->where('booking_date', $date);
        }

        return $query->orderBy('booking_date')
            ->orderBy('start_time')
            ->get();
    }

    /**
     * Crear una nueva reserva.
     */
    public function create(array $data, int $userId): Booking
    {
        return DB::transaction(function () use ($data, $userId) {
            $court = Court::findOrFail($data['court_id']);

            // Calcular duración y precio
            $startTime = Carbon::parse($data['start_time']);
            $endTime = Carbon::parse($data['end_time']);
            $durationMinutes = $startTime->diffInMinutes($endTime);
            $hours = $durationMinutes / 60;
            $totalPrice = $court->price_per_hour * $hours;

            $booking = Booking::create([
                'user_id' => $userId,
                'court_id' => $data['court_id'],
                'club_id' => $court->club_id,
                'booking_date' => $data['booking_date'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'duration_minutes' => $durationMinutes,
                'total_price' => $totalPrice,
                'status' => 'pending',
                'notes' => $data['notes'] ?? null,
                'players_count' => $data['players_count'] ?? 2,
            ]);

            // Si se incluyen jugadores invitados
            if (!empty($data['players'])) {
                foreach ($data['players'] as $playerId) {
                    $booking->players()->create([
                        'user_id' => $playerId,
                        'status' => 'pending',
                    ]);
                }
            }

            return $booking->load(['court.club', 'players.user']);
        });
    }

    /**
     * Confirmar una reserva.
     */
    public function confirm(int $id): ?Booking
    {
        $booking = Booking::findOrFail($id);
        $booking->update(['status' => 'confirmed']);
        return $booking->fresh();
    }

    /**
     * Cancelar una reserva.
     */
    public function cancel(int $id): ?Booking
    {
        $booking = Booking::findOrFail($id);
        $booking->update(['status' => 'cancelled']);
        return $booking->fresh();
    }

    /**
     * Completar una reserva.
     */
    public function complete(int $id): ?Booking
    {
        $booking = Booking::findOrFail($id);
        $booking->update(['status' => 'completed']);
        return $booking->fresh();
    }

    /**
     * Verificar disponibilidad de una cancha en un horario.
     */
    public function checkAvailability(int $courtId, string $date, string $startTime, string $endTime): bool
    {
        return !Booking::where('court_id', $courtId)
            ->where('booking_date', $date)
            ->whereIn('status', ['pending', 'confirmed'])
            ->where(function ($query) use ($startTime, $endTime) {
                $query->whereBetween('start_time', [$startTime, $endTime])
                    ->orWhereBetween('end_time', [$startTime, $endTime])
                    ->orWhere(function ($q) use ($startTime, $endTime) {
                        $q->where('start_time', '<=', $startTime)
                          ->where('end_time', '>=', $endTime);
                    });
            })
            ->exists();
    }

    /**
     * Obtener slots disponibles para una cancha en una fecha.
     */
    public function getAvailableSlots(int $courtId, string $date, int $durationMinutes = 60): array
    {
        $court = Court::findOrFail($courtId);
        $club = $court->club;

        $opening = Carbon::parse($club->opening_time);
        $closing = Carbon::parse($club->closing_time);

        $bookings = $this->courtBookings($courtId, $date);

        $slots = [];
        $current = clone $opening;

        while ($current->copy()->addMinutes($durationMinutes) <= $closing) {
            $slotEnd = $current->copy()->addMinutes($durationMinutes);
            $isAvailable = true;

            foreach ($bookings as $booking) {
                $bookingStart = Carbon::parse($booking->start_time);
                $bookingEnd = Carbon::parse($booking->end_time);

                if ($current->between($bookingStart, $bookingEnd->subMinute()) ||
                    $slotEnd->between($bookingStart->addMinute(), $bookingEnd) ||
                    ($current <= $bookingStart && $slotEnd >= $bookingEnd)) {
                    $isAvailable = false;
                    break;
                }
            }

            $slots[] = [
                'start_time' => $current->format('H:i'),
                'end_time' => $slotEnd->format('H:i'),
                'available' => $isAvailable,
                'price_per_hour' => $court->price_per_hour, // Añadimos el precio por hora de la cancha
            ];

            $current->addMinutes($durationMinutes);
        }

        return $slots;
    }

    /**
     * Invitar jugador a una reserva.
     */
    public function invitePlayer(int $bookingId, int $userId): BookingPlayer
    {
        return BookingPlayer::create([
            'booking_id' => $bookingId,
            'user_id' => $userId,
            'status' => 'pending',
        ]);
    }

    /**
     * Aceptar invitación a reserva.
     */
    public function acceptInvitation(int $bookingId, int $userId): void
    {
        BookingPlayer::where('booking_id', $bookingId)
            ->where('user_id', $userId)
            ->update(['status' => 'accepted']);
    }

    /**
     * Rechazar invitación a reserva.
     */
    public function declineInvitation(int $bookingId, int $userId): void
    {
        BookingPlayer::where('booking_id', $bookingId)
            ->where('user_id', $userId)
            ->update(['status' => 'declined']);
    }
}
