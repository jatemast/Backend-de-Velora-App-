<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear usuario admin por defecto
        $admin = User::create([
            'name' => 'Admin',
            'last_name' => 'Velora',
            'email' => 'admin@velora.com',
            'phone' => '3000000000',
            'password' => Hash::make('password'),
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // Crear perfil del admin
        UserProfile::create([
            'user_id' => $admin->id,
            'bio' => 'Administrador del sistema Velora Padel',
            'avatar_url' => null,
            'birth_date' => '1990-01-01',
            'gender' => 'other',
            'phone_alternative' => null,
            'preferences' => json_encode(['language' => 'es', 'notifications' => true]),
        ]);

        // Crear usuario de prueba
        $user = User::create([
            'name' => 'Usuario',
            'last_name' => 'Prueba',
            'email' => 'user@velora.com',
            'phone' => '3001111111',
            'password' => Hash::make('password'),
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // Crear perfil del usuario de prueba
        UserProfile::create([
            'user_id' => $user->id,
            'bio' => 'Usuario de prueba',
            'avatar_url' => null,
            'birth_date' => '1995-05-15',
            'gender' => 'other',
            'phone_alternative' => null,
            'preferences' => json_encode(['language' => 'es', 'notifications' => true]),
        ]);
    }
}
