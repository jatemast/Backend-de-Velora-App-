<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserProfileController extends Controller
{
    /**
     * Obtener perfil del usuario autenticado.
     */
    public function show(): JsonResponse
    {
        $user = User::with('profile')->findOrFail(auth()->id());

        return response()->json([
            'success' => true,
            'data' => new UserResource($user),
        ]);
    }

    /**
     * Actualizar perfil del usuario.
     */
    public function update(Request $request): JsonResponse
    {
        $user = User::findOrFail(auth()->id());

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'bio' => 'nullable|string|max:500',
            'gender' => 'nullable|in:male,female,other,prefer_not_to_say',
            'birth_date' => 'nullable|date',
            'skill_level' => 'nullable|integer|min:1|max:10',
            'preferred_hand' => 'nullable|in:left,right,ambidextrous',
            'preferences' => 'nullable|array',
            'availability' => 'nullable|array',
        ]);

        // Actualizar datos del usuario
        if (isset($validated['name'])) {
            $user->name = $validated['name'];
        }
        if (isset($validated['last_name'])) {
            $user->last_name = $validated['last_name'];
        }
        if (isset($validated['email'])) {
            $user->email = $validated['email'];
        }
        if (isset($validated['phone'])) {
            $user->phone = $validated['phone'];
        }
        $user->save();

        // Actualizar o crear perfil
        $profileData = array_filter($validated, function ($key) {
            return !in_array($key, ['name', 'last_name', 'email', 'phone']);
        }, ARRAY_FILTER_USE_KEY);

        if (!empty($profileData)) {
            $user->profile()->updateOrCreate(
                ['user_id' => $user->id],
                $profileData
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Perfil actualizado exitosamente.',
            'data' => new UserResource($user->fresh('profile')),
        ]);
    }
}
