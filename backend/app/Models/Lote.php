<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lote extends Model
{
    use HasFactory;

    protected $table = 'lotes';

    protected $fillable = [
        'descripcion',
        'territorio_id',
        'fecha',
        'creado_por',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
        ];
    }

    /**
     * Relaciones
     */
    public function territorio()
    {
        return $this->belongsTo(Territorio::class);
    }

    public function creadoPor()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function simpatizantes()
    {
        return $this->hasMany(Simpatizante::class);
    }

    /**
     * Accessor para total de registros.
     */
    public function getTotalRegistrosAttribute(): int
    {
        return $this->simpatizantes()->count();
    }

    /**
     * Scopes
     */
    public function scopeEnTerritorio($query, int $territorioId)
    {
        return $query->where('territorio_id', $territorioId);
    }

    public function scopePorFecha($query, string $fecha)
    {
        return $query->whereDate('fecha', $fecha);
    }

    public function scopePorUsuario($query, int $userId)
    {
        return $query->where('creado_por', $userId);
    }
}
