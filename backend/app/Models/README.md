# Modelos Eloquent - Mapa Electoral Caldas

## Resumen

Todos los modelos implementados con relaciones completas, scopes útiles, casts, accessors/mutators y traits personalizados.

---

## Modelos Principales

### 1. **User** (Usuario)
- **Extends:** `Authenticatable`
- **Traits:** `HasApiTokens`, `HasFactory`, `Notifiable`, `SoftDeletes`, `HasRoles`
- **Características:**
  - Autenticación con Sanctum
  - Sistema de roles (Spatie Permission)
  - 2FA integrado (Google2FA)
  - Soft deletes
  - Control de bloqueo temporal
  - Registro de último acceso

**Relaciones:**
- `campanas()` - Muchos a muchos
- `territorios()` - Muchos a muchos
- `simpatizantesCreados()`, `simpatizantesActualizados()`, `simpatizantesLiderados()`
- `lotes()`, `formularios()`, `importaciones()`, `exportaciones()`
- `auditorias()`, `historialCambios()`

**Scopes:**
- `activos()` - Usuarios activos
- `conRol($rol)` - Por rol
- `enCampana($id)`, `enTerritorio($id)` - Filtros de alcance

**Accessors:**
- `nombre_completo` - Nombres + Apellidos

**Métodos:**
- `estaBloqueado()` - Verifica bloqueo temporal
- `requiere2FA()` - Verifica si tiene 2FA habilitado

---

### 2. **Campana** (Campaña Electoral)
- **Traits:** `HasFactory`, `SoftDeletes`
- **Características:**
  - Soft deletes
  - Control de vigencia por fechas
  - Estado activo/inactivo

**Relaciones:**
- `candidatos()` - Uno a muchos
- `simpatizantes()` - Uno a muchos
- `usuarios()` - Muchos a muchos
- `importaciones()` - Uno a muchos

**Scopes:**
- `activas()` - Campañas activas
- `vigentes()` - Activas y dentro del rango de fechas

---

### 3. **Territorio** (Jerarquía Territorial)
- **Traits:** `HasFactory`
- **Características:**
  - Auto-referencia (padre_id)
  - Soporte PostGIS (geometría, centroide)
  - Funciones recursivas PostgreSQL

**Relaciones:**
- `padre()` - Territorio padre
- `hijos()` - Territorios hijos
- `puestosVotacion()`, `candidatos()`, `simpatizantes()`, `lotes()`
- `usuarios()` - Muchos a muchos (alcance)

**Métodos Especiales:**
- `obtenerDescendientes()` - Usa función PostgreSQL
- `obtenerJerarquia()` - Jerarquía completa hacia arriba
- `obtenerIdsDescendientes()` - Array de IDs descendientes

**Accessors:**
- `geom_wkt` - Geometría en formato WKT
- `centroide_lat_lng` - Array ['lat' => x, 'lng' => y]

**Scopes:**
- `tipo($tipo)`, `departamentos()`, `municipios()`
- `hijosDe($id)`, `raices()` - Sin padre

---

### 4. **Candidato**
- **Traits:** `HasFactory`, `SoftDeletes`
- **Características:**
  - Vinculado a campaña y territorio
  - Color corporativo (hex)
  - Soft deletes

**Relaciones:**
- `campana()`, `territorio()`
- `simpatizantes()` - Muchos a muchos

**Scopes:**
- `activos()`, `porCargo($cargo)`, `enCampana($id)`
- `gobernacion()`, `alcaldia()`, `asamblea()`

---

### 5. **PuestoVotacion** (Puesto de Votación)
- **Traits:** `HasFactory`
- **Características:**
  - Ubicación PostGIS (Point)
  - Total de mesas

**Relaciones:**
- `territorio()`, `simpatizantes()`

**Accessors:**
- `ubicacion` - Array ['lat' => x, 'lng' => y]

**Métodos:**
- `setUbicacion($lat, $lng)` - Establecer coordenadas

