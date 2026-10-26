<?php

namespace App\Models;

use App\Traits\HasEncryptedFields;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Formulario extends Model
{
    use HasFactory, HasEncryptedFields;

    protected $table = 'formularios';

    protected $fillable = [
        'consecutivo',
        'archivo_ruta',
        'hash_sha256',
        'fecha_firma',
        'tipo_archivo',
        'tamano_bytes',
        'subido_por',
    ];

    /**
     * Campos cifrados.
     */
    protected $encrypted = [
        'archivo_ruta',
    ];

    protected function casts(): array
    {
        return [
            'fecha_firma' => 'date',
            'tamano_bytes' => 'integer',
    ];
    }

    /**
     * Accessor para ruta descifrada.
     */
    public function getRutaAttribute(): ?string
    {
        return $this->decryptField($this->archivo_ruta);
    }

    /**
     * Accessor para URL firmada temporal.
     */
    public function getUrlTemporalAttribute(): string
    {
        // Generar URL firmada válida por 10 minutos
        return route('formularios.download', [
            'id' => $this->id,
            'signature' => $this->generarFirma(),
            'expires' => now()->addMinutes(10)->timestamp,
        ]);
    }

    /**
     * Generar firma para URL.
     */
    protected function generarFirma(): string
    {
        $expires = now()->addMinutes(10)->timestamp;
        return hash_hmac('sha256', "{$this->id}:{$expires}", config('app.key'));
    }

    /**
     * Relaciones
     */
    public function subidoPor()
    {
        return $this->belongsTo(User::class, 'subido_por');
    }

    public function simpatizantes()
    {
        return $this->hasMany(Simpatizante::class);
    }

    /**
     * Scopes
     */
    public function scopePorTipo($query, string $tipo)
    {
        return $query->where('tipo_archivo', $tipo);
    }

    public function scopePorFecha($query, string $fecha)
    {
        return $query->whereDate('fecha_firma', $fecha);
    }

    public function scopeSubidoPor($query, int $userId)
    {
        return $query->where('subido_por', $userId);
    }
}
