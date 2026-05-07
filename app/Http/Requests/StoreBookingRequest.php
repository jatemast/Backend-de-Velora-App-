<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'court_id' => 'required|exists:courts,id',
            'booking_date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'notes' => 'nullable|string|max:500',
            'players_count' => 'required|integer|min:1|max:8',
            'players' => 'nullable|array',
            'players.*' => 'exists:users,id',
        ];
    }

    public function messages(): array
    {
        return [
            'booking_date.after_or_equal' => 'La fecha de reserva debe ser hoy o una fecha futura.',
            'end_time.after' => 'La hora de fin debe ser posterior a la hora de inicio.',
            'court_id.exists' => 'La cancha seleccionada no existe.',
            'players.*.exists' => 'Uno de los jugadores invitados no existe.',
        ];
    }
}
