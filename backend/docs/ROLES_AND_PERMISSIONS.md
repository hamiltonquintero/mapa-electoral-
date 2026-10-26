# Sistema de Roles y Permisos - Mapa Electoral Caldas

## Resumen

El sistema implementa control de acceso basado en roles (RBAC) usando **Spatie Laravel Permission**. Cada usuario tiene uno o más roles, y cada rol tiene permisos específicos que determinan qué acciones puede realizar.

---

## Roles del Sistema

### 1. Superadministrador

**Descripción:** Control total del sistema. Responsable técnico y administrativo.

**Alcance:** Todo el departamento, todas las campañas

**Permisos:** TODOS (wildcard)

**Características:**
- Gestión completa de usuarios, roles y territorios
- Configuración del sistema
- Acceso a toda la información sin restricciones territoriales
- Puede eliminar registros
- 2FA obligatorio

**Casos de uso:**
- Administrador técnico del sistema
- Soporte y mantenimiento
- Configuración inicial
- Gestión de crisis

---

### 2. Administrador de Campaña

**Descripción:** Responsable de gestionar una campaña electoral específica.

**Alcance:** Toda su campaña

**Permisos:**
- `simpatizante.ver`, `simpatizante.crear`, `simpatizante.editar`, `simpatizante.exportar`
- `usuario.ver`, `usuario.crear`, `usuario.editar`
- `campana.ver`
- `catalogo.ver`
- `importacion.realizar`, `importacion.revertir`
- `auditoria.ver`
- `reporte.ver`, `reporte.exportar`
- `formulario.subir`, `formulario.ver`
- `lote.crear`, `lote.ver`

**Características:**
- Puede crear y gestionar usuarios de su campaña
- Puede ver reportes agregados y detallados
- Puede exportar datos (con auditoría)
- Puede importar y revertir importaciones
- 2FA obligatorio

**Restricciones:**
- No puede editar otros administradores o superadmins
- Solo ve datos de su campaña
- No puede eliminar registros (solo marcar como retirados)

---

### 3. Coordinador Municipal

**Descripción:** Responsable de uno o más municipios dentro de una campaña.

**Alcance:** Sus municipios asignados

**Permisos:**
- `simpatizante.ver`, `simpatizante.crear`, `simpatizante.editar`
- `territorio.ver`
- `reporte.ver`
- `formulario.subir`, `formulario.ver`
- `lote.crear`, `lote.ver`

**Características:**
- Gestiona actividades en sus municipios
- Puede ver y editar simpatizantes de su territorio
- Puede asignar líderes de zona
- Puede ver reportes de su territorio

**Restricciones:**
- Solo ve datos de sus municipios (y descendientes: veredas, barrios)
- No puede exportar datos
- No puede gestionar usuarios
- 2FA opcional (recomendado)

---

### 4. Líder de Zona

**Descripción:** Responsable de una vereda, barrio o corregimiento específico.

**Alcance:** Sus zonas asignadas

**Permisos:**
- `simpatizante.ver`, `simpatizante.crear`, `simpatizante.editar`
- `territorio.ver`
- `formulario.subir`, `formulario.ver`
- `lote.crear`, `lote.ver`

**Características:**
- Captura información en campo
- Gestiona simpatizantes de su zona
- Sube formularios físicos escaneados
- Organiza capturas por lotes

**Restricciones:**
- Solo ve su zona asignada
- No ve reportes agregados
- No puede exportar
- 2FA opcional

---

### 5. Digitador

**Descripción:** Usuario dedicado exclusivamente a capturar formularios físicos.

**Alcance:** Solo lo que captura (su lote activo)

**Permisos:**
- `simpatizante.crear`
- `formulario.subir`
- `lote.crear`, `lote.ver`

**Características:**
- Acceso mínimo para captura rápida
- Solo puede crear, no ver listados
- Trabaja por lotes de captura
- No tiene navegación del mapa

**Restricciones:**
- No puede ver simpatizantes existentes
- No puede editar
- No puede ver reportes
- No tiene acceso a exportaciones
- 2FA opcional

---

### 6. Auditor

