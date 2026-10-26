<?php

namespace App\Models;

use App\Traits\HasEncryptedFields;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Simpatizante extends Model
{
    use HasFactory, HasUuids, SoftDeletes, HasEncryptedFields;

    protected $table = 'simpatizantes';

    /**
     * Indica que la clave primaria es UUID.
     */
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'campana_id',
        'nombres',
        'apellidos',
        'cedula_cifrada',
        'cedula_hash',
        'telefono_cifrado',
        'correo_cifrado',
        'direccion_cifrada',
        'territorio_id',
        'puesto_id',
        'mesa',
        'lider_id',
        'formulario_id',
        'lote_id',
        'estado',
        'ha_votado',
        'hora_voto',
        'observaciones',
        'creado_por',
        'actualizado_por',
    ];

    /**
     * Campos que deben ser cifrados.
     */
    protected $encrypted = [
        'cedula_cifrada',
        'telefono_cifrado',
        'correo_cifrado',
        'direccion_cifrada',
    ];

    protected function casts(): array
    {
        return [
            'mesa' => 'integer',
            'ha_votado' => 'boolean',
            'hora_voto' => 'datetime',
        ];
    }

    /**
     * Boot del modelo.
     */
    protected static function boot()
    {
        parent::boot();

        // Al crear, generar el hash ciego de la cédula
        static::creating(function ($simpatizante) {
            if (!empty($simpatizante->cedula_cifrada) && empty($simpatizante->cedula_hash)) {
                // Si la cédula aún no está cifrada, generar hash del valor plano
                $cedulaPlana = $simpatizante->cedula_cifrada;
                $simpatizante->cedula_hash = $simpatizante->generateBlindIndex($cedulaPlana);
            }
        });
    }

    /**
     * Accessors para campos cifrados (descifrado on-demand).
     */
    public function getCedulaAttribute(): ?string
    {
        return $this->decryptField($this->cedula_cifrada);
    }

    public function getTelefonoAttribute(): ?string
    {
        return $this->decryptField($this->telefono_cifrado);
    }

    public function getCorreoAttribute(): ?string
    {
        return $this->decryptField($this->correo_cifrado);
    }

    public function getDireccionAttribute(): ?string
    {
        return $this->decryptField($this->direccion_cifrada);
    }

    /**
     * Mutators para campos cifrados.
     */
    public function setCedulaAttribute(?string $value): void
    {
        if (!empty($value)) {
            $this->attributes['cedula_cifrada'] = $value; // Se cifrará en boot
            $this->attributes['cedula_hash'] = $this->generateBlindIndex($value);
        }
    }

    public function setTelefonoAttribute(?string $value): void
    {
        if (!empty($value)) {
            $this->attributes['telefono_cifrado'] = $value;
        }
    }

    public function setCorreoAttribute(?string $value): void
    {
        if (!empty($value)) {
            $this->attributes['correo_cifrado'] = $value;
        }
    }

    public function setDireccionAttribute(?string $value): void
    {
        if (!empty($value)) {
            $this->attributes['direccion_cifrada'] = $value;
        }
    }

    /**
     * Accessor para nombre completo.
     */
    public function getNombreCompletoAttribute(): string
    {
        return "{$this->nombres} {$this->apellidos}";
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

    public function puesto()
    {
        return $this->belongsTo(PuestoVotacion::class, 'puesto_id');
    }

    public function lider()
    {
        return $this->belongsTo(User::class, 'lider_id');
    }

    public function formulario()
    {
        return $this->belongsTo(Formulario::class);
    }

    public function lote()
    {
        return $this->belongsTo(Lote::class);
    }

    public function creadoPor()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function actualizadoPor()
    {
        return $this->belongsTo(User::class, 'actualizado_por');
    }

    public function candidatos()
    {
        return $this->belongsToMany(Candidato::class, 'simpatizante_candidato');
    }

    public function historialCambios()
    {
        return $this->hasMany(HistorialCambio::class);
    }

    /**
     * Scopes
     */
    public function scopeEnCampana($query, int $campanaId)
    {
        return $query->where('campana_id', $campanaId);
    }

    public function scopeEnTerritorio($query, int $territorioId)
    {
        return $query->where('territorio_id', $territorioId);
    }

    public function scopeEnTerritorios($query, array $territorioIds)
    {
        return $query->whereIn('territorio_id', $territorioIds);
    }

    public function scopeEnPuesto($query, int $puestoId)
    {
        return $query->where('puesto_id', $puestoId);
    }

    public function scopeEnMesa($query, int $puestoId, int $mesa)
    {
        return $query->where('puesto_id', $puestoId)->where('mesa', $mesa);
    }

    public function scopePorEstado($query, string $estado)
    {
        return $query->where('estado', $estado);
    }

    public function scopeValidados($query)
    {
        return $query->where('estado', 'validado');
    }

    public function scopeBorradores($query)
    {
        return $query->where('estado', 'borrador');
    }

    public function scopePendientesEvidencia($query)
    {
        return $query->where('estado', 'pendiente_evidencia');
    }

    public function scopeSinPuesto($query)
    {
        return $query->whereNull('puesto_id');
    }

    public function scopeSinMesa($query)
    {
        return $query->whereNull('mesa');
    }

    public function scopeSinFormulario($query)
    {
        return $query->whereNull('formulario_id');
    }

    public function scopeApoyaCandidato($query, int $candidatoId)
    {
        return $query->whereHas('candidatos', function ($q) use ($candidatoId) {
            $q->where('candidatos.id', $candidatoId);
        });
    }

    public function scopePorLider($query, int $liderId)
    {
        return $query->where('lider_id', $liderId);
    }

    public function scopeEnLote($query, int $loteId)
    {
        return $query->where('lote_id', $loteId);
    }

    public function scopeBuscarPorCedula($query, string $cedula)
    {
        $hash = (new self)->generateBlindIndex($cedula);
        return $query->where('cedula_hash', $hash);
    }

    /**
     * Scope: filtrar por territorios accesibles del usuario autenticado
     */
    public function scopeAccesiblesPorUsuario($query, User $user)
    {
        // Superadministradores ven todo
        if ($user->hasRole('Superadministrador')) {
            return $query;
        }

        // Filtrar por territorios accesibles
        $territoriosAccesibles = $user->getAccessibleTerritoryIds();
        
        if (empty($territoriosAccesibles)) {
            // Usuario sin territorios asignados no ve nada
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('territorio_id', $territoriosAccesibles);
    }

    /**
     * Scope: filtrar por campañas accesibles del usuario autenticado
     */
    public function scopeEnCampanasDeUsuario($query, User $user)
    {
        // Superadministradores ven todo
        if ($user->hasRole('Superadministrador')) {
            return $query;
        }

        $campanasAccesibles = $user->getAccessibleCampaignIds();
        
        if (empty($campanasAccesibles)) {
            // Usuario sin campañas asignadas no ve nada
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('campana_id', $campanasAccesibles);
    }

    /**
     * Scope combinado: aplicar todas las restricciones del usuario
     */
    public function scopeAccesiblePorUsuario($query, User $user)
    {
        return $query->accesiblesPorUsuario($user)
                     ->enCampanasDeUsuario($user);
    }
}
