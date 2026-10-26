# Sistema de Autorización Territorial

El Sistema de Autorización Territorial controla automáticamente el acceso de los usuarios a datos según:

1. **Territorios asignados** (municipios, barrios/veredas)
2. **Campañas asignadas** (cada usuario pertenece a una o más campañas)
3. **Roles y permisos** (definidos con Spatie Permission)

## 🎯 Concepto

Los usuarios tienen **acceso automático** a:
- Sus territorios **directamente asignados**
- Todos los **territorios descendientes** (si se les asigna un municipio, acceden a todos sus barrios/veredas)
- **Datos de simpatizantes** solo dentro de sus territorios y campañas

Los **Superadministradores** tienen acceso completo sin restricciones.

## 📦 Componentes

### 1. Middleware

#### `TerritoryScope`
Aplica automáticamente filtros de alcance territorial basándose en los territorios asignados.

**Uso:**
```php
// En routes/api.php
Route::middleware(['auth:sanctum', 'territory.scope'])->group(function () {
    Route::get('/simpatizantes', [SimpatizanteController::class, 'index']);
});
```

**Qué hace:**
- Calcula territorios accesibles (asignados + descendientes)
- Los agrega al request: `$request->_accessible_territory_ids`
- Los agrega al usuario: `$user->accessible_territory_ids`

#### `CampaignScope`
Aplica automáticamente filtros de alcance por campaña.

**Uso:**
```php
Route::middleware(['auth:sanctum', 'campaign.scope'])->group(function () {
    Route::get('/simpatizantes', [SimpatizanteController::class, 'index']);
});
```

**Qué hace:**
- Calcula campañas accesibles del usuario
- Las agrega al request: `$request->_accessible_campaign_ids`
- Las agrega al usuario: `$user->accessible_campaign_ids`

#### `AuditMiddleware`
Registra automáticamente todas las acciones sensibles en la tabla `auditoria`.

**Uso:**
```php
Route::middleware(['auth:sanctum', 'audit'])->group(function () {
    Route::post('/simpatizantes', [SimpatizanteController::class, 'store']);
    Route::put('/simpatizantes/{id}', [SimpatizanteController::class, 'update']);
    Route::delete('/simpatizantes/{id}', [SimpatizanteController::class, 'destroy']);
});
```

**Qué registra:**
- Usuario que ejecuta la acción
- Método HTTP y ruta
- IP del cliente y User-Agent
- Datos relevantes (sanitizados, sin campos sensibles)
- Tabla y registro afectados
- Resultado (exitoso/fallido) y código HTTP

**Rutas auditadas:**
- `/simpatizantes`
- `/usuarios`
- `/territorios`
- `/campanas`
- `/importaciones`
- `/exportaciones`
- `/lotes`
- `/2fa`

### 2. Métodos del Modelo User

```php
// Verificar acceso a territorio específico
$user->hasAccessToTerritorio('uuid-territorio');

// Verificar acceso a campaña específica
$user->hasAccessToCampana('uuid-campana');

// Obtener todos los territorios accesibles
$territorios = $user->getAccessibleTerritoryIds();

// Obtener todas las campañas accesibles
$campanas = $user->getAccessibleCampaignIds();
```

### 3. Scopes del Modelo Simpatizante

```php
// Filtrar por territorios accesibles del usuario
Simpatizante::accesiblesPorUsuario($user)->get();

// Filtrar por campañas del usuario
Simpatizante::enCampanasDeUsuario($user)->get();

// Aplicar ambos filtros (recomendado)
Simpatizante::accesiblePorUsuario($user)->get();

// Uso combinado con otros scopes
Simpatizante::accesiblePorUsuario($user)
    ->validados()
    ->where('estado', 'validado')
    ->get();
```

### 4. Trait `AppliesUserScope`

Trait reutilizable para aplicar filtros de alcance en controladores.

**Uso en controladores:**

