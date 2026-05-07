<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'booking_id' => 'required|exists:bookings,id',
            'match_type' => 'required|in:singles,doubles,mixed_doubles',
            'is_competitive' => 'boolean',
            'skill_level_min' => 'nullable|integer|min:1|max:10',
            'skill_level_max' => 'nullable|integer|min:1|max:10|gte:skill_level_min',
            'players' => 'nullable|array',
            'players.*.user_id' => 'required|exists:users,id',
            'players.*.team' => 'nullable|in:team1,team2',
        ];
    }
}
