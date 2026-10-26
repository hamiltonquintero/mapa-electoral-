<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class TerritorioFactory extends Factory
{
    public function definition(): array
    {
        $tipos = ['municipio', 'corregimiento', 'vereda', 'barrio'];

        return [
            'padre_id'        => null,
            'tipo'            => fake()->randomElement($tipos),
            'nombre'          => fake('es_CO')->city(),
            'codigo_divipola' => fake()->numerify('######'),
            'geom'            => null,
            'centroide_lat'   => null,
            'centroide_lng'   => null,
        ];
    }

    public function municipio(): static
    {
        return $this->state(fn(array $attributes) => [
            'tipo'   => 'municipio',
            'nombre' => fake('es_CO')->city(),
        ]);
    }

    public function departamento(): static
    {
        return $this->state(fn(array $attributes) => [
            'tipo'     => 'departamento',
            'nombre'   => 'Caldas',
            'padre_id' => null,
        ]);
    }
}
