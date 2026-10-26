<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CampanaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nombre'      => fake('es_CO')->company() . ' - Campaña ' . fake()->year(),
            'descripcion' => fake('es_CO')->sentence(),
            'activa'      => true,
        ];
    }

    public function inactiva(): static
    {
        return $this->state(fn(array $attributes) => ['activa' => false]);
    }
}
