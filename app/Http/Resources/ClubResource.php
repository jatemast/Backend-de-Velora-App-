<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ClubResource extends JsonResource
{
    public function toArray($request): array
    {
        // Obtener sport_type desde la primera cancha (si hay)
        $firstCourt = $this->relationLoaded('courts') && $this->courts->isNotEmpty()
            ? $this->courts->first()
            : null;

        return [
            'id' => $this->id,
            'owner_id' => $this->owner_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'phone' => $this->phone,
            'email' => $this->email,
            'website' => $this->website,
            'logo' => $this->logo,
            'photos' => $this->photos,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'opening_time' => $this->opening_time,
            'closing_time' => $this->closing_time,
            'is_active' => $this->is_active,
            'sport_type' => $firstCourt?->sport_type,
            'owner' => new UserResource($this->whenLoaded('owner')),
            'courts' => CourtResource::collection($this->whenLoaded('courts')),
            'members_count' => $this->when($this->members_count, $this->members_count),
            'courts_count' => $this->when($this->courts_count, $this->courts_count),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
