<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentService
{
    /**
     * Procesar un pago para una reserva.
     */
    public function processPayment(int $bookingId, int $userId, array $paymentData): Payment
    {
        return DB::transaction(function () use ($bookingId, $userId, $paymentData) {
            $booking = Booking::findOrFail($bookingId);

            $payment = Payment::create([
                'user_id' => $userId,
                'booking_id' => $bookingId,
                'transaction_id' => 'TXN-' . strtoupper(Str::random(20)),
                'amount' => $booking->total_price,
                'currency' => $paymentData['currency'] ?? 'COP',
                'payment_method' => $paymentData['payment_method'],
                'status' => 'completed',
                'payment_details' => $paymentData['details'] ?? null,
                'paid_at' => now(),
            ]);

            // Actualizar estado de la reserva
            $booking->update(['status' => 'confirmed']);

            return $payment->load('booking');
        });
    }

    /**
     * Procesar un reembolso.
     */
    public function refund(int $paymentId): ?Payment
    {
        $payment = Payment::findOrFail($paymentId);

        return DB::transaction(function () use ($payment) {
            $payment->update([
                'status' => 'refunded',
            ]);

            $payment->booking->update(['status' => 'cancelled']);

            return $payment->fresh();
        });
    }

    /**
     * Obtener pagos de un usuario.
     */
    public function userPayments(int $userId, array $filters = [])
    {
        $query = Payment::with('booking.court.club')
            ->where('user_id', $userId);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['from_date'])) {
            $query->where('created_at', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->where('created_at', '<=', $filters['to_date']);
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Obtener un pago por ID.
     */
    public function findById(int $id): ?Payment
    {
        return Payment::with(['user', 'booking.court.club'])->find($id);
    }

    /**
     * Obtener pagos por reserva.
     */
    public function bookingPayments(int $bookingId)
    {
        return Payment::where('booking_id', $bookingId)->get();
    }
}
