<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nombres'           => fake('es_CO')->firstName(),
            'apellidos'         => fake('es_CO')->lastName() . ' ' . fake('es_CO')->lastName(),
            'email'             => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password'          => Hash::make('password'),
            'remember_token'    => Str::random(10),
            'activo'            => true,
            'google2fa_enabled' => false,
            'google2fa_secret'  => null,
            'intentos_fallidos' => 0,
            'bloqueado_hasta'   => null,
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn(array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn(array $attributes) => [
            'activo' => false,
        ]);
    }

    public function with2fa(): static
    {
        return $this->state(fn(array $attributes) => [
            'google2fa_enabled' => true,
            'google2fa_secret'  => encrypt(app('pragmarx.google2fa')->generateSecretKey()),
        ]);
    }
}
