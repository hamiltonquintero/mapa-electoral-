<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistorialCambio extends Model
{
    use HasFactory;

    protected $table = 'historial_cambios';

    const CREATED_AT = 'creado_en';
    const UPDATED_AT = null; // No tiene updated_at

    public $timestamps = false;

    protected $fillable = [
        'simpatizante_id',
        'user_id',
        'campo',
        'valor_anterior',
        'valor_nuevo',
        'creado_en',
    ];

    protected function casts(): array
    {
        return [
            'creado_en' => 'datetime',
        ];
    }

    /**
     * Relaciones
     */
    public function simpatizante()
    {
        return $this->belongsTo(Simpatizante::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scopes
     */
    public function scopePorSimpatizante($query, string $simpatizanteId)
    {
        return $query->where('simpatizante_id', $simpatizanteId);
    }

    public function scopePorUsuario($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopePorCampo($query, string $campo)
    {
        return $query->where('campo', $campo);
    }

    public function scopeEntre($query, $desde, $hasta)
    {
        return $query->whereBetween('creado_en', [$desde, $hasta]);
    }
}
