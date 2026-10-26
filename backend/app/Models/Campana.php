<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Campana extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'campanas';

    protected $fillable = [
        'nombre',
        'descripcion',
        'activa',
        'fecha_inicio',
        'fecha_cierre',
    ];

    protected function casts(): array
    {
        return [
            'activa' => 'boolean',
            'fecha_inicio' => 'date',
            'fecha_cierre' => 'date',
        ];
    }

    /**
     * Relaciones
     */
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
        return $this->belongsToMany(User::class, 'usuario_campana');
    }

    public function importaciones()
    {
        return $this->hasMany(Importacion::class);
    }

    /**
     * Scopes
     */
    public function scopeActivas($query)
    {
        return $query->where('activa', true);
    }

    public function scopeVigentes($query)
    {
        $hoy = now()->toDateString();
        return $query->where('activa', true)
            ->where(function ($q) use ($hoy) {
                $q->whereNull('fecha_cierre')
                  ->orWhere('fecha_cierre', '>=', $hoy);
            });
    }
}