```php
use App\Traits\AppliesUserScope;

class SimpatizanteController extends Controller
{
    use AppliesUserScope;

    public function index(Request $request)
    {
        $user = $request->user();
        
        // Método 1: Aplicar ambos filtros
        $query = $this->applyUserScope(
            Simpatizante::query(),
            $user
        );
        
        // Método 2: Solo filtro territorial
        $query = $this->applyTerritoryScope(
            Simpatizante::query(),
            $user
        );
        
        // Método 3: Solo filtro de campaña
        $query = $this->applyCampaignScope(
            Simpatizante::query(),
            $user
        );
        
        return $query->paginate(50);
    }
    
    public function show(Request $request, string $id)
    {
        $user = $request->user();
        $simpatizante = Simpatizante::findOrFail($id);
        
        // Verificar acceso
        if (!$this->userHasAccessToTerritory($user, $simpatizante->territorio_id)) {
            abort(403, 'No tiene acceso a este territorio');
        }
        
        if (!$this->userHasAccessToCampaign($user, $simpatizante->campana_id)) {
            abort(403, 'No tiene acceso a esta campaña');
        }
        
        return $simpatizante;
    }
}
```

**Métodos disponibles:**
- `applyTerritoryScope($query, $user, $territorioColumn = 'territorio_id')`
- `applyCampaignScope($query, $user, $campanaColumn = 'campana_id')`
- `applyUserScope($query, $user, $territorioColumn, $campanaColumn)`
- `userHasAccessToTerritory($user, $territorioId)`
- `userHasAccessToCampaign($user, $campanaId)`
- `getAccessibleTerritories($user)`
- `getAccessibleCampaigns($user)`

## 🔄 Flujo de Autorización

### Petición HTTP con Middleware

```
1. Usuario hace petición a /api/v1/simpatizantes
   ↓
2. Middleware auth:sanctum → autentica usuario
   ↓
3. Middleware territory.scope → calcula territorios accesibles
   ↓
4. Middleware campaign.scope → calcula campañas accesibles
   ↓
5. Controller usa scopes o trait para filtrar datos
   ↓
6. Middleware audit → registra acción (si es POST/PUT/DELETE)
   ↓
7. Respuesta JSON al cliente
```

### Cálculo de Territorios Accesibles

```
Usuario asignado a: Municipio Manizales (ID: abc-123)
   ↓
Función PostgreSQL: obtener_descendientes_territorio('abc-123')
   ↓
Retorna:
   - Municipio Manizales
   - Barrio Centro
   - Barrio La Enea
   - Vereda El Rosario
   - ... (todos los descendientes)
   ↓
Usuario accede a TODOS los simpatizantes en cualquiera de estos territorios
```

## 💡 Ejemplos de Uso

### Ejemplo 1: Listar Simpatizantes con Filtros

```php
public function index(Request $request)
{
    $user = $request->user();
    
    // Opción A: Usar scope del modelo (recomendado)
    $simpatizantes = Simpatizante::accesiblePorUsuario($user)
        ->with(['territorio', 'puesto', 'candidatos'])
        ->when($request->estado, fn($q, $estado) => $q->porEstado($estado))
        ->when($request->territorio_id, fn($q, $id) => $q->enTerritorio($id))
        ->orderBy('created_at', 'desc')
        ->paginate(50);
    
    return response()->json($simpatizantes);
}
```

### Ejemplo 2: Crear Simpatizante con Validación

