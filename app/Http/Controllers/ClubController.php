<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClubRequest;
use App\Http\Resources\ClubResource;
use App\Services\ClubService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ClubController extends Controller
{
    public function __construct(
        protected ClubService $clubService
    ) {}

    #[OA\Get(
        path: "/api/clubs",
        summary: "Listar clubes",
        description: "Obtiene una lista paginada de clubes, con opciones de filtrado.",
        operationId: "listClubs",
        tags: ["Clubs"],
        parameters: [
            new OA\Parameter(
                name: "city",
                in: "query",
                required: false,
                description: "Filtrar por ciudad",
                schema: new OA\Schema(type: "string")
            ),
            new OA\Parameter(
                name: "country",
                in: "query",
                required: false,
                description: "Filtrar por país",
                schema: new OA\Schema(type: "string")
            ),
            new OA\Parameter(
                name: "search",
                in: "query",
                required: false,
                description: "Buscar por nombre o descripción del club",
                schema: new OA\Schema(type: "string")
            ),
            new OA\Parameter(
                name: "sport",
                in: "query",
                required: false,
                description: "Filtrar por tipo de deporte (ej. Padel, Fútbol, Tenis, Voley)",
                schema: new OA\Schema(type: "string")
            ),
            new OA\Parameter(
                name: "has_courts_for_sport",
                in: "query",
                required: false,
                description: "Filtrar clubes que tienen canchas para un deporte específico (requiere el parámetro 'sport')",
                schema: new OA\Schema(type: "boolean")
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
                description: "Lista de clubes obtenida exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "data", type: "array", items: new OA\Items(ref: "#/components/schemas/ClubResource")),
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
            )
        ]
    )]
    /**
     * Listar clubs.
     */
    public function index(Request $request): JsonResponse
    {
        $clubs = $this->clubService->list($request->all());

        return response()->json([
            'success' => true,
            'data' => ClubResource::collection($clubs),
            'meta' => [
                'current_page' => $clubs->currentPage(),
                'last_page' => $clubs->lastPage(),
                'per_page' => $clubs->perPage(),
                'total' => $clubs->total(),
            ],
        ]);
    }

    /**
     * Crear un club.
     */
    public function store(StoreClubRequest $request): JsonResponse
    {
        $club = $this->clubService->create(
            $request->validated(),
            auth()->id()
        );

        return response()->json([
            'success' => true,
            'message' => 'Club creado exitosamente.',
            'data' => new ClubResource($club),
        ], 201);
    }

    /**
     * Mostrar un club.
     */
    public function show(int $id): JsonResponse
    {
        $club = $this->clubService->findById($id);

        if (!$club) {
            return response()->json([
                'success' => false,
                'message' => 'Club no encontrado.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new ClubResource($club),
        ]);
    }

    /**
     * Mostrar un club por slug.
     */
    public function showBySlug(string $slug): JsonResponse
    {
        $club = $this->clubService->findBySlug($slug);

        if (!$club) {
            return response()->json([
                'success' => false,
                'message' => 'Club no encontrado.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new ClubResource($club),
        ]);
    }

    /**
     * Actualizar un club.
     */
    public function update(StoreClubRequest $request, int $id): JsonResponse
    {
        $club = $this->clubService->update($id, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Club actualizado exitosamente.',
            'data' => new ClubResource($club),
        ]);
    }

    /**
     * Eliminar un club.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->clubService->delete($id);

        return response()->json([
            'success' => true,
            'message' => 'Club eliminado exitosamente.',
        ]);
    }

    /**
     * Listar miembros de un club.
     */
    public function members(int $clubId): JsonResponse
    {
        $members = $this->clubService->getMembers($clubId);

        return response()->json([
            'success' => true,
            'data' => $members,
        ]);
    }

    /**
     * Agregar miembro a un club.
     */
    public function addMember(Request $request, int $clubId): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'nullable|in:admin,member',
        ]);

        $member = $this->clubService->addMember(
            $clubId,
            $request->user_id,
            $request->role ?? 'member'
        );

        return response()->json([
            'success' => true,
            'message' => 'Miembro agregado exitosamente.',
            'data' => $member,
        ], 201);
    }
}
