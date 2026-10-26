<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PuestoVotacion extends Model
{
    use HasFactory;

    protected $table = 'puestos_votacion';

    protected $fillable = [
        'territorio_id',
        'nombre',
        'direccion',
        'total_mesas',
        'comuna',
        'mujeres',
        'hombres',
        'censo_total',
        'codigo_registraduria',
        'latitud',
        'longitud',
    ];

    protected function casts(): array
    {
        return [
            'total_mesas' => 'integer',
        ];
    }

    /**
     * Relaciones
     */
    public function territorio()
    {
        return $this->belongsTo(Territorio::class);
    }

    /**
     * Relación con la tabla importaciones.
     */
    public function importacion()
    {
        return $this->belongsTo(Importacion::class);
    }

    public function simpatizantes()
    {
        return $this->hasMany(Simpatizante::class, 'puesto_id');
    }

    /**
     * Accessor para ubicación (lat, lng)
     * MySQL: Usa campos latitud/longitud separados
     */
    public function getUbicacionAttribute(): ?array
    {
        $lat = $this->attributes['latitud'] ?? null;
        $lng = $this->attributes['longitud'] ?? null;

        if (!$lat || !$lng) {
            return null;
        }

        return ['lat' => (float) $lat, 'lng' => (float) $lng];
    }

    /**
     * Establecer ubicación desde lat/lng
     * MySQL: guarda en columnas latitud/longitud
     */
    public function setUbicacion(float $lat, float $lng): void
    {
        $this->latitud  = $lat;
        $this->longitud = $lng;
        $this->save();
    }

    /**
     * Scopes
     */
    public function scopeEnTerritorio($query, int $territorioId)
    {
        return $query->where('territorio_id', $territorioId);
    }

    public function scopeConUbicacion($query)
    {
        return $query->whereNotNull('latitud')->whereNotNull('longitud');
    }

    public function scopeSinUbicacion($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('latitud')->orWhereNull('longitud');
        });
    }
}
