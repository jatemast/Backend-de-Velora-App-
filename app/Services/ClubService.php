<?php

namespace App\Services;

use App\Models\Club;
use App\Models\ClubMember;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ClubService
{
    /**
     * Listar clubs con filtros.
     */
    public function list(array $filters = [])
    {
        $query = Club::with(['owner', 'courts'])
            ->where('is_active', true);

        if (!empty($filters['city'])) {
            $query->where('city', 'like', "%{$filters['city']}%");
        }

        if (!empty($filters['country'])) {
            $query->where('country', $filters['country']);
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                  ->orWhere('description', 'like', "%{$filters['search']}%");
            });
        }

        if (!empty($filters['sport'])) {
            $query->whereHas('courts', function ($q) use ($filters) {
                $q->where('sport_type', $filters['sport']);
            });
        }

        if (isset($filters['has_courts_for_sport']) && $filters['has_courts_for_sport'] === true && !empty($filters['sport'])) {
            $query->whereHas('courts', function ($q) use ($filters) {
                $q->where('sport_type', $filters['sport']);
            });
        }

        return $query->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Crear un nuevo club.
     */
    public function create(array $data, int $ownerId): Club
    {
        return DB::transaction(function () use ($data, $ownerId) {
            $data['owner_id'] = $ownerId;
            $data['slug'] = Str::slug($data['name']) . '-' . Str::random(6);

            $club = Club::create($data);

            // Agregar al dueño como miembro con rol owner
            $club->members()->create([
                'user_id' => $ownerId,
                'role' => 'owner',
                'status' => 'active',
            ]);

            return $club;
        });
    }

    /**
     * Obtener un club por ID.
     */
    public function findById(int $id): ?Club
    {
        return Club::with(['owner', 'courts', 'members.user'])->find($id);
    }

    /**
     * Obtener un club por slug.
     */
    public function findBySlug(string $slug): ?Club
    {
        return Club::with(['owner', 'courts', 'members.user'])
            ->where('slug', $slug)
            ->first();
    }

    /**
     * Actualizar un club.
     */
    public function update(int $id, array $data): ?Club
    {
        $club = Club::findOrFail($id);
        $club->update($data);
        return $club->fresh();
    }

    /**
     * Eliminar un club (soft delete).
     */
    public function delete(int $id): bool
    {
        $club = Club::findOrFail($id);
        return $club->delete();
    }

    /**
     * Agregar miembro a un club.
     */
    public function addMember(int $clubId, int $userId, string $role = 'member'): ClubMember
    {
        return ClubMember::create([
            'club_id' => $clubId,
            'user_id' => $userId,
            'role' => $role,
            'status' => 'active',
        ]);
    }

    /**
     * Obtener miembros de un club.
     */
    public function getMembers(int $clubId)
    {
        return ClubMember::with('user')
            ->where('club_id', $clubId)
            ->where('status', 'active')
            ->get();
    }

    /**
     * Verificar si un usuario es miembro de un club.
     */
    public function isMember(int $clubId, int $userId): bool
    {
        return ClubMember::where('club_id', $clubId)
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->exists();
    }
}
