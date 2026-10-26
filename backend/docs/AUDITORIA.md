# Módulo de Auditoría - Sistema Mapa Electoral Caldas

## Descripción general

El módulo de auditoría registra **todos los eventos sensibles** del sistema para garantizar trazabilidad completa, cumplimiento legal y detección de actividad sospechosa.

Características principales:
- ✅ Registro automático de eventos mediante middleware
- ✅ Consulta avanzada con 12+ filtros
- ✅ Dashboard con métricas y estadísticas
- ✅ Alertas de seguridad en tiempo real
- ✅ Exportación a CSV con control de acceso
- ✅ Historial por usuario y entidad
- ✅ Limpieza controlada de logs antiguos
- ✅ Sin datos personales en claro (compliance GDPR/LOPD)

---

## Tabla de auditoría

### Estructura

```sql
CREATE TABLE auditoria (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT REFERENCES users(id),
    rol VARCHAR(100),
    accion VARCHAR(100) NOT NULL,
    entidad VARCHAR(100),
    entidad_id VARCHAR(100),
    ip INET,
    user_agent TEXT,
    detalle JSONB,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Índices para rendimiento
CREATE INDEX idx_auditoria_user_id ON auditoria(user_id);
CREATE INDEX idx_auditoria_accion ON auditoria(accion);
CREATE INDEX idx_auditoria_entidad ON auditoria(entidad, entidad_id);
CREATE INDEX idx_auditoria_creado_en ON auditoria(creado_en);
CREATE INDEX idx_auditoria_ip ON auditoria(ip);
CREATE INDEX idx_auditoria_detalle ON auditoria USING gin(detalle);
```

### Campos

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | BIGINT | ID único del evento |
| `user_id` | BIGINT | ID del usuario que ejecutó la acción (puede ser NULL para eventos del sistema) |
| `rol` | VARCHAR | Rol del usuario en el momento de la acción |
| `accion` | VARCHAR | Tipo de acción ejecutada (ver lista abajo) |
| `entidad` | VARCHAR | Nombre de la tabla/recurso afectado |
| `entidad_id` | VARCHAR | ID del registro afectado (puede ser UUID) |
| `ip` | INET | Dirección IP del cliente |
| `user_agent` | TEXT | User-Agent del navegador |
| `detalle` | JSONB | Información adicional relevante (sin datos personales en claro) |
| `creado_en` | TIMESTAMP | Fecha y hora del evento (UTC) |

---

## Eventos auditados

### Autenticación y seguridad
- `login_exitoso` - Inicio de sesión correcto
- `login_fallido` - Intento de login fallido
- `logout` - Cierre de sesión
- `2fa_habilitado` - Activación de 2FA
- `2fa_deshabilitado` - Desactivación de 2FA
- `2fa_verificado` - Verificación exitosa de código 2FA

### Simpatizantes
- `simpatizante_creado` - Nuevo registro creado
- `simpatizante_actualizado` - Registro modificado
- `simpatizante_eliminado` - Supresión definitiva
- `simpatizante_consultado` - Visualización de detalle (PII)
- `simpatizante_estado_cambiado` - Cambio de estado (borrador → validado, etc.)
- `simpatizante_puesto_asignado` - Asignación de puesto de votación
- `simpatizante_lider_asignado` - Asignación de líder responsable

### Usuarios y permisos
- `usuario_creado` - Nuevo usuario registrado
- `usuario_actualizado` - Datos de usuario modificados
- `usuario_eliminado` - Usuario desactivado/eliminado
- `rol_asignado` - Asignación de rol
- `rol_removido` - Remoción de rol
- `permiso_otorgado` - Permiso específico otorgado
- `permiso_revocado` - Permiso revocado
- `territorio_asignado` - Asignación de alcance territorial
- `territorio_desasignado` - Remoción de territorio

