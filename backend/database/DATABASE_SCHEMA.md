# Esquema de Base de Datos - Mapa Electoral Caldas

## Resumen

Base de datos PostgreSQL 16 con extensión PostGIS para datos geoespaciales.

**Total de Tablas:** 22
**Vistas Materializadas:** 3
**Funciones Personalizadas:** 4
**Triggers:** 1

---

## Tablas Principales

### 1. **campanas**
Campañas electorales activas en el sistema.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | PK |
| nombre | string | Nombre de la campaña |
| descripcion | text | Descripción |
| activa | boolean | Si está activa |
| fecha_inicio | date | Fecha de inicio |
| fecha_cierre | date | Fecha de cierre |
| timestamps | - | created_at, updated_at |
| soft_deletes | - | deleted_at |

**Índices:** activa

---

### 2. **territorios**
Jerarquía territorial: Departamento → Municipio → Vereda/Barrio/Corregimiento

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | PK |
| padre_id | bigint | FK a territorios (auto-referencia) |
| tipo | enum | departamento, municipio, corregimiento, vereda, barrio |
| nombre | string | Nombre del territorio |
| codigo_divipola | string(10) | Código DANE |
| geom | geometry(MultiPolygon) | Geometría PostGIS (SRID 4326) |
| centroide | geometry(Point) | Punto central |
| timestamps | - | created_at, updated_at |

**Índices:** padre_id, tipo, [padre_id, tipo], codigo_divipola (unique), geom (GIST), centroide (GIST)

---

### 3. **candidatos**
Candidatos a cargos de elección popular

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | PK |
| campana_id | bigint | FK a campanas |
| nombre | string | Nombre completo |
| cargo | enum | gobernacion, alcaldia, asamblea |
| territorio_id | bigint | FK a territorios (municipio si aplica) |
| color | string(7) | Color corporativo (hex) |
| descripcion | text | Descripción |
| foto | string | Ruta de foto |
| activo | boolean | Si está activo |
| timestamps | - | created_at, updated_at |
| soft_deletes | - | deleted_at |

**Índices:** [campana_id, cargo], activo

---

### 4. **puestos_votacion**
Puestos de votación con ubicación geográfica

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | PK |
| territorio_id | bigint | FK a territorios |
| nombre | string | Nombre del puesto |
| direccion | string | Dirección |
| geom | geometry(Point) | Ubicación (SRID 4326) |
| total_mesas | integer | Número de mesas |
| codigo_registraduria | string(20) | Código oficial |
| timestamps | - | created_at, updated_at |

**Índices:** territorio_id, codigo_registraduria, geom (GIST)

---

### 5. **users**
Usuarios del sistema con autenticación y 2FA

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | PK |
| nombres | string | Nombres |
| apellidos | string | Apellidos |
| email | string | Email (unique) |
| email_verified_at | timestamp | Verificación de email |
| password | string | Hash de contraseña (Argon2id) |
| google2fa_enabled | boolean | Si tiene 2FA habilitado |
| google2fa_secret | text | Secreto 2FA cifrado |
| google2fa_verified_at | timestamp | Fecha de verificación 2FA |
| activo | boolean | Usuario activo |
| intentos_fallidos | integer | Intentos de login fallidos |
| bloqueado_hasta | timestamp | Bloqueo temporal |
| ultimo_acceso | timestamp | Último login |
| ultima_ip | string | Última IP |
| timestamps | - | created_at, updated_at |
| soft_deletes | - | deleted_at |

**Índices:** email, activo

**Relacionadas:** password_reset_tokens, sessions

---

### 6. **roles** y **permissions** (Spatie Permission)
Sistema de roles y permisos

Tablas del paquete Spatie Laravel Permission:
- roles
- permissions
- model_has_roles
- model_has_permissions
- role_has_permissions

---

