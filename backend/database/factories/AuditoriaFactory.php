<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AuditoriaFactory extends Factory
{
    public function definition(): array
    {
        $acciones = [
            'login_exitoso', 'logout', 'simpatizante_creado',
            'simpatizante_actualizado', 'simpatizante_consultado',
            'exportacion_creada', 'rol_asignado',
        ];

        return [
            'user_id'    => User::factory(),
            'rol'        => fake()->randomElement(['Digitador', 'Coordinador Municipal', 'Auditor']),
            'accion'     => fake()->randomElement($acciones),
            'entidad'    => fake()->randomElement(['simpatizantes', 'users', 'territorios']),
            'entidad_id' => (string) fake()->numberBetween(1, 999),
            'ip'         => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'detalle'    => ['origen' => 'test'],
            'creado_en'  => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }
}