### Importaciones y exportaciones
- `importacion_validada` - Validación previa de archivo
- `importacion_procesada` - Importación completada
- `importacion_revertida` - Importación revertida (rollback)
- `exportacion_creada` - Solicitud de exportación de datos
- `exportacion_auditoria` - Exportación específica de logs de auditoría

### Territorios y catálogos
- `territorio_creado` - Nuevo territorio creado
- `territorio_actualizado` - Territorio modificado
- `territorio_eliminado` - Territorio eliminado
- `puesto_votacion_creado` - Nuevo puesto registrado
- `puesto_votacion_actualizado` - Puesto modificado
- `campana_creada` - Nueva campaña
- `candidato_creado` - Nuevo candidato

### Formularios
- `formulario_subido` - Escaneo de formulario físico subido
- `formulario_validado` - Formulario validado por coordinador
- `formulario_eliminado` - Formulario eliminado

### Auditoría y sistema
- `limpieza_auditoria` - Eliminación de logs antiguos
- `configuracion_cambiada` - Cambio en configuración del sistema

---

## API Endpoints

### 1. Listar eventos de auditoría

```http
GET /api/v1/auditoria
```

**Query params:**
- `user_id` (integer): Filtrar por usuario
- `accion` (string): Filtrar por tipo de acción
- `entidad` (string): Filtrar por entidad afectada
- `entidad_id` (string): Filtrar por ID específico
- `ip` (string): Filtrar por dirección IP
- `desde` (datetime): Fecha inicio (Y-m-d H:i:s)
- `hasta` (datetime): Fecha fin (Y-m-d H:i:s)
- `buscar` (string): Búsqueda en acciones y detalles
- `per_page` (integer): Registros por página (default 50, max 500)
- `order` (string): Campo ordenamiento (default creado_en)
- `direction` (asc|desc): Dirección ordenamiento

**Ejemplo:**
```bash
GET /api/v1/auditoria?accion=simpatizante_eliminado&desde=2026-10-01&per_page=100
```

**Respuesta:**
```json
{
  "message": "Eventos de auditoría obtenidos",
  "data": [
    {
      "id": 15432,
      "user_id": 5,
      "user": {
        "id": 5,
        "name": "María García",
        "email": "maria@ejemplo.com"
      },
      "rol": "Coordinador Municipal",
      "accion": "simpatizante_eliminado",
      "entidad": "simpatizantes",
      "entidad_id": "a3f2c1d4-8b9e-4a7c-9d3e-1f2a3b4c5d6e",
      "ip": "192.168.1.45",
      "user_agent": "Mozilla/5.0...",
      "detalle": {
        "motivo": "Solicitud de supresión del titular"
      },
      "creado_en": "2026-10-15T14:32:10.000000Z"
    }
  ],
  "pagination": {
    "total": 450,
    "per_page": 50,
    "current_page": 1,
    "last_page": 9,
    "from": 1,
    "to": 50
  }
}
```

---

### 2. Ver detalle de un evento

```http
GET /api/v1/auditoria/{id}
```

**Respuesta:**
```json
{
  "message": "Detalle de evento de auditoría",
  "data": {
    "id": 15432,
    "user_id": 5,
    "user": { "id": 5, "name": "María García", "email": "maria@ejemplo.com" },
    "rol": "Coordinador Municipal",
    "accion": "simpatizante_eliminado",
    "entidad": "simpatizantes",
    "entidad_id": "a3f2c1d4-8b9e-4a7c-9d3e-1f2a3b4c5d6e",
    "ip": "192.168.1.45",
    "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64)...",
    "detalle": {
      "motivo": "Solicitud de supresión del titular",
      "cedula_hash": "8f3d9..."
    },
    "creado_en": "2026-10-15T14:32:10.000000Z"
  }
}
```

---

### 3. Resumen estadístico

```http
GET /api/v1/auditoria/resumen?dias=30
```

**Query params:**
- `dias` (integer): Número de días a incluir (default 30)