### 7. **usuario_campana**
Relación muchos a muchos: usuarios ↔ campañas

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | PK |
| user_id | bigint | FK a users |
| campana_id | bigint | FK a campanas |
| timestamps | - | created_at, updated_at |

**Índices:** [user_id, campana_id] (unique), campana_id

---

### 8. **usuario_territorio**
Relación muchos a muchos: usuarios ↔ territorios (alcance)

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | PK |
| user_id | bigint | FK a users |
| territorio_id | bigint | FK a territorios |
| timestamps | - | created_at, updated_at |

**Índices:** [user_id, territorio_id] (unique), territorio_id

---

### 9. **lotes**
Agrupación de registros por sesión de captura

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | PK |
| descripcion | string | Descripción del lote |
| territorio_id | bigint | FK a territorios |
| fecha | date | Fecha del lote |
| creado_por | bigint | FK a users |
| observaciones | text | Observaciones |
| timestamps | - | created_at, updated_at |

**Índices:** creado_por, fecha, territorio_id

---

### 10. **formularios**
Escaneos de formularios físicos firmados

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | PK |
| consecutivo | string(50) | Número consecutivo (unique) |
| archivo_ruta | text | Ruta cifrada del archivo |
| hash_sha256 | string(64) | Hash del archivo |
| fecha_firma | date | Fecha de firma |
| tipo_archivo | string(10) | jpg, png, pdf |
| tamano_bytes | integer | Tamaño del archivo |
| subido_por | bigint | FK a users |
| timestamps | - | created_at, updated_at |

**Índices:** consecutivo, hash_sha256, subido_por, fecha_firma

---

### 11. **simpatizantes** ⭐
Tabla principal: personas que apoyan candidatos

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | uuid | PK |
| campana_id | bigint | FK a campanas |
| nombres | string | Nombres |
| apellidos | string | Apellidos |
| cedula_cifrada | text | Cédula cifrada (AES-256-GCM) |
| cedula_hash | string(64) | Hash ciego HMAC-SHA256 |
| telefono_cifrado | text | Teléfono cifrado |
| correo_cifrado | text | Correo cifrado |
| direccion_cifrada | text | Dirección cifrada |
| territorio_id | bigint | FK a territorios |
| puesto_id | bigint | FK a puestos_votacion |
| mesa | integer | Número de mesa |
| lider_id | bigint | FK a users (líder responsable) |
| formulario_id | bigint | FK a formularios |
| lote_id | bigint | FK a lotes |
| estado | enum | borrador, validado, observado, retirado, pendiente_evidencia |
| observaciones | text | Observaciones |
| creado_por | bigint | FK a users |
| actualizado_por | bigint | FK a users |
| timestamps | - | created_at, updated_at |
| soft_deletes | - | deleted_at |

**Índices:** [campana_id, cedula_hash] (unique), campana_id, territorio_id, puesto_id, estado, lider_id, creado_por, [puesto_id, mesa], created_at

---

### 12. **simpatizante_candidato**
Relación muchos a muchos: simpatizantes ↔ candidatos

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | PK |
| simpatizante_id | uuid | FK a simpatizantes |
| candidato_id | bigint | FK a candidatos |
| timestamps | - | created_at, updated_at |

**Índices:** [simpatizante_id, candidato_id] (unique), candidato_id

---

### 13. **auditoria**
Registro de todas las acciones sensibles (solo inserción)

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | PK |
| user_id | bigint | FK a users |
| rol | string | Rol del usuario al momento |
| accion | string | login, logout, crear, editar, eliminar, exportar, etc. |
| entidad | string | simpatizante, usuario, territorio, etc. |
| entidad_id | string | ID del registro afectado |
| ip | string(45) | Dirección IP |
| user_agent | text | User agent |
| detalle | json | Info adicional SIN datos personales |
| creado_en | timestamp | Timestamp de la acción |

**Índices:** user_id, accion, entidad, [entidad, entidad_id], creado_en, ip

---

