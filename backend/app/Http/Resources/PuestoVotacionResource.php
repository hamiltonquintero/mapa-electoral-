<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PuestoVotacionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'codigo' => $this->codigo,
            'direccion' => $this->direccion,
            'total_mesas' => $this->total_mesas,
            'territorio_id' => $this->territorio_id,
            'territorio' => new TerritorioSimpleResource($this->whenLoaded('territorio')),
            'ubicacion' => $this->ubicacion,
            'activo' => $this->activo,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
