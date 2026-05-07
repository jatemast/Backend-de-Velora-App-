<?php

namespace Database\Factories;

use App\Models\Club;
use App\Models\Court;
use Illuminate\Database\Eloquent\Factories\Factory;

class CourtFactory extends Factory
{
    protected $model = Court::class;

    public function definition(): array
    {
        return [
            'club_id' => Club::factory(),
            'name' => 'Cancha ' . $this->faker->numberBetween(1, 10),
            'description' => $this->faker->sentence(),
            'surface_type' => $this->faker->randomElement(['cement', 'clay', 'carpet', 'acrylic']),
            'court_type' => $this->faker->randomElement(['indoor', 'outdoor']),
            'is_covered' => $this->faker->boolean(),
            'has_lighting' => true,
            'max_players' => 4,
            'price_per_hour' => $this->faker->randomFloat(2, 20, 80),
            'price_per_session' => $this->faker->randomFloat(2, 60, 200),
            'amenities' => $this->faker->randomElements(['vestuarios', 'duchas', 'parking', 'wifi', 'cafetería', 'alquiler_palas'], 3),
            'is_active' => true,
        ];
    }
}
