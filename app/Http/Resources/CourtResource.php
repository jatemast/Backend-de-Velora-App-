<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CourtResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'club_id' => $this->club_id,
            'name' => $this->name,
            'description' => $this->description,
            'surface_type' => $this->surface_type,
            'court_type' => $this->court_type,
            'is_covered' => $this->is_covered,
            'has_lighting' => $this->has_lighting,
            'max_players' => $this->max_players,
            'price_per_hour' => (float) $this->price_per_hour,
            'price_per_session' => (float) $this->price_per_session,
            'photos' => $this->photos,
            'amenities' => $this->amenities,
            'is_active' => $this->is_active,
            'club' => new ClubResource($this->whenLoaded('club')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