**Respuesta:**
```json
{
  "message": "Resumen de auditoría (últimos 30 días)",
  "data": {
    "periodo": {
      "desde": "2026-09-15 14:30:00",
      "hasta": "2026-10-15 14:30:00",
      "dias": 30
    },
    "total_eventos": 5240,
    "por_accion": [
      { "accion": "simpatizante_consultado", "total": 1523 },
      { "accion": "simpatizante_creado", "total": 892 },
      { "accion": "login_exitoso", "total": 645 }
    ],
    "por_usuario": [
      {
        "user_id": 8,
        "user_name": "Juan Pérez",
        "user_email": "juan@ejemplo.com",
        "total": 324
      }
    ],
    "por_dia": [
      { "fecha": "2026-10-01", "total": 156 },
      { "fecha": "2026-10-02", "total": 189 }
    ],
    "por_entidad": [
      { "entidad": "simpatizantes", "total": 3456 },
      { "entidad": "usuarios", "total": 234 }
    ],
    "ips_activas": [
      { "ip": "192.168.1.45", "total": 892 },
      { "ip": "10.0.0.15", "total": 456 }
    ]
  }
}
```

---

### 4. Actividad de un usuario específico

```http
GET /api/v1/auditoria/usuario/{userId}?dias=30&limite=50
```

**Query params:**
- `dias` (integer): Días de historial (default 30)
- `limite` (integer): Máximo de eventos (default 50, max 200)

**Respuesta:**
```json
{
  "message": "Actividad reciente del usuario María García",
  "data": {
    "usuario": {
      "id": 5,
      "name": "María García",
      "email": "maria@ejemplo.com"
    },
    "resumen": {
      "total_acciones": 187,
      "acciones_unicas": 12,
      "entidades_afectadas": 5,
      "ips_utilizadas": ["192.168.1.45", "10.0.2.34"],
      "primera_accion": "2026-09-15T08:30:00.000000Z",
      "ultima_accion": "2026-10-15T16:45:00.000000Z"
    },
    "eventos": [...]
  }
}
```

---

### 5. Historial de una entidad específica

```http
GET /api/v1/auditoria/entidad/{entidad}/{entidadId}
```

**Ejemplo:**
```bash
GET /api/v1/auditoria/entidad/simpatizantes/a3f2c1d4-8b9e-4a7c-9d3e-1f2a3b4c5d6e
```

**Respuesta:**
```json
{
  "message": "Historial de auditoría para simpatizantes #a3f2c1d4-8b9e-4a7c-9d3e-1f2a3b4c5d6e",
  "data": {
    "entidad": "simpatizantes",
    "entidad_id": "a3f2c1d4-8b9e-4a7c-9d3e-1f2a3b4c5d6e",
    "total_eventos": 5,
    "eventos": [
      {
        "id": 15432,
        "user": { "name": "María García" },
        "accion": "simpatizante_eliminado",
        "creado_en": "2026-10-15T14:32:10Z"
      },
      {
        "id": 14521,
        "user": { "name": "Juan Pérez" },
        "accion": "simpatizante_actualizado",
        "detalle": { "cambio": "puesto_votacion_id" },
        "creado_en": "2026-10-10T09:15:30Z"
      },
      {
        "id": 12340,
        "user": { "name": "Pedro López" },
        "accion": "simpatizante_creado",
        "creado_en": "2026-10-01T11:22:45Z"
      }
    ]
  }
}
```

---

### 6. Lista de acciones únicas

```http
GET /api/v1/auditoria/acciones
```

**Respuesta:**
```json
{
  "message": "Lista de acciones auditadas",
  "data": [
    { "accion": "login_exitoso", "total": 5623 },
    { "accion": "simpatizante_creado", "total": 3456 },
    { "accion": "simpatizante_actualizado", "total": 1234 }
  ]
}
```

---

### 7. Alertas de seguridad

```http
GET /api/v1/auditoria/alertas?horas=24
```

