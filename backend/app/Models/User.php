<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nombres',
        'apellidos',
        'name',
        'email',
        'password',
        'google2fa_enabled',
        'two_factor_enabled',
        'google2fa_secret',
        'activo',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'name',
        'nombre_completo',
        'two_factor_enabled',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'google2fa_secret',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'google2fa_enabled' => 'boolean',
            'google2fa_verified_at' => 'datetime',
            'activo' => 'boolean',
            'bloqueado_hasta' => 'datetime',
            'ultimo_acceso' => 'datetime',
        ];
    }

    /**
     * Accessor para nombre completo.
     */
    public function getNombreCompletoAttribute(): string
    {
        return trim("{$this->nombres} {$this->apellidos}");
    }

    public function getNameAttribute(): string
    {
        return $this->nombre_completo;
    }

    public function setNameAttribute($value): void
    {
        $parts = explode(' ', trim((string) $value), 2);
        $this->attributes['nombres'] = $parts[0] ?? '';
        $this->attributes['apellidos'] = $parts[1] ?? '';
    }

    public function getTwoFactorEnabledAttribute(): bool
    {
        return (bool) $this->google2fa_enabled;
    }

    public function setTwoFactorEnabledAttribute($value): void
    {
        $this->attributes['google2fa_enabled'] = (bool) $value;
    }

    /**
     * Verificar si el usuario está bloqueado.
     */
    public function estaBloqueado(): bool
    {
        return $this->bloqueado_hasta && $this->bloqueado_hasta->isFuture();
    }

    /**
     * Verificar si requiere 2FA.
     */
    public function requiere2FA(): bool
    {
        return $this->google2fa_enabled;
    }

    /**
     * Relaciones
     */
    public function campanas()
    {
        return $this->belongsToMany(Campana::class, 'usuario_campana');
    }

    public function territorios()
    {
        return $this->belongsToMany(Territorio::class, 'usuario_territorio');
    }

    public function simpatizantesCreados()
    {
        return $this->hasMany(Simpatizante::class, 'creado_por');
    }

    public function simpatizantesActualizados()
    {
        return $this->hasMany(Simpatizante::class, 'actualizado_por');
    }

    public function simpatizantesLiderados()
    {
        return $this->hasMany(Simpatizante::class, 'lider_id');
    }

    public function lotes()
    {
        return $this->hasMany(Lote::class, 'creado_por');
    }

    public function formularios()
    {
        return $this->hasMany(Formulario::class, 'subido_por');
    }

    public function importaciones()
    {
        return $this->hasMany(Importacion::class, 'creado_por');
    }

    public function exportaciones()
    {
        return $this->hasMany(Exportacion::class, 'user_id');
    }

    public function auditorias()
    {
        return $this->hasMany(Auditoria::class, 'user_id');
    }

    public function historialCambios()
    {
        return $this->hasMany(HistorialCambio::class, 'user_id');
    }

    /**
     * Scopes
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopeConRol($query, string $rol)
    {
        return $query->role($rol);
    }

    public function scopeEnCampana($query, int $campanaId)
    {
        return $query->whereHas('campanas', function ($q) use ($campanaId) {
            $q->where('campanas.id', $campanaId);
        });
    }

    public function scopeEnTerritorio($query, int $territorioId)
    {
        return $query->whereHas('territorios', function ($q) use ($territorioId) {
            $q->where('territorios.id', $territorioId);
        });
    }

    /**
     * Verificar si el usuario tiene acceso a un territorio específico
     * (incluyendo descendientes)
     */
    public function hasAccessToTerritorio(string $territorioId): bool
    {
        // Superadministradores tienen acceso completo
        if ($this->hasRole('Superadministrador')) {
            return true;
        }

        // Si ya tenemos los territorios accesibles calculados (desde middleware)
        if ($this->getAttribute('accessible_territory_ids')) {
            return in_array($territorioId, $this->getAttribute('accessible_territory_ids'));
        }

        // Calcular manualmente sin funciones PostgreSQL
        $territoriosAsignados = $this->territorios()->pluck('territorios.id')->toArray();
        
        if (in_array($territorioId, $territoriosAsignados)) {
            return true;
        }

        // Verificar en descendientes recursivamente
        foreach ($territoriosAsignados as $asignado) {
            $descendientes = $this->getDescendientesRecursivos($asignado);
            
            if (in_array($territorioId, $descendientes)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Obtener descendientes de un territorio recursivamente
     */
    private function getDescendientesRecursivos(int $territorioId, array &$result = []): array
    {
        $hijos = Territorio::where('padre_id', $territorioId)->pluck('id')->toArray();
        
        foreach ($hijos as $hijo) {
            if (!in_array($hijo, $result)) {
                $result[] = $hijo;
                $this->getDescendientesRecursivos($hijo, $result);
            }
        }
        
        return $result;
    }

    /**
     * Verificar si el usuario tiene acceso a una campaña específica
     */
    public function hasAccessToCampana(string $campanaId): bool
    {
        // Superadministradores tienen acceso completo
        if ($this->hasRole('Superadministrador')) {
            return true;
        }

        // Si ya tenemos las campañas accesibles calculadas (desde middleware)
        if ($this->getAttribute('accessible_campaign_ids')) {
            return in_array($campanaId, $this->getAttribute('accessible_campaign_ids'));
        }

        // Verificar manualmente
        return $this->campanas()->where('campanas.id', $campanaId)->exists();
    }

    /**
     * Obtener todos los territorios accesibles (asignados + descendientes)
     */
    public function getAccessibleTerritoryIds(): array
    {
        // Si ya están calculados desde middleware
        if ($this->getAttribute('accessible_territory_ids')) {
            return $this->getAttribute('accessible_territory_ids');
        }

        // Superadministradores tienen acceso a todos
        if ($this->hasRole('Superadministrador')) {
            return Territorio::pluck('id')->toArray();
        }

        // Calcular
        $territoriosAsignados = $this->territorios()->pluck('territorios.id')->toArray();
        
        if (empty($territoriosAsignados)) {
            return [];
        }

        $todosLosTerritorios = $territoriosAsignados;

        foreach ($territoriosAsignados as $territorioId) {
            $descendientes = $this->getDescendientesRecursivos($territorioId);
            $todosLosTerritorios = array_merge($todosLosTerritorios, $descendientes);
        }

        return array_unique($todosLosTerritorios);
    }

    /**
     * Obtener todas las campañas accesibles
     */
    public function getAccessibleCampaignIds(): array
    {
        // Si ya están calculadas desde middleware
        if ($this->getAttribute('accessible_campaign_ids')) {
            return $this->getAttribute('accessible_campaign_ids');
        }

        // Superadministradores tienen acceso a todas
        if ($this->hasRole('Superadministrador')) {
            return Campana::pluck('id')->toArray();
        }

        return $this->campanas()->pluck('campanas.id')->toArray();
    }
}
