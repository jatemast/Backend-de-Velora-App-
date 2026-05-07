<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class GameMatchResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'booking_id' => $this->booking_id,
            'club_id' => $this->club_id,
            'court_id' => $this->court_id,
            'match_type' => $this->match_type,
            'status' => $this->status,
            'team1_players' => $this->team1_players,
            'team2_players' => $this->team2_players,
            'team1_score' => $this->team1_score,
            'team2_score' => $this->team2_score,
            'score_details' => $this->score_details,
            'winner_team' => $this->winner_team,
            'is_competitive' => $this->is_competitive,
            'skill_level_min' => $this->skill_level_min,
            'skill_level_max' => $this->skill_level_max,
            'club' => new ClubResource($this->whenLoaded('club')),
            'court' => new CourtResource($this->whenLoaded('court')),
            'players' => MatchPlayerResource::collection($this->whenLoaded('players')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
