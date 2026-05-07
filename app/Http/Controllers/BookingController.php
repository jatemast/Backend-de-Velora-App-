<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookingRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function __construct(
        protected BookingService $bookingService
    ) {}

    /**
     * Listar reservas del usuario autenticado.
     */
    public function index(Request $request): JsonResponse
    {
        $bookings = $this->bookingService->userBookings(
            auth()->id(),
            $request->all()
        );

        return response()->json([
            'success' => true,
            'data' => BookingResource::collection($bookings),
            'meta' => [
                'current_page' => $bookings->currentPage(),
                'last_page' => $bookings->lastPage(),
                'per_page' => $bookings->perPage(),
                'total' => $bookings->total(),
            ],
        ]);
    }

    /**
     * Crear una reserva.
     */
    public function store(StoreBookingRequest $request): JsonResponse
    {
        $booking = $this->bookingService->create(
            $request->validated(),
            auth()->id()
        );

        return response()->json([
            'success' => true,
            'message' => 'Reserva creada exitosamente.',
            'data' => new BookingResource($booking),
        ], 201);
    }

    /**
     * Mostrar una reserva.
     */
    public function show(int $id): JsonResponse
    {
        $booking = Booking::with([
            'user', 'court.club', 'players.user', 'payment'
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new BookingResource($booking),
        ]);
    }

    /**
     * Confirmar una reserva.
     */
    public function confirm(int $id): JsonResponse
    {
        $booking = $this->bookingService->confirm($id);

        return response()->json([
            'success' => true,
            'message' => 'Reserva confirmada.',
            'data' => new BookingResource($booking),
        ]);
    }

    /**
     * Cancelar una reserva.
     */
    public function cancel(int $id): JsonResponse
    {
        $booking = $this->bookingService->cancel($id);

        return response()->json([
            'success' => true,
            'message' => 'Reserva cancelada.',
            'data' => new BookingResource($booking),
        ]);
    }

    /**
     * Invitar jugador a una reserva.
     */
    public function invitePlayer(Request $request, int $bookingId): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $player = $this->bookingService->invitePlayer(
            $bookingId,
            $request->user_id
        );

        return response()->json([
            'success' => true,
            'message' => 'Jugador invitado exitosamente.',
            'data' => $player,
        ], 201);
    }

    /**
     * Aceptar invitación a reserva.
     */
    public function acceptInvitation(int $bookingId): JsonResponse
    {
        $this->bookingService->acceptInvitation(
            $bookingId,
            auth()->id()
        );

        return response()->json([
            'success' => true,
            'message' => 'Invitación aceptada.',
        ]);
    }

    /**
     * Rechazar invitación a reserva.
     */
    public function declineInvitation(int $bookingId): JsonResponse
    {
        $this->bookingService->declineInvitation(
            $bookingId,
            auth()->id()
        );

        return response()->json([
            'success' => true,
            'message' => 'Invitación rechazada.',
        ]);
    }
}
