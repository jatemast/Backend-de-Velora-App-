<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClubRequest;
use App\Http\Resources\ClubResource;
use App\Services\ClubService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClubController extends Controller
{
    public function __construct(
        protected ClubService $clubService
    ) {}

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
