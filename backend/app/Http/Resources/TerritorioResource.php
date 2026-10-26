<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TerritorioResource extends JsonResource
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
            'tipo' => $this->tipo,
            'codigo' => $this->codigo,
            'nivel' => $this->nivel,
            'poblacion' => $this->poblacion,
            'padre_id' => $this->padre_id,
            'padre' => new TerritorioSimpleResource($this->whenLoaded('padre')),
            'centroide' => $this->centroide_lat_lng,
            'geom_wkt' => $this->when($request->input('incluir_geometria'), $this->geom_wkt),
            'tiene_hijos' => $this->when(isset($this->hijos_count), $this->hijos_count > 0),
            'total_simpatizantes' => $this->when(isset($this->total_simpatizantes), $this->total_simpatizantes),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