**Descripción:** Solo lectura para revisión de calidad y trazabilidad.

**Alcance:** Según asignación territorial

**Permisos:**
- `simpatizante.ver`
- `territorio.ver`
- `catalogo.ver`
- `auditoria.ver`
- `reporte.ver`
- `formulario.ver`
- `lote.ver`

**Características:**
- Solo lectura en todo el sistema
- Puede ver logs de auditoría
- Puede revisar calidad de datos
- Puede ver formularios escaneados

**Restricciones:**
- No puede crear, editar ni eliminar nada
- No puede exportar datos
- 2FA opcional

---

## Permisos Disponibles

### Simpatizantes
- `simpatizante.ver` - Ver listados y detalle
- `simpatizante.crear` - Crear nuevos registros
- `simpatizante.editar` - Modificar existentes
- `simpatizante.eliminar` - Eliminar (supresión)
- `simpatizante.exportar` - Exportar datos

### Territorios
- `territorio.administrar` - CRUD de territorios
- `territorio.ver` - Ver territorios

### Usuarios
- `usuario.administrar` - Gestión completa
- `usuario.ver` - Ver usuarios
- `usuario.crear` - Crear usuarios
- `usuario.editar` - Editar usuarios
- `usuario.eliminar` - Eliminar usuarios

### Campañas
- `campana.administrar` - CRUD de campañas
- `campana.ver` - Ver campañas

### Catálogos
- `catalogo.administrar` - Gestionar catálogos (candidatos, puestos)
- `catalogo.ver` - Ver catálogos

### Importación
- `importacion.realizar` - Importar archivos
- `importacion.revertir` - Revertir importaciones

### Auditoría
- `auditoria.ver` - Ver logs de auditoría
- `auditoria.exportar` - Exportar logs de auditoría a CSV
- `auditoria.gestionar` - Gestión completa (incluye limpieza de logs antiguos)

### Reportes
- `reporte.ver` - Ver reportes
- `reporte.exportar` - Exportar reportes

### Formularios
- `formulario.subir` - Subir escaneos
- `formulario.ver` - Ver formularios

### Lotes
- `lote.crear` - Crear lotes de captura
- `lote.ver` - Ver lotes

### Sistema
- `sistema.administrar` - Administración general
- `sistema.configurar` - Configuración avanzada

---

## Políticas de Autorización

### SimpatizantePolicy

**Reglas de acceso:**

1. **Ver cualquier** (`viewAny`):
   - Requiere permiso `simpatizante.ver`

2. **Ver uno específico** (`view`):
   - Requiere permiso `simpatizante.ver`
   - Debe estar en una campaña del usuario
   - Debe estar en territorio del usuario (o descendiente)

3. **Crear** (`create`):
   - Requiere permiso `simpatizante.crear`

4. **Actualizar** (`update`):
   - Requiere permiso `simpatizante.editar`
   - Debe estar en campaña del usuario
   - Debe estar en territorio del usuario

5. **Eliminar** (`delete`):
   - Requiere permiso `simpatizante.eliminar`
   - Solo Superadministrador

6. **Exportar** (`export`):
   - Requiere permiso `simpatizante.exportar`

---

### UserPolicy

**Reglas de acceso:**

1. **Ver** (`view`):
   - Superadmin: todos
   - Admin Campaña: usuarios de sus campañas
   - Otros: solo a sí mismos

2. **Crear** (`create`):
   - Requiere permiso `usuario.crear`

3. **Actualizar** (`update`):
   - Superadmin: todos
   - Admin Campaña: usuarios de su campaña (excepto otros admins)
   - Otros: solo a sí mismos

4. **Eliminar** (`delete`):
   - Solo Superadmin
   - No puede eliminarse a sí mismo

5. **Asignar rol** (`assignRole`):
   - Solo Superadmin
   - No puede cambiar su propio rol

---

### TerritorioPolicy

**Reglas de acceso:**

1. **Ver** (`view`):
   - Superadmin: todos
   - Otros: solo territorios asignados y descendientes

2. **Administrar** (`create`, `update`, `delete`):
   - Requiere permiso `territorio.administrar`

---

### CampanaPolicy

**Reglas de acceso:**