### 14. **importaciones**
Control de importaciones masivas

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | PK |
| campana_id | bigint | FK a campanas |
| archivo_original | string | Nombre original |
| archivo_ruta | string | Ruta del archivo |
| estado | enum | pendiente, validando, validado, importando, completado, error, revertido |
| filas_total | integer | Total de filas |
| filas_ok | integer | Filas importadas OK |
| filas_error | integer | Filas con error |
| errores | json | Detalle de errores |
| reversible | boolean | Si se puede revertir |
| reversible_hasta | timestamp | Fecha límite reversión |
| revertido_en | timestamp | Fecha de reversión |
| revertido_por | bigint | FK a users |
| creado_por | bigint | FK a users |
| timestamps | - | created_at, updated_at |

**Índices:** campana_id, estado, creado_por, created_at

---

### 15. **solicitudes_titular**
Solicitudes de titulares (consulta, rectificación, supresión)

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | PK |
| tipo | enum | consulta, rectificacion, supresion |
| simpatizante_id | uuid | FK a simpatizantes |
| cedula_hash | text | Hash de cédula si ya no existe |
| estado | enum | recibida, en_revision, resuelta, rechazada |
| descripcion | text | Descripción |
| fecha_recepcion | date | Fecha de recepción |
| fecha_limite | date | Fecha límite legal (15 días) |
| resuelta_en | timestamp | Fecha de resolución |
| notas | text | Notas internas |
| responsable_id | bigint | FK a users |
| timestamps | - | created_at, updated_at |

**Índices:** tipo, estado, fecha_limite, responsable_id

---

### 16. **exportaciones**
Control de exportaciones de datos

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | PK |
| user_id | bigint | FK a users |
| tipo | enum | datos_personales, agregado, reporte |
| motivo | string | Motivo obligatorio |
| filtros | json | Filtros aplicados |
| total_registros | integer | Total exportado |
| archivo_ruta | string | Ruta del archivo generado |
| estado | enum | solicitado, procesando, completado, error |
| error_mensaje | text | Mensaje de error |
| descargado_en | timestamp | Fecha de descarga |
| timestamps | - | created_at, updated_at |

**Índices:** user_id, tipo, estado, created_at

---

### 17. **historial_cambios**
Historial de cambios en simpatizantes

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | PK |
| simpatizante_id | uuid | FK a simpatizantes |
| user_id | bigint | FK a users |
| campo | string | Campo modificado |
| valor_anterior | text | Valor anterior |
| valor_nuevo | text | Valor nuevo |
| creado_en | timestamp | Timestamp del cambio |

**Índices:** simpatizante_id, user_id, creado_en

---

### Tablas de Sistema Laravel

18. **cache** y **cache_locks**
19. **jobs**, **job_batches**, **failed_jobs**
20. **personal_access_tokens** (Sanctum)

---

## Vistas Materializadas

### 1. **mv_conteo_territorio**
Conteos agregados por territorio y candidato

```sql
SELECT 
    campana_id,
    candidato_id,
    territorio_id,
    COUNT(*) as total,
    COUNT(CASE WHEN estado = 'validado' ...) as validados,
    ...
GROUP BY campana_id, candidato_id, territorio_id
```

**Índices:** [campana_id, candidato_id, territorio_id] (unique), campana_id, territorio_id

**Refresh:** Cada 5 minutos (configurable)

---

### 2. **mv_conteo_puesto_mesa**
Conteos por puesto de votación y mesa

```sql
SELECT 
    campana_id,
    candidato_id,
    puesto_id,
    mesa,
    territorio_id,
    COUNT(*) as total,
    ...
GROUP BY campana_id, candidato_id, puesto_id, mesa, territorio_id
```

**Índices:** [campana_id, candidato_id, puesto_id, COALESCE(mesa, 0)] (unique), campana_id, puesto_id

---

### 3. **mv_estadisticas_generales**
Estadísticas generales por campaña y candidato