**Query params:**
- `horas` (integer): Ventana de tiempo para analizar (default 24)

**Detecta automáticamente:**
1. **Login fallidos múltiples** (>5 en 1 hora por usuario)
2. **Exportaciones grandes** recientes
3. **Supresiones de datos** (eliminaciones)
4. **Cambios de roles/permisos**
5. **Accesos desde múltiples IPs** por mismo usuario

**Respuesta:**
```json
{
  "message": "Alertas de seguridad (últimas 24 horas)",
  "data": {
    "periodo": {
      "desde": "2026-10-14 14:30:00",
      "hasta": "2026-10-15 14:30:00"
    },
    "total_alertas": 2,
    "alertas": [
      {
        "tipo": "login_fallidos_multiples",
        "severidad": "alta",
        "mensaje": "Múltiples intentos de login fallidos detectados",
        "detalles": [
          {
            "user_id": 12,
            "intentos": 8,
            "user": { "name": "Usuario X" }
          }
        ]
      },
      {
        "tipo": "exportaciones_recientes",
        "severidad": "media",
        "mensaje": "Se realizaron 3 exportaciones en las últimas 24 horas",
        "detalles": [...]
      }
    ]
  }
}
```

---

### 8. Exportar logs de auditoría

```http
POST /api/v1/auditoria/exportar
```

**Permisos requeridos:** `auditoria.exportar`

**Body:**
```json
{
  "motivo": "Auditoría trimestral de seguridad para informe ejecutivo",
  "incluir_detalle": false,
  "user_id": 5,
  "accion": "simpatizante_eliminado",
  "desde": "2026-10-01 00:00:00",
  "hasta": "2026-10-15 23:59:59"
}
```

**Campos:**
- `motivo` (**obligatorio**): Justificación de la exportación (10-500 caracteres)
- `incluir_detalle` (boolean): Incluir columna JSON con detalles (default false)
- Todos los filtros de `GET /auditoria` son válidos

**Respuesta:**
Archivo CSV con BOM UTF-8 (compatible con Excel), headers:
```
Content-Type: text/csv
Content-Disposition: attachment; filename="auditoria_2026-10-15_143210.csv"
X-Export-Count: 450
X-Export-User: María García
X-Export-Date: 2026-10-15T14:32:10+00:00
```

**Límites:**
- Máximo 50,000 registros por exportación
- La exportación misma queda auditada con la acción `exportacion_auditoria`

**Columnas CSV:**
```
ID, Fecha/Hora, Usuario ID, Usuario Nombre, Usuario Email, Rol, Acción, 
Entidad, Entidad ID, IP, User Agent [, Detalle (JSON)]
```

---

### 9. Limpiar logs antiguos

```http
DELETE /api/v1/auditoria/limpiar
```

**Permisos:** Solo **Superadministrador**

**Body:**
```json
{
  "dias_antiguedad": 730,
  "confirmar": true
}
```

**Validaciones:**
- `dias_antiguedad` mínimo: **365 días** (1 año)
- `confirmar` debe ser `true`

**Respuesta:**
```json
{
  "message": "Se eliminaron 125430 eventos de auditoría anteriores a 2024-10-15",
  "data": {
    "registros_eliminados": 125430,
    "fecha_limite": "2024-10-15T14:32:10.000000Z"
  }
}
```

**Nota:** La limpieza misma queda registrada en auditoría con la acción `limpieza_auditoria` **ANTES** de eliminar los registros.

---

## Registro automático de eventos

### Middleware de auditoría

El sistema utiliza `AuditMiddleware` para registrar automáticamente eventos en rutas sensibles.

**Rutas auditadas:**
- `simpatizantes/*`
- `usuarios/*`
- `territorios/*`
- `campanas/*`
- `importaciones/*`
- `exportaciones/*`
- `lotes/*`
- `2fa/*`

**Métodos auditados:** POST, PUT, PATCH, DELETE

### Registro manual

Para auditar eventos personalizados:

