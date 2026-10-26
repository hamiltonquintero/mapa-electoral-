<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Auditoria extends Model
{
    use HasFactory;

    protected $table = 'auditoria';

    const CREATED_AT = 'creado_en';
    const UPDATED_AT = null; // No tiene updated_at

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'rol',
        'accion',
        'entidad',
        'entidad_id',
        'ip',
        'user_agent',
        'detalle',
        'creado_en',
    ];

    protected function casts(): array
    {
        return [
            'detalle' => 'array',
            'creado_en' => 'datetime',
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
     * Registrar evento de auditoría.
     */
    public static function registrar(
        string $accion,
        ?string $entidad = null,
        ?string $entidadId = null,
        ?array $detalle = null
    ): void {
        $user = auth()->user();

        self::create([
            'user_id' => $user?->id,
            'rol' => $user?->roles->first()?->name,
            'accion' => $accion,
            'entidad' => $entidad,
            'entidad_id' => $entidadId,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'detalle' => $detalle,
            'creado_en' => now(),
        ]);
    }

    /**
     * Scopes
     */
    public function scopePorUsuario($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopePorAccion($query, string $accion)
    {
        return $query->where('accion', $accion);
    }

    public function scopePorEntidad($query, string $entidad, ?string $entidadId = null)
    {
        $query->where('entidad', $entidad);
        
        if ($entidadId) {
            $query->where('entidad_id', $entidadId);
        }
        
        return $query;
    }

    public function scopeEntre($query, $desde, $hasta)
    {
        return $query->whereBetween('creado_en', [$desde, $hasta]);
    }

    public function scopePorIp($query, string $ip)
    {
        return $query->where('ip', $ip);
    }
}
