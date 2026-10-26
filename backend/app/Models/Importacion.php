<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Importacion extends Model
{
    use HasFactory;

    protected $table = 'importaciones';

    protected $fillable = [
        'campana_id',
        'archivo_original',
        'archivo_ruta',
        'estado',
        'filas_total',
        'filas_ok',
        'filas_error',
        'errores',
        'reversible',
        'reversible_hasta',
        'revertido_en',
        'revertido_por',
        'creado_por',
    ];

    protected function casts(): array
    {
        return [
            'errores' => 'array',
            'reversible' => 'boolean',
            'reversible_hasta' => 'datetime',
            'revertido_en' => 'datetime',
            'filas_total' => 'integer',
            'filas_ok' => 'integer',
            'filas_error' => 'integer',
        ];
    }

    /**
     * Relaciones
     */
    public function campana()
    {
        return $this->belongsTo(Campana::class);
    }

    public function creadoPor()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function revertidoPor()
    {
        return $this->belongsTo(User::class, 'revertido_por');
    }

    /**
     * Verificar si puede ser revertida.
     */
    public function puedeRevertirse(): bool
    {
        return $this->reversible 
            && $this->estado === 'completado'
            && !$this->revertido_en
            && (!$this->reversible_hasta || $this->reversible_hasta->isFuture());
    }

    /**
     * Accessor para porcentaje de éxito.
     */
    public function getPorcentajeExitoAttribute(): float
    {
        if ($this->filas_total === 0) {
            return 0;
        }
        
        return round(($this->filas_ok / $this->filas_total) * 100, 2);
    }

    /**
     * Scopes
     */
    public function scopeEnCampana($query, int $campanaId)
    {
        return $query->where('campana_id', $campanaId);
    }

    public function scopePorEstado($query, string $estado)
    {
        return $query->where('estado', $estado);
    }

    public function scopeCompletadas($query)
    {
        return $query->where('estado', 'completado');
    }

    public function scopeReversibles($query)
    {
        return $query->where('reversible', true)
            ->where('estado', 'completado')
            ->whereNull('revertido_en')
            ->where(function ($q) {
                $q->whereNull('reversible_hasta')
                  ->orWhere('reversible_hasta', '>', now());
            });
    }

    public function scopePorUsuario($query, int $userId)
    {
        return $query->where('creado_por', $userId);
    }
}
