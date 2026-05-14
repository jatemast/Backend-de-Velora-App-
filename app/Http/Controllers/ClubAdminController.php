<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ClubAdminService;
use OpenApi\Attributes as OA;

class ClubAdminController extends Controller
{
    protected $clubAdminService;

    public function __construct(ClubAdminService $clubAdminService)
    {
        $this->clubAdminService = $clubAdminService;
    }

    #[OA\Get(
        path: "/admin/clubs/{clubId}/summary",
        summary: "Obtener resumen del dashboard para un club",
        security: [["bearerAuth" => []]],
        tags: ["Administración de Clubes"],
        parameters: [
            new OA\Parameter(
                name: "clubId",
                in: "path",
                required: true,
                description: "ID del club",
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Resumen del dashboard del club",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "reservas_hoy", type: "integer", example: 12),
                        new OA\Property(property: "ingresos_hoy", type: "number", format: "float", example: 960000.00),
                        new OA\Property(property: "ocupacion", type: "string", example: "78%"),
                        new OA\Property(property: "calificacion", type: "number", format: "float", example: 4.7),
                    ],
                    type: "object"
                )
            ),
            new OA\Response(response: 401, description: "No autenticado"),
            new OA\Response(response: 404, description: "Club no encontrado")
        ]
    )]
    /**
     * Obtiene el resumen del dashboard para un club específico.
     *
     * @param  int  $clubId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDashboardSummary($clubId)
    {
        $summary = $this->clubAdminService->getDashboardSummary($clubId);
        return response()->json($summary);
    }

    #[OA\Get(
        path: "/admin/clubs/{clubId}/upcoming-bookings",
        summary: "Obtener próximas reservas para un club",
        security: [["bearerAuth" => []]],
        tags: ["Administración de Clubes"],
        parameters: [
            new OA\Parameter(
                name: "clubId",
                in: "path",
                required: true,
                description: "ID del club",
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Listado de próximas reservas",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: "id", type: "integer", example: 1),
                            new OA\Property(property: "cancha", type: "string", example: "Cancha 1"),
                            new OA\Property(property: "fecha", type: "string", example: "14 May 2026"),
                            new OA\Property(property: "hora", type: "string", example: "20:00 - 21:30"),
                            new OA\Property(property: "usuario", type: "string", example: "Juan Pérez"),
                            new OA\Property(property: "estado", type: "string", example: "confirmed"),
                        ],
                        type: "object"
                    )
                )
            ),
            new OA\Response(response: 401, description: "No autenticado"),
            new OA\Response(response: 404, description: "Club no encontrado")
        ]
    )]
    /**
     * Obtiene las próximas reservas para un club específico.
     *
     * @param  int  $clubId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUpcomingBookings($clubId)
    {
        $bookings = $this->clubAdminService->getUpcomingBookings($clubId);
        return response()->json($bookings);
    }
}
