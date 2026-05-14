<?php

namespace App\Services;

use App\Models\GameMatch;
use App\Models\MatchPlayer;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class MatchmakingService
{
    /**
     * Crear un partido a partir de una reserva.
     */
    public function createFromBooking(int $bookingId, array $data): GameMatch
    {
        return DB::transaction(function () use ($bookingId, $data) {
            $gameMatch = GameMatch::create([
                'booking_id' => $bookingId,
                'club_id' => $data['club_id'],
                'court_id' => $data['court_id'],
                'match_type' => $data['match_type'] ?? 'doubles',
                'status' => 'scheduled',
                'is_competitive' => $data['is_competitive'] ?? false,
                'skill_level_min' => $data['skill_level_min'] ?? null,
                'skill_level_max' => $data['skill_level_max'] ?? null,
            ]);

            // Agregar jugadores si se proporcionan
            if (!empty($data['players'])) {
                foreach ($data['players'] as $player) {
                    $gameMatch->players()->create([
                        'user_id' => $player['user_id'],
                        'team' => $player['team'] ?? null,
                        'status' => 'confirmed',
                    ]);
                }
            }

            return $gameMatch->load(['players.user', 'court', 'club']);
        });
    }

    /**
     * Buscar oponentes para un jugador según su nivel.
     */
    public function findOpponents(int $userId, array $filters = [])
    {
        $user = User::with('profile')->findOrFail($userId);
        $skillLevel = $user->profile->skill_level ?? 1;

        $query = User::with('profile')
            ->where('id', '!=', $userId)
            ->whereHas('profile');

        // Filtrar por nivel de habilidad
        $range = $filters['skill_range'] ?? 2;
        $query->whereHas('profile', function ($q) use ($skillLevel, $range) {
            $q->whereBetween('skill_level', [
                max(1, $skillLevel - $range),
                min(10, $skillLevel + $range),
            ]);
        });

        // Filtrar por tipo de deporte (sport_type)
        if (!empty($filters['sport_type'])) {
            $query->whereHas('profile', function ($q) use ($filters) {
                $q->where('preferred_sport', $filters['sport_type'])
                  ->orWhere('preferences', 'like', "%{$filters['sport_type']}%");
            });
        }

        // Filtrar por fecha (disponibilidad)
        if (!empty($filters['date'])) {
            $query->whereHas('profile', function ($q) use ($filters) {
                $q->where('availability', 'like', "%{$filters['date']}%");
            });
        }

        return $query->paginate($filters['per_page'] ?? 10);
    }

    /**
     * Actualizar resultado de un partido.
     */
    public function updateScore(int $matchId, array $scoreData): ?GameMatch
    {
        $gameMatch = GameMatch::findOrFail($matchId);

        $gameMatch->update([
            'team1_score' => $scoreData['team1_score'],
            'team2_score' => $scoreData['team2_score'],
            'score_details' => $scoreData['score_details'] ?? null,
            'winner_team' => $scoreData['winner_team'] ?? null,
            'status' => 'completed',
        ]);

        return $gameMatch->fresh();
    }

    /**
     * Obtener partidos disponibles para unirse.
     */
    public function getAvailableMatches(array $filters = [])
    {
        $query = GameMatch::with(['club', 'court', 'players.user'])
            ->where('status', 'scheduled')
            ->where('is_competitive', true);

        if (!empty($filters['club_id'])) {
            $query->where('club_id', $filters['club_id']);
        }

        if (!empty($filters['match_type'])) {
            $query->where('match_type', $filters['match_type']);
        }

        if (!empty($filters['skill_level'])) {
            $query->where('skill_level_min', '<=', $filters['skill_level'])
                  ->where('skill_level_max', '>=', $filters['skill_level']);
        }

        if (!empty($filters['sport_type'])) {
            $query->whereHas('court', function ($q) use ($filters) {
                $q->where('sport_type', $filters['sport_type']);
            });
        }

        if (!empty($filters['date'])) {
            $query->whereHas('booking', function ($q) use ($filters) {
                $q->where('booking_date', $filters['date']);
            });
        }

        return $query->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Unirse a un partido.
     */
    public function joinMatch(int $matchId, int $userId, ?string $team = null): MatchPlayer
    {
        return MatchPlayer::create([
            'match_id' => $matchId,
            'user_id' => $userId,
            'team' => $team,
            'status' => 'confirmed',
        ]);
    }

    /**
     * Abandonar un partido.
     */
    public function leaveMatch(int $matchId, int $userId): void
    {
        MatchPlayer::where('match_id', $matchId)
            ->where('user_id', $userId)
            ->delete();
    }
}