1. **Ver** (`view`):
   - Superadmin: todas
   - Otros: solo campañas a las que pertenecen

2. **Administrar** (`create`, `update`, `delete`):
   - Requiere permiso `campana.administrar`

---

## Comandos Artisan

### Inicializar Roles y Permisos

```bash
# Ejecutar seeder (primera vez o para resetear)
php artisan db:seed --class=RolePermissionSeeder
```

### Crear Usuario

```bash
# Interactivo
php artisan mapa:create-user

# Con parámetros
php artisan mapa:create-user \
  --email="admin@example.com" \
  --password="securepass" \
  --nombres="Juan" \
  --apellidos="Pérez" \
  --role="Superadministrador"
```

### Asignar Rol

```bash
# Asignar rol a usuario existente
php artisan mapa:assign-role user@example.com "Coordinador Municipal"

# Remover rol
php artisan mapa:assign-role user@example.com "Digitador" --remove
```

### Listar Permisos

```bash
# Ver todos los roles y permisos
php artisan mapa:list-permissions

# Ver permisos de un rol específico
php artisan mapa:list-permissions --role="Líder de Zona"
```

---

## Uso en Código

### En Controladores

```php
// Verificar permiso
if (!auth()->user()->can('simpatizante.crear')) {
    abort(403, 'No autorizado');
}

// Verificar rol
if (!auth()->user()->hasRole('Superadministrador')) {
    abort(403, 'Solo superadministradores');
}

// Usar política
$this->authorize('view', $simpatizante);
$this->authorize('update', $simpatizante);
```

### En Rutas

```php
// Por permiso
Route::middleware('permission:simpatizante.exportar')->group(function () {
    Route::post('/exportar', [Controller::class, 'export']);
});

// Por rol
Route::middleware('role:Superadministrador')->group(function () {
    Route::resource('usuarios', UserController::class);
});

// Rol O permiso
Route::middleware('role_or_permission:Superadministrador|simpatizante.ver')
    ->get('/simpatizantes', [Controller::class, 'index']);
```

### En Blade (Futuro Frontend)

```blade
@can('simpatizante.crear')
    <button>Crear Simpatizante</button>
@endcan

@role('Superadministrador')
    <a href="/admin">Panel Admin</a>
@endrole

@hasanyrole('Superadministrador|Administrador de Campaña')
    <a href="/reportes">Reportes</a>
@endhasanyrole
```

### En Modelos (Global Scopes)

```php
// Aplicar scope automático por territorio
protected static function booted()
{
    static::addGlobalScope('territory', function (Builder $builder) {
        $user = auth()->user();
        
        if (!$user || $user->hasRole('Superadministrador')) {
            return;
        }

        $territorioIds = $user->territorios->flatMap(fn($t) => 
            $t->obtenerIdsDescendientes()
        )->toArray();

        $builder->whereIn('territorio_id', $territorioIds);
    });
}
```

---

## Matriz de Permisos

| Permiso | Super | Admin Camp | Coord Mun | Líder Zona | Digitador | Auditor |
|---------|-------|------------|-----------|------------|-----------|---------|
| simpatizante.ver | ✓ | ✓ | ✓ | ✓ | ✗ | ✓ |
| simpatizante.crear | ✓ | ✓ | ✓ | ✓ | ✓ | ✗ |
| simpatizante.editar | ✓ | ✓ | ✓ | ✓ | ✗ | ✗ |
| simpatizante.eliminar | ✓ | ✗ | ✗ | ✗ | ✗ | ✗ |
| simpatizante.exportar | ✓ | ✓ | ✗ | ✗ | ✗ | ✗ |
| usuario.administrar | ✓ | ✗ | ✗ | ✗ | ✗ | ✗ |
| usuario.ver | ✓ | ✓ | ✗ | ✗ | ✗ | ✗ |
| usuario.crear | ✓ | ✓ | ✗ | ✗ | ✗ | ✗ |
| territorio.administrar | ✓ | ✗ | ✗ | ✗ | ✗ | ✗ |
| territorio.ver | ✓ | ✓ | ✓ | ✓ | ✗ | ✓ |
| campana.administrar | ✓ | ✗ | ✗ | ✗ | ✗ | ✗ |
| campana.ver | ✓ | ✓ | ✗ | ✗ | ✗ | ✗ |
| importacion.realizar | ✓ | ✓ | ✗ | ✗ | ✗ | ✗ |
| auditoria.ver | ✓ | ✓ | ✗ | ✗ | ✗ | ✓ |
| auditoria.exportar | ✓ | ✓ | ✗ | ✗ | ✗ | ✗ |
| auditoria.gestionar | ✓ | ✗ | ✗ | ✗ | ✗ | ✗ |
| reporte.ver | ✓ | ✓ | ✓ | ✗ | ✗ | ✓ |
| reporte.exportar | ✓ | ✓ | ✗ | ✗ | ✗ | ✗ |
| formulario.subir | ✓ | ✓ | ✓ | ✓ | ✓ | ✗ |
| sistema.administrar | ✓ | ✗ | ✗ | ✗ | ✗ | ✗ |

