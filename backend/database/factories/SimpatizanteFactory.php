<?php

namespace Database\Factories;

use App\Models\Campana;
use App\Models\Territorio;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SimpatizanteFactory extends Factory
{
    public function definition(): array
    {
        $cedula = (string) fake()->unique()->numerify('##########');

        return [
            'id'          => Str::uuid(),
            'campana_id'  => Campana::factory(),
            'nombres'     => fake('es_CO')->firstName(),
            'apellidos'   => fake('es_CO')->lastName() . ' ' . fake('es_CO')->lastName(),
            // En producción iría cifrado; en tests dejamos el valor en texto claro
            // para simplificar sin necesitar las claves reales de cifrado
            'cedula_cifrada'  => $cedula,
            'cedula_hash'     => hash_hmac('sha256', $cedula, config('encryption.cedula_hash_key', 'test-key')),
            'telefono_cifrado' => null,
            'correo_cifrado'   => null,
            'direccion_cifrada'=> null,
            'territorio_id'    => Territorio::factory(),
            'puesto_id'        => null,
            'mesa'             => null,
            'lider_id'         => null,
            'formulario_id'    => null,
            'lote_id'          => null,
            'estado'           => 'borrador',
            'creado_por'       => User::factory(),
        ];
    }

    public function validado(): static
    {
        return $this->state(fn(array $attributes) => ['estado' => 'validado']);
    }

    public function pendienteEvidencia(): static
    {
        return $this->state(fn(array $attributes) => ['estado' => 'pendiente_evidencia']);
    }

    public function enCampana(Campana $campana): static
    {
        return $this->state(fn(array $attributes) => ['campana_id' => $campana->id]);
    }

    public function enTerritorio(Territorio $territorio): static
    {
        return $this->state(fn(array $attributes) => ['territorio_id' => $territorio->id]);
    }
}
