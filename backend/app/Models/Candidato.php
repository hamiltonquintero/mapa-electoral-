<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Candidato extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'candidatos';

    protected $fillable = [
        'campana_id',
        'nombre',
        'cargo',
        'territorio_id',
        'color',
        'descripcion',
        'foto',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    /**
     * Relaciones
     */
    public function campana()
    {
        return $this->belongsTo(Campana::class);
    }

    public function territorio()
    {
        return $this->belongsTo(Territorio::class);
    }

    public function simpatizantes()
    {
        return $this->belongsToMany(Simpatizante::class, 'simpatizante_candidato');
    }

    /**
     * Scopes
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopePorCargo($query, string $cargo)
    {
        return $query->where('cargo', $cargo);
    }

    public function scopeEnCampana($query, int $campanaId)
    {
        return $query->where('campana_id', $campanaId);
    }

    public function scopeGobernacion($query)
    {
        return $query->where('cargo', 'gobernacion');
    }

    public function scopeAlcaldia($query)
    {
        return $query->where('cargo', 'alcaldia');
    }

    public function scopeAsamblea($query)
    {
        return $query->where('cargo', 'asamblea');
    }
}
