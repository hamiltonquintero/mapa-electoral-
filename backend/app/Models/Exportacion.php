<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exportacion extends Model
{
    use HasFactory;

    protected $table = 'exportaciones';

    protected $fillable = [
        'user_id',
        'tipo',
        'motivo',
        'filtros',
        'total_registros',
        'archivo_ruta',
        'estado',
        'error_mensaje',
        'descargado_en',
    ];

    protected function casts(): array
    {
        return [
            'filtros' => 'array',
            'total_registros' => 'integer',
            'descargado_en' => 'datetime',
        ];
    }

    /**
     * Relaciones
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Marcar como descargado.
     */
    public function marcarDescargado(): void
    {
        $this->update(['descargado_en' => now()]);
    }

    /**
     * Scopes
     */
    public function scopePorUsuario($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopePorTipo($query, string $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    public function scopePorEstado($query, string $estado)
    {
        return $query->where('estado', $estado);
    }

    public function scopeCompletadas($query)
    {
        return $query->where('estado', 'completado');
    }

    public function scopePendientes($query)
    {
        return $query->whereIn('estado', ['solicitado', 'procesando']);
    }
}