**Scopes:**
- `enTerritorio($id)`, `conUbicacion()`, `sinUbicacion()`

---

### 6. **Simpatizante** ⭐ (Modelo Principal)
- **Traits:** `HasFactory`, `HasUuids`, `SoftDeletes`, `HasEncryptedFields`
- **Características:**
  - UUID como primary key
  - Campos cifrados (cédula, teléfono, correo, dirección)
  - Índice ciego para búsqueda de cédula
  - Soft deletes

**Campos Cifrados:**
- `cedula_cifrada` ↔ accessor `cedula`
- `telefono_cifrado` ↔ accessor `telefono`
- `correo_cifrado` ↔ accessor `correo`
- `direccion_cifrada` ↔ accessor `direccion`

**Relaciones:**
- `campana()`, `territorio()`, `puesto()`, `lider()`, `formulario()`, `lote()`
- `creadoPor()`, `actualizadoPor()`
- `candidatos()` - Muchos a muchos
- `historialCambios()` - Uno a muchos

**Accessors:**
- `nombre_completo` - Nombres + Apellidos
- Campos descifrados on-demand

**Mutators:**
- `setCedulaAttribute()` - Cifra y genera hash
- `setTelefonoAttribute()`, `setCorreoAttribute()`, `setDireccionAttribute()`

**Scopes:**
- `enCampana($id)`, `enTerritorio($id)`, `enTerritorios($ids)`
- `enPuesto($id)`, `enMesa($puestoId, $mesa)`
- `porEstado($estado)`, `validados()`, `borradores()`, `pendientesEvidencia()`
- `sinPuesto()`, `sinMesa()`, `sinFormulario()`
- `apoyaCandidato($id)`, `porLider($id)`, `enLote($id)`
- `buscarPorCedula($cedula)` - Búsqueda por índice ciego

---

### 7. **Lote** (Agrupación de Capturas)
- **Traits:** `HasFactory`

**Relaciones:**
- `territorio()`, `creadoPor()`, `simpatizantes()`

**Accessors:**
- `total_registros` - Count de simpatizantes

**Scopes:**
- `enTerritorio($id)`, `porFecha($fecha)`, `porUsuario($id)`

---

### 8. **Formulario** (Escaneos)
- **Traits:** `HasFactory`, `HasEncryptedFields`
- **Características:**
  - Ruta cifrada
  - Hash SHA256 para integridad
  - URL firmada temporal

**Campos Cifrados:**
- `archivo_ruta` ↔ accessor `ruta`

**Accessors:**
- `url_temporal` - URL firmada válida por 10 minutos

**Relaciones:**
- `subidoPor()`, `simpatizantes()`

**Scopes:**
- `porTipo($tipo)`, `porFecha($fecha)`, `subidoPor($id)`

---

### 9. **Auditoria**
- **Traits:** `HasFactory`
- **Características:**
  - Solo inserción (no updates)
  - Timestamp único: `creado_en`
  - Detalle en JSON

**Método Estático:**
- `registrar($accion, $entidad, $entidadId, $detalle)` - Registrar evento

**Relaciones:**
- `user()`

**Scopes:**
- `porUsuario($id)`, `porAccion($accion)`, `porEntidad($entidad, $id)`
- `entre($desde, $hasta)`, `porIp($ip)`

---

### 10. **Importacion**
- **Traits:** `HasFactory`
- **Características:**
  - Control de estado
  - Reversión transaccional
  - Reporte de errores

**Relaciones:**
- `campana()`, `creadoPor()`, `revertidoPor()`

**Accessors:**
- `porcentaje_exito` - % de filas OK

**Métodos:**
- `puedeRevertirse()` - Verificar si es reversible

**Scopes:**
- `enCampana($id)`, `porEstado($estado)`, `completadas()`, `reversibles()`

---

### 11. **Exportacion**
- **Traits:** `HasFactory`
- **Características:**
  - Control de descarga
  - Filtros en JSON

