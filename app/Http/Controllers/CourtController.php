<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCourtRequest;
use App\Http\Resources\CourtResource;
use App\Models\Court;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class CourtController extends Controller
{
    public function __construct(
        protected BookingService $bookingService
    ) {}

    /**
     * Listar canchas de un club.
     */
    public function index(int $clubId): JsonResponse
    {
        $courts = Court::with('club')
            ->where('club_id', $clubId)
            ->where('is_active', true)
            ->get();

        return response()->json([
            'success' => true,
            'data' => CourtResource::collection($courts),
        ]);
    }

    /**
     * Crear una cancha.
     */
    public function store(StoreCourtRequest $request, int $clubId): JsonResponse
    {
        $data = $request->validated();
        $data['club_id'] = $clubId;

        $court = Court::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Cancha creada exitosamente.',
            'data' => new CourtResource($court),
        ], 201);
    }

    /**
     * Mostrar una cancha.
     */
    public function show(int $id): JsonResponse
    {
        $court = Court::with(['club', 'reviews'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new CourtResource($court),
        ]);
    }

    /**
     * Actualizar una cancha.
     */
    public function update(StoreCourtRequest $request, int $id): JsonResponse
    {
        $court = Court::findOrFail($id);
        $court->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Cancha actualizada exitosamente.',
            'data' => new CourtResource($court->fresh()),
        ]);
    }

    /**
     * Eliminar una cancha.
     */
    public function destroy(int $id): JsonResponse
    {
        $court = Court::findOrFail($id);
        $court->delete();

        return response()->json([
            'success' => true,
            'message' => 'Cancha eliminada exitosamente.',
        ]);
    }

    /**
     * Verificar disponibilidad de una cancha.
     */
    public function checkAvailability(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
        ]);

        $available = $this->bookingService->checkAvailability(
            $id,
            $request->date,
            $request->start_time,
            $request->end_time
        );

        return response()->json([
            'success' => true,
            'available' => $available,
        ]);
    }

    #[OA\Get(
        path: "/api/courts/{id}/available-slots",
        summary: "Obtener slots disponibles para una cancha",
        description: "Obtiene los horarios disponibles para reservar una cancha en una fecha específica.",
        operationId: "getAvailableCourtSlots",
        tags: ["Canchas"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "ID de la cancha",
                schema: new OA\Schema(type: "integer")
            ),
            new OA\Parameter(
                name: "date",
                in: "query",
                required: true,
                description: "Fecha para consultar disponibilidad (YYYY-MM-DD)",
                schema: new OA\Schema(type: "string", format: "date")
            ),
            new OA\Parameter(
                name: "duration",
                in: "query",
                required: false,
                description: "Duración de cada slot en minutos (ej. 60)",
                schema: new OA\Schema(type: "integer", default: 60)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Slots disponibles obtenidos exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(
                            property: "data",
                            type: "array",
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: "start_time", type: "string", format: "time", example: "10:00"),
                                    new OA\Property(property: "end_time", type: "string", format: "time", example: "11:00"),
                                    new OA\Property(property: "available", type: "boolean", example: true),
                                    new OA\Property(property: "price_per_hour", type: "number", format: "float", example: 80000.00)
                                ],
                                type: "object"
                            )
                        )
                    ]
                )
            ),
            new OA\Response(response: 422, description: "Error de validación"),
            new OA\Response(response: 404, description: "Cancha no encontrada")
        ]
    )]
    /**
     * Obtener slots disponibles.
     */
    public function availableSlots(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'date' => 'required|date',
            'duration' => 'nullable|integer|min:30|max:240',
        ]);

        $slots = $this->bookingService->getAvailableSlots(
            $id,
            $request->date,
            $request->duration ?? 60
        );

        return response()->json([
            'success' => true,
            'data' => $slots,
        ]);
    }
}
