<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SolicitudTitular extends Model
{
    use HasFactory;

    protected $table = 'solicitudes_titular';

    protected $fillable = [
        'tipo',
        'simpatizante_id',
        'cedula_hash',
        'estado',
        'descripcion',
        'fecha_recepcion',
        'fecha_limite',
        'resuelta_en',
        'notas',
        'responsable_id',
    ];

    protected function casts(): array
    {
        return [
            'fecha_recepcion' => 'date',
            'fecha_limite' => 'date',
            'resuelta_en' => 'datetime',
        ];
    }

    /**
     * Boot del modelo.
     */
    protected static function boot()
    {
        parent::boot();

        // Al crear, calcular fecha límite (15 días hábiles)
        static::creating(function ($solicitud) {
            if (!$solicitud->fecha_limite) {
                $solicitud->fecha_limite = self::calcularFechaLimite($solicitud->fecha_recepcion);
            }
        });
    }

    /**
     * Calcular fecha límite (15 días hábiles).
     */
    protected static function calcularFechaLimite($fechaInicio): string
    {
        $fecha = \Carbon\Carbon::parse($fechaInicio);
        $diasHabiles = 0;

        while ($diasHabiles < 15) {
            $fecha->addDay();
            
            // Contar solo días hábiles (lunes a viernes)
            if ($fecha->isWeekday()) {
                $diasHabiles++;
            }
        }

        return $fecha->toDateString();
    }

    /**
     * Relaciones
     */
    public function simpatizante()
    {
        return $this->belongsTo(Simpatizante::class);
    }

    public function responsable()
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    /**
     * Verificar si está vencida.
     */
    public function estaVencida(): bool
    {
        return $this->estado !== 'resuelta' && now()->toDateString() > $this->fecha_limite;
    }

    /**
     * Scopes
     */
    public function scopePorTipo($query, string $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    public function scopePorEstado($query, string $estado)
    {
        return $query->where('estado', $estado);
    }

    public function scopePendientes($query)
    {
        return $query->whereIn('estado', ['recibida', 'en_revision']);
    }

    public function scopeVencidas($query)
    {
        return $query->whereIn('estado', ['recibida', 'en_revision'])
            ->where('fecha_limite', '<', now()->toDateString());
    }

    public function scopePorResponsable($query, int $responsableId)
    {
        return $query->where('responsable_id', $responsableId);
    }
}
