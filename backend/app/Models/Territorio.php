<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Territorio extends Model
{
    use HasFactory;

    protected $table = 'territorios';

    protected $fillable = [
        'padre_id',
        'tipo',
        'nombre',
        'codigo_divipola',
    ];

    protected function casts(): array
    {
        return [
            'padre_id' => 'integer',
        ];
    }

    /**
     * Relaciones
     */
    public function padre()
    {
        return $this->belongsTo(Territorio::class, 'padre_id');
    }

    public function hijos()
    {
        return $this->hasMany(Territorio::class, 'padre_id');
    }

    public function puestosVotacion()
    {
        return $this->hasMany(PuestoVotacion::class);
    }

    public function candidatos()
    {
        return $this->hasMany(Candidato::class);
    }

    public function simpatizantes()
    {
        return $this->hasMany(Simpatizante::class);
    }

    public function usuarios()
    {
        return $this->belongsToMany(User::class, 'usuario_territorio');
    }

    public function lotes()
    {
        return $this->hasMany(Lote::class);
    }

    /**
     * Obtener todos los descendientes (versión MySQL recursiva).
     */
    public function obtenerDescendientes()
    {
        $result = [];
        $this->obtenerDescendientesRecursivos($this->id, $result);
        return $result;
    }

    public static function obtenerDescendientesIds(int $territorioId): array
    {
        $territorio = static::find($territorioId);
        if (!$territorio) {
            return [$territorioId];
        }
        $result = [];
        $territorio->obtenerDescendientesRecursivos($territorioId, $result);
        $ids = array_map(fn($t) => is_object($t) ? $t->id : $t['id'], $result);
        return array_unique(array_merge([$territorioId], $ids));
    }

    /**
     * Helper recursivo para obtener descendientes.
     */
    private function obtenerDescendientesRecursivos(int $territorioId, array &$result)
    {
        $hijos = static::where('padre_id', $territorioId)->get();
        
        foreach ($hijos as $hijo) {
            $result[] = $hijo;
            $this->obtenerDescendientesRecursivos($hijo->id, $result);
        }
    }

    /**
     * Obtener jerarquía completa (hacia arriba).
     */
    public function obtenerJerarquia()
    {
        $jerarquia = [];
        $actual = $this;
        
        while ($actual) {
            array_unshift($jerarquia, $actual);
            $actual = $actual->padre;
        }
        
        return $jerarquia;
    }

    /**
     * Obtener IDs de todos los descendientes.
     */
    public function obtenerIdsDescendientes(): array
    {
        $descendientes = $this->obtenerDescendientes();
        return array_column($descendientes, 'id');
    }

    /**
     * Accessor para geometría (WKT)
     * MySQL simplificado: retorna null ya que no tenemos geometrías reales
     */
    public function getGeomWktAttribute(): ?string
    {
        return null;
    }

    /**
     * Accessor para centroide (lat, lng)
     * MySQL: Usa campos separados centroide_lat y centroide_lng
     */
    public function getCentroideLatLngAttribute(): ?array
    {
        if (!isset($this->attributes['centroide_lat']) || !isset($this->attributes['centroide_lng'])) {
            return null;
        }
        
        return [
            'lat' => (float) $this->attributes['centroide_lat'],
            'lng' => (float) $this->attributes['centroide_lng'],
        ];
    }

    /**
     * Scopes
     */
    public function scopeTipo($query, string $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    public function scopeDepartamentos($query)
    {
        return $query->where('tipo', 'departamento');
    }

    public function scopeMunicipios($query)
    {
        return $query->where('tipo', 'municipio');
    }

    public function scopeHijosDe($query, int $padreId)
    {
        return $query->where('padre_id', $padreId);
    }

    public function scopeRaices($query)
    {
        return $query->whereNull('padre_id');
    }
}