```php
public function store(Request $request)
{
    $user = $request->user();
    
    $validated = $request->validate([
        'campana_id' => 'required|uuid|exists:campanas,id',
        'territorio_id' => 'required|uuid|exists:territorios,id',
        'nombres' => 'required|string|max:100',
        'cedula' => 'required|string|max:20',
        // ... otros campos
    ]);
    
    // Verificar que el usuario tenga acceso al territorio
    if (!$user->hasAccessToTerritorio($validated['territorio_id'])) {
        return response()->json([
            'message' => 'No tiene acceso a este territorio'
        ], 403);
    }
    
    // Verificar que el usuario tenga acceso a la campaña
    if (!$user->hasAccessToCampana($validated['campana_id'])) {
        return response()->json([
            'message' => 'No tiene acceso a esta campaña'
        ], 403);
    }
    
    // Cifrar campos sensibles (automático con mutators)
    $simpatizante = Simpatizante::create([
        ...$validated,
        'creado_por' => $user->id,
    ]);
    
    // La auditoría se registra automáticamente por AuditMiddleware
    
    return response()->json($simpatizante, 201);
}
```

### Ejemplo 3: Exportar Datos con Alcance

```php
public function export(Request $request)
{
    $user = $request->user();
    
    // Solo exporta datos accesibles por el usuario
    $simpatizantes = Simpatizante::accesiblePorUsuario($user)
        ->with(['territorio', 'puesto'])
        ->get();
    
    // Desencriptar campos para exportación
    $data = $simpatizantes->map(function ($s) {
        return [
            'nombres' => $s->nombres,
            'apellidos' => $s->apellidos,
            'cedula' => $s->cedula, // Desencriptado automáticamente
            'telefono' => $s->telefono,
            'territorio' => $s->territorio->nombre,
            'puesto' => $s->puesto?->nombre,
            'mesa' => $s->mesa,
        ];
    });
    
    // Crear exportación y registrar en auditoría
    $exportacion = Exportacion::create([
        'user_id' => $user->id,
        'tipo' => 'simpatizantes',
        'filtros' => $request->all(),
        'total_registros' => $data->count(),
    ]);
    
    return Excel::download(new SimpatizantesExport($data), 'simpatizantes.xlsx');
}
```

### Ejemplo 4: Estadísticas por Territorio

```php
public function estadisticas(Request $request)
{
    $user = $request->user();
    $territoriosAccesibles = $user->getAccessibleTerritoryIds();
    
    // Contar simpatizantes por territorio (solo accesibles)
    $conteos = DB::table('simpatizantes')
        ->join('territorios', 'simpatizantes.territorio_id', '=', 'territorios.id')
        ->whereIn('simpatizantes.territorio_id', $territoriosAccesibles)
        ->whereIn('simpatizantes.campana_id', $user->getAccessibleCampaignIds())
        ->select('territorios.nombre', DB::raw('count(*) as total'))
        ->groupBy('territorios.nombre')
        ->get();
    
    return response()->json($conteos);
}
```

## 🔒 Seguridad

### Validaciones Obligatorias

**Siempre verificar acceso antes de operaciones sensibles:**

```php
// ❌ MAL: No verificar acceso
$simpatizante = Simpatizante::findOrFail($id);
$simpatizante->update($request->all());

// ✅ BIEN: Verificar acceso
$simpatizante = Simpatizante::findOrFail($id);

if (!$user->hasAccessToTerritorio($simpatizante->territorio_id)) {
    abort(403, 'Acceso denegado');
}

$simpatizante->update($request->validated());
```

### Auditoría Automática

Todas las operaciones sensibles se registran automáticamente:

```sql
SELECT * FROM auditoria 
WHERE usuario_id = 'user-uuid' 
  AND tabla_afectada = 'simpatizantes'
  AND accion = 'simpatizante_actualizado'
ORDER BY created_at DESC;
```

### Campos Sanitizados en Auditoría

Los siguientes campos **nunca** se guardan en `datos_relevantes`:
- `password`, `password_confirmation`, `current_password`
- `two_factor_secret`, `recovery_codes`
- `token`, `api_token`, `remember_token`

Las cédulas se guardan parcialmente: `1234****`

## 🧪 Testing

### Probar Acceso Territorial

