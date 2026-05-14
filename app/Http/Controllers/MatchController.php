<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMatchRequest;
use App\Http\Requests\UpdateScoreRequest;
use App\Http\Resources\GameMatchResource;
use App\Models\GameMatch;
use App\Services\MatchmakingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class MatchController extends Controller
{
    public function __construct(
        protected MatchmakingService $matchmakingService
    ) {}

    #[OA\Get(
        path: "/api/matches",
        summary: "Listar partidos disponibles",
        description: "Obtiene una lista paginada de partidos disponibles para unirse, con opciones de filtrado.",
        operationId: "listAvailableMatches",
        tags: ["Partidos"],
        parameters: [
            new OA\Parameter(
                name: "club_id",
                in: "query",
                required: false,
                description: "Filtrar por ID de club",
                schema: new OA\Schema(type: "integer")
            ),
            new OA\Parameter(
                name: "match_type",
                in: "query",
                required: false,
                description: "Filtrar por tipo de partido (ej. singles, doubles)",
                schema: new OA\Schema(type: "string")
            ),
            new OA\Parameter(
                name: "skill_level",
                in: "query",
                required: false,
                description: "Filtrar por nivel de habilidad mínimo requerido para el partido",
                schema: new OA\Schema(type: "integer", minimum: 1, maximum: 10)
            ),
            new OA\Parameter(
                name: "sport_type",
                in: "query",
                required: false,
                description: "Filtrar por tipo de deporte del partido (ej. Padel, Fútbol)",
                schema: new OA\Schema(type: "string")
            ),
            new OA\Parameter(
                name: "date",
                in: "query",
                required: false,
                description: "Filtrar partidos para una fecha específica (YYYY-MM-DD)",
                schema: new OA\Schema(type: "string", format: "date")
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
                description: "Lista de partidos obtenida exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "data", type: "array", items: new OA\Items(ref: "#/components/schemas/GameMatchResource")),
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
     * Listar partidos disponibles para unirse.
     */
    public function index(Request $request): JsonResponse
    {
        $matches = $this->matchmakingService->getAvailableMatches($request->all());

        return response()->json([
            'success' => true,
            'data' => GameMatchResource::collection($matches),
            'meta' => [
                'current_page' => $matches->currentPage(),
                'last_page' => $matches->lastPage(),
                'per_page' => $matches->perPage(),
                'total' => $matches->total(),
            ],
        ]);
    }

    /**
     * Crear un partido desde una reserva.
     */
    public function store(StoreMatchRequest $request): JsonResponse
    {
        $gameMatch = $this->matchmakingService->createFromBooking(
            $request->booking_id,
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Partido creado exitosamente.',
            'data' => new GameMatchResource($gameMatch),
        ], 201);
    }

    /**
     * Mostrar un partido.
     */
    public function show(int $id): JsonResponse
    {
        $gameMatch = GameMatch::with([
            'club', 'court', 'players.user', 'booking'
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new GameMatchResource($gameMatch),
        ]);
    }

    /**
     * Actualizar resultado de un partido.
     */
    public function updateScore(UpdateScoreRequest $request, int $id): JsonResponse
    {
        $gameMatch = $this->matchmakingService->updateScore(
            $id,
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Resultado actualizado.',
            'data' => new GameMatchResource($gameMatch),
        ]);
    }

    /**
     * Unirse a un partido.
     */
    public function join(Request $request, int $matchId): JsonResponse
    {
        $request->validate([
            'team' => 'nullable|in:team1,team2',
        ]);

        $player = $this->matchmakingService->joinMatch(
            $matchId,
            auth()->id(),
            $request->team
        );

        return response()->json([
            'success' => true,
            'message' => 'Te has unido al partido.',
            'data' => $player,
        ]);
    }

    /**
     * Abandonar un partido.
     */
    public function leave(int $matchId): JsonResponse
    {
        $this->matchmakingService->leaveMatch(
            $matchId,
            auth()->id()
        );

        return response()->json([
            'success' => true,
            'message' => 'Has abandonado el partido.',
        ]);
    }

    #[OA\Get(
        path: "/api/matchmaking/opponents",
        summary: "Buscar oponentes por nivel de habilidad",
        description: "Busca usuarios que puedan ser oponentes basándose en el nivel de habilidad.",
        operationId: "findOpponents",
        tags: ["Matchmaking"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "skill_range",
                in: "query",
                required: false,
                description: "Rango de nivel de habilidad para buscar (ej. 2 para +/- 2 niveles del usuario)",
                schema: new OA\Schema(type: "integer", default: 2)
            ),
            new OA\Parameter(
                name: "per_page",
                in: "query",
                required: false,
                description: "Número de resultados por página",
                schema: new OA\Schema(type: "integer", default: 10)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Oponentes encontrados exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "data", type: "array", items: new OA\Items(ref: "#/components/schemas/UserResource")),
                    ]
                )
            ),
            new OA\Response(response: 401, description: "No autenticado")
        ]
    )]
    /**
     * Buscar oponentes.
     */
    public function findOpponents(Request $request): JsonResponse
    {
        $opponents = $this->matchmakingService->findOpponents(
            auth()->id(),
            $request->all()
        );

        return response()->json([
            'success' => true,
            'data' => $opponents,
        ]);
    }
}