---

## Flujo de Asignación de Usuarios

### 1. Creación de Usuario

```
Superadministrador → Crea usuario
                   → Asigna rol
                   → Asigna campaña(s)
                   → Asigna territorio(s)
```

### 2. Asignación de Territorio

**Regla:** El territorio asignado incluye automáticamente todos sus descendientes.

Ejemplo:
```
Usuario asignado a: Manizales (municipio)
Acceso automático a:
  - Todas las veredas de Manizales
  - Todos los barrios de Manizales
  - Todos los corregimientos de Manizales
```

### 3. Asignación de Campaña

Un usuario puede pertenecer a múltiples campañas, pero solo ve datos de las campañas asignadas.

---

## Seguridad y Buenas Prácticas

### 1. Principio de Mínimo Privilegio

- Asignar solo los permisos necesarios
- Revisar permisos periódicamente
- Remover accesos cuando ya no son necesarios

### 2. Separación de Responsabilidades

- No mezclar roles (ej: un Digitador no debe ser Coordinador)
- Un rol por contexto de trabajo
- Si necesita más permisos, cambiar de rol

### 3. Auditoría de Cambios de Roles

Todos los cambios de roles quedan registrados en `auditoria`:

```php
Auditoria::registrar('rol_asignado', 'usuario', $userId, [
    'rol' => $roleName,
    'asignado_por' => auth()->id(),
]);
```

### 4. Revisión de Accesos

```bash
# Usuarios con roles críticos
php artisan mapa:list-permissions --role="Superadministrador"

# Verificar usuarios sin roles
User::doesntHave('roles')->get();

# Verificar permisos de un usuario
$user->getAllPermissions();
```

---

## Troubleshooting

### Usuario no puede acceder aunque tiene el rol

1. **Verificar permiso está en el rol:**
   ```bash
   php artisan mapa:list-permissions --role="Nombre Rol"
   ```

2. **Limpiar caché de permisos:**
   ```bash
   php artisan permission:cache-reset
   ```

3. **Verificar alcance territorial:**
   ```php
   $user->territorios; // Debe tener territorios asignados
   ```

### Política no se aplica

1. **Verificar registro en AuthServiceProvider:**
   ```php
   protected $policies = [
       Simpatizante::class => SimpatizantePolicy::class,
   ];
   ```

2. **Limpiar caché:**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

### Cambios en permisos no se reflejan

```bash
# Limpiar caché de permisos
php artisan permission:cache-reset

# Reiniciar aplicación
php artisan optimize:clear
```

---

## Migración de Usuarios Existentes

```bash
# 1. Crear roles y permisos
php artisan db:seed --class=RolePermissionSeeder

# 2. Asignar roles masivamente (script personalizado)
# 3. Asignar territorios
# 4. Asignar campañas
# 5. Verificar accesos
```

---

## Referencias

- [Spatie Laravel Permission](https://spatie.be/docs/laravel-permission)
- [Laravel Authorization](https://laravel.com/docs/11.x/authorization)
- [RBAC Best Practices](https://en.wikipedia.org/wiki/Role-based_access_control)

---

Última actualización: 2024-01-01