```php
public function test_user_can_only_access_assigned_territories()
{
    $municipio = Territorio::factory()->create(['tipo' => 'municipio']);
    $barrio = Territorio::factory()->create(['padre_id' => $municipio->id, 'tipo' => 'barrio']);
    
    $user = User::factory()->create();
    $user->territorios()->attach($municipio->id);
    
    $simpatizanteMunicipio = Simpatizante::factory()->create(['territorio_id' => $municipio->id]);
    $simpatizanteBarrio = Simpatizante::factory()->create(['territorio_id' => $barrio->id]);
    $simpatizanteOtro = Simpatizante::factory()->create();
    
    $accesibles = Simpatizante::accesiblesPorUsuario($user)->pluck('id');
    
    $this->assertTrue($accesibles->contains($simpatizanteMunicipio->id));
    $this->assertTrue($accesibles->contains($simpatizanteBarrio->id)); // Descendiente
    $this->assertFalse($accesibles->contains($simpatizanteOtro->id));
}
```

### Probar Auditoría

```php
public function test_sensitive_actions_are_audited()
{
    $user = User::factory()->create();
    $this->actingAs($user, 'sanctum');
    
    $response = $this->postJson('/api/v1/simpatizantes', [
        'campana_id' => $campana->id,
        'territorio_id' => $territorio->id,
        'nombres' => 'Juan',
        'apellidos' => 'Pérez',
        'cedula' => '1234567890',
    ]);
    
    $response->assertStatus(201);
    
    $this->assertDatabaseHas('auditoria', [
        'usuario_id' => $user->id,
        'accion' => 'simpatizante_creado',
        'tabla_afectada' => 'simpatizantes',
        'resultado' => 'exitoso',
    ]);
}
```

## 📊 Rendimiento

### Caché de Territorios Accesibles

Los territorios accesibles se calculan **una vez por petición** en el middleware y se reutilizan:

```php
// Primera llamada: calcula y cachea
$territorios = $user->getAccessibleTerritoryIds();

// Llamadas posteriores: usa cache del request
$territorios = $user->getAccessibleTerritoryIds(); // Instantáneo
```

### Optimización de Consultas

```php
// ✅ BIEN: Una consulta optimizada
$simpatizantes = Simpatizante::accesiblePorUsuario($user)
    ->with(['territorio', 'puesto']) // Eager loading
    ->whereIn('estado', ['validado', 'pendiente'])
    ->paginate(50);

// ❌ MAL: N+1 queries
$simpatizantes = Simpatizante::accesiblePorUsuario($user)->get();
foreach ($simpatizantes as $s) {
    echo $s->territorio->nombre; // Query adicional por cada simpatizante
}
```

## 🚨 Troubleshooting

### Usuario no ve ningún dato

**Verificar:**
1. ¿Tiene territorios asignados? `$user->territorios()->count()`
2. ¿Tiene campañas asignadas? `$user->campanas()->count()`
3. ¿Los territorios existen? `Territorio::whereIn('id', $user->territorios()->pluck('id'))->count()`

### Superadmin no bypasea restricciones

**Verificar:**
1. ¿Tiene el rol correcto? `$user->hasRole('Superadministrador')` (nota la mayúscula y tilde)
2. ¿El rol existe? `Role::where('name', 'Superadministrador')->exists()`

### Auditoría no registra acciones

**Verificar:**
1. ¿La ruta usa middleware `audit`?
2. ¿El método HTTP es POST/PUT/PATCH/DELETE?
3. ¿La ruta coincide con patrones auditables?
4. Ver logs: `tail -f storage/logs/laravel.log`

## 📚 Referencias

- **Políticas de Acceso:** `docs/ROLES_AND_PERMISSIONS.md`
- **Cifrado de Datos:** `docs/ENCRYPTION.md`
- **Autenticación 2FA:** `docs/TWO_FACTOR_AUTHENTICATION.md`
- **Función PostgreSQL:** `obtener_descendientes_territorio()` en `database/migrations/2024_01_01_000020_create_functions.php`
