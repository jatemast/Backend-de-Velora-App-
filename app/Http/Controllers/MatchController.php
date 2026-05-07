<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMatchRequest;
use App\Http\Requests\UpdateScoreRequest;
use App\Http\Resources\GameMatchResource;
use App\Models\GameMatch;
use App\Services\MatchmakingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MatchController extends Controller
{
    public function __construct(
        protected MatchmakingService $matchmakingService
    ) {}

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
