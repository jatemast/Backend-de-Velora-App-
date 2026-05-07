<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProcessPaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService
    ) {}

    /**
     * Listar pagos del usuario autenticado.
     */
    public function index(Request $request): JsonResponse
    {
        $payments = $this->paymentService->userPayments(
            auth()->id(),
            $request->all()
        );

        return response()->json([
            'success' => true,
            'data' => PaymentResource::collection($payments),
            'meta' => [
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
                'per_page' => $payments->perPage(),
                'total' => $payments->total(),
            ],
        ]);
    }

    /**
     * Procesar un pago.
     */
    public function process(ProcessPaymentRequest $request, int $bookingId): JsonResponse
    {
        $payment = $this->paymentService->processPayment(
            $bookingId,
            auth()->id(),
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Pago procesado exitosamente.',
            'data' => new PaymentResource($payment),
        ], 201);
    }

    /**
     * Mostrar un pago.
     */
    public function show(int $id): JsonResponse
    {
        $payment = $this->paymentService->findById($id);

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Pago no encontrado.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new PaymentResource($payment),
        ]);
    }

    /**
     * Reembolsar un pago.
     */
    public function refund(int $id): JsonResponse
    {
        $payment = $this->paymentService->refund($id);

        return response()->json([
            'success' => true,
            'message' => 'Reembolso procesado exitosamente.',
            'data' => new PaymentResource($payment),
        ]);
    }
}