```sql
SELECT 
    campana_id,
    candidato_id,
    COUNT(*) as total_simpatizantes,
    COUNT(DISTINCT territorio_id) as total_territorios,
    COUNT(DISTINCT puesto_id) as total_puestos,
    ...
GROUP BY campana_id, candidato_id
```

**Índices:** [campana_id, candidato_id] (unique)

---

## Funciones Personalizadas

### 1. **obtener_descendientes_territorio(territorio_padre_id)**
Devuelve todos los territorios descendientes de un territorio dado.

```sql
SELECT * FROM obtener_descendientes_territorio(1);
```

Retorna: id, padre_id, tipo, nombre, nivel

---

### 2. **obtener_jerarquia_territorio(territorio_id)**
Devuelve la jerarquía completa (hacia arriba) de un territorio.

```sql
SELECT * FROM obtener_jerarquia_territorio(25);
```

Retorna: departamento → municipio → vereda

---

### 3. **refresh_all_materialized_views()**
Refresca todas las vistas materializadas de forma concurrente.

```sql
SELECT refresh_all_materialized_views();
```

Se ejecuta automáticamente cada 5 minutos vía scheduler.

---

### 4. **registrar_cambio_simpatizante()** (TRIGGER)
Trigger automático que registra en `historial_cambios` los cambios en campos importantes de `simpatizantes`.

Campos auditados:
- nombres
- apellidos
- territorio_id
- puesto_id
- mesa
- estado

---

## Diagrama de Relaciones (Simplificado)

```
campanas
    ├─► candidatos
    │       └─► simpatizante_candidato ◄─┐
    ├─► usuario_campana                  │
    ├─► simpatizantes ───────────────────┘
    └─► importaciones

territorios (auto-referencia padre_id)
    ├─► puestos_votacion
    ├─► usuario_territorio
    ├─► simpatizantes
    ├─► candidatos
    └─► lotes

users
    ├─► model_has_roles
    ├─► usuario_campana
    ├─► usuario_territorio
    ├─► simpatizantes (creado_por, actualizado_por, lider_id)
    ├─► lotes (creado_por)
    ├─► formularios (subido_por)
    ├─► importaciones (creado_por, revertido_por)
    ├─► exportaciones
    ├─► auditoria
    └─► historial_cambios

simpatizantes (NÚCLEO)
    ├─► simpatizante_candidato
    ├─► historial_cambios
    └─► solicitudes_titular

formularios
    └─► simpatizantes

lotes
    └─► simpatizantes
```

---

## Seguridad

### Cifrado de Campos

Campos cifrados con AES-256-GCM:
- `simpatizantes.cedula_cifrada`
- `simpatizantes.telefono_cifrado`
- `simpatizantes.correo_cifrado`
- `simpatizantes.direccion_cifrada`
- `formularios.archivo_ruta`
- `users.google2fa_secret`

### Índice Ciego

- `simpatizantes.cedula_hash`: HMAC-SHA256 para búsqueda sin descifrar

### Auditoría

Todas las operaciones sensibles se registran en `auditoria` sin incluir datos personales en claro.

---

## Comandos Útiles

```bash
# Ejecutar migraciones
php artisan migrate

# Revertir última migración
php artisan migrate:rollback

# Refrescar vistas materializadas
php artisan mapa:refresh-views

# Verificar estado de migraciones
php artisan migrate:status
```

---

## Notas de Rendimiento

1. **Índices espaciales (GIST)** en todas las columnas de geometría
2. **Vistas materializadas** para conteos (refresh cada 5 min)
3. **Índices compuestos** en consultas frecuentes
4. **Particionado** de `auditoria` (opcional, para bases muy grandes)
5. **Unique constraints** para prevenir duplicados
6. **Foreign keys con ON DELETE CASCADE/SET NULL** según lógica de negocio

---

Última actualización: 2024-01-01
