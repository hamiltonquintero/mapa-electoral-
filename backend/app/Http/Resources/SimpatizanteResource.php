<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SimpatizanteResource extends JsonResource
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
            'nombres' => $this->nombres,
            'apellidos' => $this->apellidos,
            'nombre_completo' => $this->nombre_completo,
            
            // Campos cifrados (se descifran automáticamente con accessors)
            'cedula' => $this->when(
                $request->user()?->can('view', $this->resource),
                $this->cedula
            ),
            'telefono' => $this->when(
                $request->user()?->can('view', $this->resource),
                $this->telefono
            ),
            'correo' => $this->when(
                $request->user()?->can('view', $this->resource),
                $this->correo
            ),
            'direccion' => $this->when(
                $request->user()?->can('view', $this->resource),
                $this->direccion
            ),
            
            // Relaciones
            'campana_id' => $this->campana_id,
            'campana' => new CampanaSimpleResource($this->whenLoaded('campana')),
            
            'territorio_id' => $this->territorio_id,
            'territorio' => new TerritorioSimpleResource($this->whenLoaded('territorio')),
            
            'puesto_id' => $this->puesto_id,
            'puesto' => new PuestoVotacionSimpleResource($this->whenLoaded('puesto')),
            'mesa' => $this->mesa,
            
            'lider_id' => $this->lider_id,
            'lider' => new UserSimpleResource($this->whenLoaded('lider')),
            
            'candidatos' => CandidatoSimpleResource::collection($this->whenLoaded('candidatos')),
            
            'formulario_id' => $this->formulario_id,
            'formulario' => $this->when(
                $this->relationLoaded('formulario') && $this->formulario,
                function () {
                    return [
                        'id' => $this->formulario->id,
                        'estado' => $this->formulario->estado,
                        'url' => $this->formulario->url_temporal,
                    ];
                }
            ),
            
            'lote_id' => $this->lote_id,
            'lote' => $this->when(
                $this->relationLoaded('lote') && $this->lote,
                function () {
                    return [
                        'id' => $this->lote->id,
                        'nombre' => $this->lote->nombre,
                    ];
                }
            ),
            
            // Estado y metadata
            'estado' => $this->estado,
            'ha_votado' => (bool) $this->ha_votado,
            'hora_voto' => $this->hora_voto?->format('H:i'),
            'observaciones' => $this->observaciones,
            
            'creado_por' => $this->creado_por,
            'creador' => new UserSimpleResource($this->whenLoaded('creadoPor')),
            
            'actualizado_por' => $this->actualizado_por,
            'actualizador' => new UserSimpleResource($this->whenLoaded('actualizadoPor')),
            
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'deleted_at' => $this->deleted_at?->toISOString(),
            
            // Historial de cambios
            'historial_cambios' => $this->when(
                $this->relationLoaded('historialCambios'),
                function () {
                    return $this->historialCambios->map(function ($cambio) {
                        return [
                            'id' => $cambio->id,
                            'campo' => $cambio->campo,
                            'valor_anterior' => $cambio->valor_anterior,
                            'valor_nuevo' => $cambio->valor_nuevo,
                            'user_id' => $cambio->user_id,
                            'created_at' => $cambio->created_at?->toISOString(),
                        ];
                    });
                }
            ),
        ];
    }
}
