<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'court_id' => $this->court_id,
            'club_id' => $this->club_id,
            'booking_date' => $this->booking_date,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'duration_minutes' => $this->duration_minutes,
            'total_price' => (float) $this->total_price,
            'status' => $this->status,
            'notes' => $this->notes,
            'players_count' => $this->players_count,
            'user' => new UserResource($this->whenLoaded('user')),
            'court' => new CourtResource($this->whenLoaded('court')),
            'club' => new ClubResource($this->whenLoaded('club')),
            'players' => BookingPlayerResource::collection($this->whenLoaded('players')),
            'payment' => new PaymentResource($this->whenLoaded('payment')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