```php
use App\Models\Auditoria;

Auditoria::registrar(
    accion: 'accion_personalizada',
    entidad: 'nombre_tabla',
    entidadId: '123',
    detalle: [
        'campo1' => 'valor1',
        'campo2' => 'valor2',
    ]
);
```

**IMPORTANTE:** El campo `detalle` NO debe contener datos personales en claro (nombres, cédulas, teléfonos). Solo IDs, hashes o metadatos.

---

## Compliance y seguridad

### Datos personales

❌ **NUNCA registrar en auditoría:**
- Cédulas sin cifrar
- Nombres completos de personas
- Teléfonos
- Correos electrónicos
- Direcciones
- Contraseñas o tokens

✅ **SÍ registrar:**
- IDs de usuario (user_id)
- IDs de entidades (simpatizante_id, territorio_id)
- Hashes ciegos (cedula_hash)
- Metadatos (acción, fecha, IP, rol)
- Motivos de acción

### Retención de logs

**Recomendaciones según normativa:**
- Auditoría de acceso a datos personales: **mínimo 2 años**
- Auditoría de seguridad: **mínimo 1 año**
- Auditoría administrativa: según política interna

**Automatización sugerida:**
```bash
# Comando Artisan para limpiar logs > 2 años
php artisan auditoria:limpiar --dias=730 --auto

# Programar en cron (cada mes):
0 3 1 * * cd /ruta/proyecto && php artisan auditoria:limpiar --dias=730 --auto
```

### Alertas de seguridad

**Eventos críticos que deben monitorearse:**
1. `login_fallido` (>5 en 1 hora)
2. `simpatizante_eliminado` (cualquier supresión)
3. `exportacion_creada` (exportaciones de PII)
4. `rol_asignado` / `permiso_otorgado`
5. `2fa_deshabilitado` (desactivación de 2FA)
6. Accesos desde IPs nuevas o múltiples IPs

**Integración con SIEM:** Los logs de auditoría pueden exportarse a sistemas de monitoreo como:
- Wazuh
- OSSEC
- Elastic Stack (ELK)
- Splunk

---

## Casos de uso

### 1. Investigar quién eliminó un registro

```http
GET /api/v1/auditoria/entidad/simpatizantes/{simpatizanteId}
```

Retorna todos los eventos de ese registro, incluyendo creación, ediciones y eliminación.

### 2. Auditoría de accesos de un usuario

```http
GET /api/v1/auditoria/usuario/{userId}?dias=90
```

Ver toda la actividad de un usuario en los últimos 90 días.

### 3. Detectar actividad sospechosa

```http
GET /api/v1/auditoria/alertas?horas=24
```

Obtener alertas de seguridad automáticas.

### 4. Exportar auditoría para revisión legal

```http
POST /api/v1/auditoria/exportar
{
  "motivo": "Auditoría legal del periodo electoral 2026",
  "desde": "2026-01-01 00:00:00",
  "hasta": "2026-12-31 23:59:59",
  "incluir_detalle": true
}
```

### 5. Generar informe de productividad

```http
GET /api/v1/auditoria/resumen?dias=30
```

Ver estadísticas de actividad por usuario, acción y día.

### 6. Rastrear cambios en permisos

```http
GET /api/v1/auditoria?accion=rol_asignado&desde=2026-10-01
```

Ver todos los cambios de roles en un periodo.

---

## Performance

### Índices

Los siguientes índices están creados para garantizar consultas rápidas:

```sql
-- Búsqueda por usuario
CREATE INDEX idx_auditoria_user_id ON auditoria(user_id);

-- Búsqueda por acción
CREATE INDEX idx_auditoria_accion ON auditoria(accion);

-- Búsqueda por entidad
CREATE INDEX idx_auditoria_entidad ON auditoria(entidad, entidad_id);

-- Búsqueda por fecha (común en filtros)
CREATE INDEX idx_auditoria_creado_en ON auditoria(creado_en);

-- Búsqueda por IP (alertas de seguridad)
CREATE INDEX idx_auditoria_ip ON auditoria(ip);

-- Búsqueda en JSONB (filtros avanzados)
CREATE INDEX idx_auditoria_detalle ON auditoria USING gin(detalle);
```

