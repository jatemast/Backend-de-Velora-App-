<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCourtRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'surface_type' => 'required|in:cement,clay,grass,carpet,acrylic,other',
            'court_type' => 'required|in:indoor,outdoor',
            'is_covered' => 'boolean',
            'has_lighting' => 'boolean',
            'max_players' => 'required|integer|min:2|max:8',
            'price_per_hour' => 'required|numeric|min:0',
            'price_per_session' => 'nullable|numeric|min:0',
            'photos' => 'nullable|array',
            'photos.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'amenities' => 'nullable|array',
            'amenities.*' => 'string',
            'is_active' => 'boolean',
        ];
    }
}
