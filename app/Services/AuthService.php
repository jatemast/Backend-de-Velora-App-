<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    /**
     * Registrar un nuevo usuario.
     */
    public function register(array $data): User
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        // Crear perfil por defecto
        $user->profile()->create([
            'skill_level' => 1,
        ]);

        return $user;
    }

    /**
     * Iniciar sesión y obtener token Sanctum.
     */
    public function login(array $credentials): ?array
    {
        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return null;
        }

        // Revocar tokens anteriores
        $user->tokens()->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'access_token' => $token,
            'token_type' => 'bearer',
            'user' => $user,
        ];
    }

    /**
     * Cerrar sesión (revocar token actual).
     */
    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }

    /**
     * Obtener el usuario autenticado.
     */
    public function userProfile(User $user): User
    {
        return $user;
    }
}