### Particionamiento (opcional para >10M registros)

Si la tabla crece mucho (>10 millones de registros), considerar **particionamiento por fecha**:

```sql
-- Particionar por mes
CREATE TABLE auditoria_2026_10 PARTITION OF auditoria
    FOR VALUES FROM ('2026-10-01') TO ('2026-11-01');

CREATE TABLE auditoria_2026_11 PARTITION OF auditoria
    FOR VALUES FROM ('2026-11-01') TO ('2026-12-01');
```

### Archivado

**Recomendación:** Archivar logs antiguos (>2 años) a almacenamiento frío:

```bash
# Exportar logs antiguos a archivo comprimido
pg_dump -t auditoria --data-only \
  --where="creado_en < '2024-01-01'" \
  mapaelectoral | gzip > auditoria_2022-2023.sql.gz

# Eliminar del sistema activo
DELETE FROM auditoria WHERE creado_en < '2024-01-01';
```

---

## Testing

### Verificar que eventos se registran

```php
// tests/Feature/AuditoriaTest.php

public function test_simpatizante_creado_queda_auditado()
{
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->postJson('/api/v1/simpatizantes', [
        'nombres' => 'Juan',
        'apellidos' => 'Pérez',
        'cedula' => '12345678',
        // ...
    ]);

    $this->assertDatabaseHas('auditoria', [
        'user_id' => $user->id,
        'accion' => 'simpatizante_creado',
        'entidad' => 'simpatizantes',
    ]);
}
```

### Verificar que datos personales NO quedan registrados

```php
public function test_auditoria_no_registra_cedula_en_claro()
{
    // Crear simpatizante con cédula
    $this->postJson('/api/v1/simpatizantes', [
        'cedula' => '12345678',
        // ...
    ]);

    // Verificar que la cédula NO está en el detalle
    $auditoria = Auditoria::latest()->first();
    $this->assertStringNotContainsString('12345678', json_encode($auditoria->detalle));
}
```

---

## Troubleshooting

### Problema: Auditoría no registra eventos

**Verificar:**
1. Middleware `audit` está aplicado en las rutas
2. Usuario autenticado correctamente
3. No hay excepciones silenciadas

```php
// En bootstrap/app.php, verificar que el middleware esté registrado
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'audit' => \App\Http\Middleware\AuditMiddleware::class,
    ]);
})
```

### Problema: Performance lenta en consultas

**Solución:**
1. Verificar índices existen: `\d auditoria` en psql
2. Limitar rango de fechas en consultas
3. Usar paginación (no cargar >1000 registros)
4. Considerar particionamiento si >5M registros

### Problema: Tabla crece demasiado

**Solución:**
```bash
# Ver tamaño de la tabla
SELECT pg_size_pretty(pg_total_relation_size('auditoria'));

# Si >50GB, archivar logs antiguos
php artisan auditoria:limpiar --dias=730
```

---

## Resumen de endpoints

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/auditoria` | Listar eventos con filtros |
| GET | `/auditoria/{id}` | Ver detalle de evento |
| GET | `/auditoria/resumen` | Estadísticas generales |
| GET | `/auditoria/acciones` | Lista de acciones únicas |
| GET | `/auditoria/alertas` | Alertas de seguridad |
| GET | `/auditoria/usuario/{userId}` | Actividad de usuario |
| GET | `/auditoria/entidad/{entidad}/{id}` | Historial de entidad |
| POST | `/auditoria/exportar` | Exportar logs a CSV |
| DELETE | `/auditoria/limpiar` | Limpiar logs antiguos |

---

**Última actualización:** 2026-10-20
