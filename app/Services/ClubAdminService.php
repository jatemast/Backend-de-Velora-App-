<?php

namespace App\Services;

use App\Models\Club;
use App\Models\Booking;
use Carbon\Carbon;

class ClubAdminService
{
    public function getDashboardSummary($clubId)
    {
        $club = Club::findOrFail($clubId);
        $today = Carbon::today();

        $bookingsToday = $club->bookings()->whereDate('booking_date', $today)->count();
        $incomeToday = $club->bookings()
                            ->whereDate('booking_date', $today)
                            ->where('status', 'confirmed') // Asumimos que solo las reservas confirmadas generan ingresos
                            ->sum('total_price');

        // Ocupación: Esto requeriría una lógica más compleja para calcular con precisión
        // Por ahora, un placeholder simple.
        $totalCourts = $club->courts()->count();
        // Si tuviéramos un sistema de 'slots' de tiempo, podríamos calcular la ocupación con más precisión.
        // Por simplicidad, asumimos una métrica básica o se puede expandir.
        $occupancyRate = 0; // Placeholder
        if ($totalCourts > 0) {
            // Esto es solo un ejemplo, la lógica real de ocupación es más compleja
            $occupancyRate = ($bookingsToday / ($totalCourts * 10)) * 100; // Asumiendo 10 slots por cancha al día
            $occupancyRate = min($occupancyRate, 100); // No exceder el 100%
        }

        $averageRating = $club->reviews()->avg('rating');

        return [
            'reservas_hoy' => $bookingsToday,
            'ingresos_hoy' => $incomeToday,
            'ocupacion' => round($occupancyRate, 2) . '%',
            'calificacion' => round($averageRating, 1) ?? 0,
        ];
    }

    public function getUpcomingBookings($clubId)
    {
        $club = Club::findOrFail($clubId);
        $now = Carbon::now();

        $upcomingBookings = $club->bookings()
                                ->with(['court', 'user'])
                                ->where('booking_date', '>=', $now->toDateString())
                                ->where(function ($query) use ($now) {
                                    $query->where('booking_date', '>', $now->toDateString())
                                          ->orWhere(function ($query) use ($now) {
                                              $query->where('booking_date', $now->toDateString())
                                                    ->where('start_time', '>', $now->toTimeString());
                                          });
                                })
                                ->orderBy('booking_date')
                                ->orderBy('start_time')
                                ->get();

        return $upcomingBookings->map(function ($booking) {
            return [
                'id' => $booking->id,
                'cancha' => $booking->court->name,
                'fecha' => Carbon::parse($booking->booking_date)->format('d M Y'),
                'hora' => Carbon::parse($booking->start_time)->format('H:i') . ' - ' . Carbon::parse($booking->end_time)->format('H:i'),
                'usuario' => $booking->user->name,
                'estado' => $booking->status,
            ];
        });
    }
}
