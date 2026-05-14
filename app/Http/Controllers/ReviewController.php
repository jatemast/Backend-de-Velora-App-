<?php

namespace App\Http\Controllers;

use App\Http\Resources\ReviewResource;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ReviewController extends Controller
{
    #[OA\Get(
        path: "/api/reviews",
        summary: "Listar reseñas de un club o cancha",
        description: "Obtiene una lista paginada de reseñas, filtradas por tipo y ID de entidad (club o cancha).",
        operationId: "listReviews",
        tags: ["Reseñas"],
        parameters: [
            new OA\Parameter(
                name: "reviewable_type",
                in: "query",
                required: true,
                description: "Tipo de entidad a reseñar (club o court)",
                schema: new OA\Schema(type: "string", enum: ["club", "court"])
            ),
            new OA\Parameter(
                name: "reviewable_id",
                in: "query",
                required: true,
                description: "ID de la entidad (club o cancha)",
                schema: new OA\Schema(type: "integer")
            ),
            new OA\Parameter(
                name: "per_page",
                in: "query",
                required: false,
                description: "Número de resultados por página",
                schema: new OA\Schema(type: "integer", default: 15)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Lista de reseñas obtenida exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "data", type: "array", items: new OA\Items(ref: "#/components/schemas/ReviewResource")),
                        new OA\Property(
                            property: "meta",
                            type: "object",
                            properties: [
                                new OA\Property(property: "current_page", type: "integer"),
                                new OA\Property(property: "last_page", type: "integer"),
                                new OA\Property(property: "per_page", type: "integer"),
                                new OA\Property(property: "total", type: "integer"),
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(response: 422, description: "Error de validación")
        ]
    )]
    /**
     * Listar reseñas de un club o cancha.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'reviewable_type' => 'required|in:club,court',
            'reviewable_id' => 'required|integer',
        ]);

        $type = $request->reviewable_type === 'club'
            ? 'App\Models\Club'
            : 'App\Models\Court';

        $reviews = Review::with('user')
            ->where('reviewable_type', $type)
            ->where('reviewable_id', $request->reviewable_id)
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => ReviewResource::collection($reviews),
            'meta' => [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'per_page' => $reviews->perPage(),
                'total' => $reviews->total(),
            ],
        ]);
    }

    #[OA\Post(
        path: "/api/reviews",
        summary: "Crear una nueva reseña",
        description: "Permite a un usuario autenticado crear una reseña para un club o una cancha.",
        operationId: "createReview",
        tags: ["Reseñas"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["reviewable_type", "reviewable_id", "rating"],
                properties: [
                    new OA\Property(property: "reviewable_type", type: "string", enum: ["club", "court"], example: "club", description: "Tipo de entidad a reseñar"),
                    new OA\Property(property: "reviewable_id", type: "integer", example: 1, description: "ID de la entidad"),
                    new OA\Property(property: "booking_id", type: "integer", nullable: true, example: null, description: "ID de la reserva asociada (opcional)"),
                    new OA\Property(property: "rating", type: "integer", minimum: 1, maximum: 5, example: 5, description: "Calificación de 1 a 5 estrellas"),
                    new OA\Property(property: "comment", type: "string", nullable: true, maxLength: 1000, example: "Excelente lugar y servicio.", description: "Comentario de la reseña"),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Reseña creada exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Reseña creada exitosamente."),
                        new OA\Property(property: "data", ref: "#/components/schemas/ReviewResource"),
                    ]
                )
            ),
            new OA\Response(response: 401, description: "No autenticado"),
            new OA\Response(response: 422, description: "Error de validación")
        ]
    )]
    /**
     * Crear una reseña.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reviewable_type' => 'required|in:club,court',
            'reviewable_id' => 'required|integer',
            'booking_id' => 'nullable|exists:bookings,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $validated['user_id'] = auth()->id();
        $validated['reviewable_type'] = $validated['reviewable_type'] === 'club'
            ? 'App\Models\Club'
            : 'App\Models\Court';

        $review = Review::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Reseña creada exitosamente.',
            'data' => new ReviewResource($review),
        ], 201);
    }

    #[OA\Put(
        path: "/api/reviews/{id}",
        summary: "Actualizar una reseña",
        description: "Permite a un usuario autenticado actualizar su propia reseña.",
        operationId: "updateReview",
        tags: ["Reseñas"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "ID de la reseña",
                schema: new OA\Schema(type: "integer")
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "rating", type: "integer", minimum: 1, maximum: 5, example: 4, description: "Nueva calificación de 1 a 5 estrellas"),
                    new OA\Property(property: "comment", type: "string", nullable: true, maxLength: 1000, example: "Muy buena, aunque el servicio podría mejorar.", description: "Nuevo comentario de la reseña"),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Reseña actualizada exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Reseña actualizada."),
                        new OA\Property(property: "data", ref: "#/components/schemas/ReviewResource"),
                    ]
                )
            ),
            new OA\Response(response: 401, description: "No autenticado"),
            new OA\Response(response: 403, description: "No autorizado para actualizar esta reseña"),
            new OA\Response(response: 404, description: "Reseña no encontrada"),
            new OA\Response(response: 422, description: "Error de validación")
        ]
    )]
    /**
     * Actualizar una reseña.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $review = Review::where('user_id', auth()->id())
            ->findOrFail($id);

        $validated = $request->validate([
            'rating' => 'sometimes|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $review->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Reseña actualizada.',
            'data' => new ReviewResource($review->fresh()),
        ]);
    }

    #[OA\Delete(
        path: "/api/reviews/{id}",
        summary: "Eliminar una reseña",
        description: "Permite a un usuario autenticado eliminar su propia reseña.",
        operationId: "deleteReview",
        tags: ["Reseñas"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "ID de la reseña",
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Reseña eliminada exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Reseña eliminada."),
                    ]
                )
            ),
            new OA\Response(response: 401, description: "No autenticado"),
            new OA\Response(response: 403, description: "No autorizado para eliminar esta reseña"),
            new OA\Response(response: 404, description: "Reseña no encontrada")
        ]
    )]
    /**
     * Eliminar una reseña.
     */
    public function destroy(int $id): JsonResponse
    {
        $review = Review::where('user_id', auth()->id())
            ->findOrFail($id);

        $review->delete();

        return response()->json([
            'success' => true,
            'message' => 'Reseña eliminada.',
        ]);
    }
}