**Relaciones:**
- `user()`

**Métodos:**
- `marcarDescargado()` - Timestamp de descarga

**Scopes:**
- `porUsuario($id)`, `porTipo($tipo)`, `porEstado($estado)`
- `completadas()`, `pendientes()`

---

### 12. **SolicitudTitular** (RGPD/Habeas Data)
- **Traits:** `HasFactory`
- **Características:**
  - Cálculo automático de fecha límite (15 días hábiles)
  - Control de vencimiento

**Relaciones:**
- `simpatizante()`, `responsable()`

**Métodos:**
- `estaVencida()` - Verificar vencimiento

**Scopes:**
- `porTipo($tipo)`, `porEstado($estado)`, `pendientes()`, `vencidas()`

---

### 13. **HistorialCambio**
- **Traits:** `HasFactory`
- **Características:**
  - Solo inserción
  - Timestamp único: `creado_en`
  - Automático vía trigger PostgreSQL

**Relaciones:**
- `simpatizante()`, `user()`

**Scopes:**
- `porSimpatizante($id)`, `porUsuario($id)`, `porCampo($campo)`
- `entre($desde, $hasta)`

---

## Trait Personalizado: HasEncryptedFields

### Ubicación
`app/Traits/HasEncryptedFields.php`

### Funcionalidad
Cifrado transparente de campos sensibles usando AES-256-GCM.

### Métodos:
- `encryptField($value)` - Cifra un valor
- `decryptField($encrypted)` - Descifra un valor
- `generateBlindIndex($value)` - Genera hash HMAC-SHA256

### Uso en Modelos:
```php
use HasEncryptedFields;

protected $encrypted = [
    'cedula_cifrada',
    'telefono_cifrado',
];
```

### Boot Automático:
El trait intercepta el evento `saving` y cifra automáticamente los campos definidos en `$encrypted`.

---

## Convenciones de Código

### Nombres de Relaciones
- Singular para `belongsTo`: `territorio()`, `campana()`
- Plural para `hasMany`: `simpatizantes()`, `candidatos()`
- Descriptivo para múltiples del mismo tipo: `creadoPor()`, `actualizadoPor()`

### Scopes
- Prefijo con acción: `activos()`, `validados()`, `pendientes()`
- Prefijo `por` para filtros: `porEstado()`, `porFecha()`, `porUsuario()`
- Prefijo `en` para contexto: `enCampana()`, `enTerritorio()`
- Prefijo `sin` para ausencia: `sinPuesto()`, `sinFormulario()`

### Accessors
- Snake case: `nombre_completo`, `porcentaje_exito`, `url_temporal`

### Métodos de Instancia
- Camel case: `estaBloqueado()`, `puedeRevertirse()`, `estaVencida()`

---

## Índices y Optimizaciones

Todos los modelos tienen índices definidos en migraciones:
- Foreign keys indexadas
- Campos de búsqueda frecuente indexados
- Índices compuestos para consultas comunes
- Índices espaciales (GIST) en geometrías
- Unique constraints para prevenir duplicados

---

## Seguridad

### Cifrado
- AES-256-GCM para campos personales
- Clave separada de `APP_KEY`
- IV aleatorio por registro
- Tag de autenticación (AEAD)

### Índice Ciego
- HMAC-SHA256 con clave secreta
- Permite búsqueda exacta sin descifrar
- Previene rainbow tables

### Mass Assignment
- `$fillable` definido en todos los modelos
- Sin `$guarded = []`

### Soft Deletes
- Modelos principales usan soft deletes
- Auditoría y simpatizantes no se eliminan físicamente

---

## Comandos Útiles

```bash
# Listar todos los modelos
php artisan model:show User
php artisan model:show Simpatizante

# Generar modelo con migración y factory
php artisan make:model NuevoModelo -mf

# Limpiar caché de modelos
php artisan model:prune
```

---

Última actualización: 2024-01-01
