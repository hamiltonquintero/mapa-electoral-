# API de Gestión de Simpatizantes

API REST completa para captura, gestión y validación de simpatizantes del sistema electoral.

## 🎯 Características

- **CRUD Completo**: Crear, leer, actualizar y eliminar simpatizantes
- **Detección de Duplicados**: Por cédula usando índice ciego HMAC-SHA256
- **Cifrado Automático**: Campos sensibles (cédula, teléfono, correo, dirección) cifrados con AES-256-GCM
- **Gestión de Estados**: borrador → pendiente_evidencia → validado/rechazado
- **Formularios Adjuntos**: Subir escaneos de documentos con URLs temporales firmadas
- **Filtros Avanzados**: Por campaña, territorio, puesto, mesa, estado, líder, candidato
- **Control de Acceso**: Restricción automática por alcance territorial y de campaña

## 📋 Estados de Simpatizantes

| Estado | Descripción |
|--------|-------------|
| **borrador** | Recién creado, sin validar |
| **pendiente_evidencia** | Esperando validación de formulario adjunto |
| **validado** | Verificado y aprobado |
| **rechazado** | No cumple requisitos o datos incorrectos |

## 🔐 Autenticación y Permisos

Todos los endpoints requieren:
- `Authorization: Bearer {token}`
- `Accept: application/json`
- Middleware: `auth:sanctum`, `2fa`, `territory.scope`, `campaign.scope`

**Permisos necesarios:**
- `simpatizante.ver`: Listar y ver detalles
- `simpatizante.crear`: Crear nuevos
- `simpatizante.actualizar`: Editar existentes
- `simpatizante.eliminar`: Eliminar (soft delete)
- `simpatizante.exportar`: Exportar datos

## 📍 Endpoints

### 1. Listar Simpatizantes

```http
GET /api/v1/simpatizantes
```

**Parámetros Query (todos opcionales):**

| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| `campana_id` | UUID | Filtrar por campaña |
| `territorio_id` | UUID | Filtrar por territorio |
| `puesto_id` | UUID | Filtrar por puesto de votación |
| `mesa` | integer | Filtrar por número de mesa |
| `estado` | string | borrador, pendiente_evidencia, validado, rechazado |
| `lider_id` | UUID | Filtrar por líder asignado |
| `candidato_id` | UUID | Filtrar por candidato que apoyan |
| `lote_id` | UUID | Filtrar por lote de importación |
| `sin_puesto` | boolean | Solo simpatizantes sin puesto asignado |
| `sin_formulario` | boolean | Solo simpatizantes sin formulario adjunto |
| `buscar` | string | Buscar por nombre, apellido o cédula |
| `orden_campo` | string | Campo para ordenar (default: created_at) |
| `orden_dir` | string | asc o desc (default: desc) |
| `por_pagina` | integer | Resultados por página (default: 50) |

**Ejemplo:**

```http
GET /api/v1/simpatizantes?campana_id=xxx&estado=validado&territorio_id=yyy&por_pagina=100
```

**Respuesta:**

```json
{
  "data": [
    {
      "id": "uuid",
      "nombres": "Juan Carlos",
      "apellidos": "Pérez Gómez",
      "nombre_completo": "Juan Carlos Pérez Gómez",
      "cedula": "1234567890",
      "telefono": "3001234567",
      "correo": "juan@example.com",
      "direccion": "Calle 20 # 23-40",
      "campana_id": "uuid",
      "campana": {
        "id": "uuid",
        "nombre": "Campaña 2024",
        "codigo": "C2024",
        "activa": true
      },
      "territorio_id": "uuid",
      "territorio": {
        "id": "uuid",
        "nombre": "Barrio Centro",
        "tipo": "barrio",
        "codigo": "17001-001",
        "nivel": 3
      },
      "puesto_id": "uuid",
      "puesto": {
        "id": "uuid",
        "nombre": "Colegio San José",
        "codigo": "17001-001",
        "direccion": "Carrera 23 # 20-15",
        "total_mesas": 12
      },
      "mesa": 5,
      "lider_id": "uuid",
      "lider": {
        "id": "uuid",
        "nombres": "María",
        "apellidos": "López",
        "nombre_completo": "María López",
        "email": "maria@example.com"
      },
      "candidatos": [
        {
          "id": "uuid",
          "nombres": "Carlos",
          "apellidos": "Rodríguez",
          "nombre_completo": "Carlos Rodríguez",
          "cargo": "Alcalde",
          "numero_lista": 1
        }
      ],
      "formulario_id": "uuid",
      "formulario": {
        "id": "uuid",
        "estado": "validado",
        "url": "https://..."
      },
      "lote_id": null,
      "lote": null,
      "estado": "validado",
      "observaciones": null,
      "creado_por": "uuid",
      "creador": {...},
      "actualizado_por": "uuid",
      "actualizador": {...},
      "created_at": "2024-01-15T10:30:00.000Z",
      "updated_at": "2024-01-16T14:20:00.000Z",
      "deleted_at": null
    }
  ],
  "links": {
    "first": "...",
    "last": "...",
    "prev": null,
    "next": "..."
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 25,
    "per_page": 50,
    "to": 50,
    "total": 1250
  }
}
```

### 2. Ver un Simpatizante

```http
GET /api/v1/simpatizantes/{id}
```

Incluye relaciones completas y los últimos 10 cambios del historial.

**Respuesta:** Igual que el elemento individual en listar, pero con `historial_cambios`:

```json
{
  "data": {
    ...
    "historial_cambios": [
      {
        "id": "uuid",
        "campo": "estado",
        "valor_anterior": "borrador",
        "valor_nuevo": "validado",
        "user_id": "uuid",
        "created_at": "2024-01-16T14:20:00.000Z"
      }
    ]
  }
}
```

### 3. Crear Simpatizante

```http
POST /api/v1/simpatizantes
Content-Type: application/json
```

**Permiso:** `simpatizante.crear`

**Body:**

```json
{
  "campana_id": "uuid",
  "territorio_id": "uuid",
  "nombres": "Juan Carlos",
  "apellidos": "Pérez Gómez",
  "cedula": "1234567890",
  "telefono": "3001234567",
  "correo": "juan@example.com",
  "direccion": "Calle 20 # 23-40",
  "puesto_id": "uuid",
  "mesa": 5,
  "lider_id": "uuid",
  "candidato_ids": ["uuid1", "uuid2"],
  "observaciones": "Contactar en la tarde",
  "lote_id": null
}
```

**Validaciones:**

| Campo | Reglas |
|-------|--------|
| `nombres` | requerido, máx 100 caracteres, solo letras y espacios |
| `apellidos` | requerido, máx 100 caracteres, solo letras y espacios |
| `cedula` | requerido, máx 20 caracteres, solo números |
| `telefono` | opcional, máx 20 caracteres, formato válido |
| `correo` | opcional, email válido |
| `mesa` | opcional, entre 1-999 |
| `candidato_ids` | opcional, mínimo 1 si se proporciona |

**Respuesta Exitosa (201):**

```json
{
  "message": "Simpatizante creado exitosamente",
  "data": {...}
}
```

**Respuesta Error - Duplicado (422):**

```json
{
  "message": "Ya existe un simpatizante con esta cédula en la campaña",
  "duplicados": [
    {
      "id": "uuid",
      "nombre_completo": "Juan Carlos Pérez Gómez",
      "territorio": {...},
      "estado": "validado",
      "created_at": "2024-01-10T08:00:00.000Z"
    }
  ]
}
```

### 4. Actualizar Simpatizante

```http
PUT /api/v1/simpatizantes/{id}
Content-Type: application/json
```

**Permiso:** `simpatizante.actualizar`

**Body:** Igual que crear, pero todos los campos opcionales (solo enviar los que cambian).

**Respuesta:**

```json
{
  "message": "Simpatizante actualizado exitosamente",
  "data": {...}
}
```

### 5. Eliminar Simpatizante

```http
DELETE /api/v1/simpatizantes/{id}
```

**Permiso:** `simpatizante.eliminar`

Realiza un soft delete (el registro no se elimina físicamente, solo se marca como eliminado).

**Respuesta:**

```json
{
  "message": "Simpatizante eliminado exitosamente"
}
```

### 6. Buscar Duplicados

```http
POST /api/v1/simpatizantes/buscar-duplicados
Content-Type: application/json
```

Busca simpatizantes duplicados por cédula antes de crear/actualizar.

**Body:**

```json
{
  "cedula": "1234567890",
  "campana_id": "uuid",
  "excluir_id": "uuid"
}
```

**Respuesta:**

```json
{
  "encontrados": 1,
  "data": [
    {
      "id": "uuid",
      "nombre_completo": "Juan Carlos Pérez Gómez",
      "cedula": "1234567890",
      "territorio": {...},
      "estado": "validado",
      "created_at": "2024-01-10T08:00:00.000Z"
    }
  ]
}
```

### 7. Cambiar Estado

```http
POST /api/v1/simpatizantes/{id}/cambiar-estado
Content-Type: application/json
```

**Body:**

```json
{
  "estado": "validado",
  "observaciones": "Verificado correctamente"
}
```

**Estados válidos:** `borrador`, `pendiente_evidencia`, `validado`, `rechazado`

**Respuesta:**

```json
{
  "message": "Estado cambiado de 'borrador' a 'validado'",
  "data": {...}
}
```

### 8. Asignar Puesto y Mesa

```http
POST /api/v1/simpatizantes/{id}/asignar-puesto-mesa
Content-Type: application/json
```

**Body:**

```json
{
  "puesto_id": "uuid",
  "mesa": 5
}
```

**Validaciones:**
- El puesto debe pertenecer al mismo territorio del simpatizante
- La mesa debe existir en el puesto (entre 1 y `total_mesas`)

**Respuesta:**

```json
{
  "message": "Puesto y mesa asignados exitosamente",
  "data": {...}
}
```

### 9. Asignar Líder

```http
POST /api/v1/simpatizantes/{id}/asignar-lider
Content-Type: application/json
```

**Body:**

```json
{
  "lider_id": "uuid"
}
```

Para remover el líder, enviar `null`:

```json
{
  "lider_id": null
}
```

**Respuesta:**

```json
{
  "message": "Líder asignado exitosamente",
  "data": {...}
}
```

## 📄 Gestión de Formularios

### 10. Subir Formulario (Escaneo)

```http
POST /api/v1/formularios
Content-Type: multipart/form-data
```

**Form Data:**

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `simpatizante_id` | UUID | ID del simpatizante |
| `archivo` | file | JPG, PNG o PDF (máx 10MB) |
| `tipo` | string | cedula, planilla, otro |
| `observaciones` | string | Opcional |

**Respuesta:**

```json
{
  "message": "Formulario subido exitosamente",
  "data": {
    "id": "uuid",
    "tipo": "cedula",
    "nombre_original": "cedula_juan.jpg",
    "tamano": 1024000,
    "estado": "pendiente",
    "url_temporal": "https://...?signature=...",
    "created_at": "2024-01-15T10:30:00.000Z"
  }
}
```

**Notas:**
- El archivo se guarda cifrado en `storage/app/private/formularios/`
- Se genera automáticamente una URL temporal firmada (válida 15 minutos)
- Si el simpatizante estaba en estado `borrador`, cambia a `pendiente_evidencia`

### 11. Ver Formulario

```http
GET /api/v1/formularios/{id}
```

Genera una nueva URL temporal firmada para acceder al archivo.

**Respuesta:**

```json
{
  "data": {
    "id": "uuid",
    "simpatizante_id": "uuid",
    "tipo": "cedula",
    "nombre_original": "cedula_juan.jpg",
    "mime_type": "image/jpeg",
    "tamano": 1024000,
    "observaciones": null,
    "estado": "validado",
    "url_temporal": "https://...?signature=...",
    "subido_por": "uuid",
    "created_at": "2024-01-15T10:30:00.000Z",
    "updated_at": "2024-01-16T09:00:00.000Z"
  }
}
```

### 12. Validar/Rechazar Formulario

```http
POST /api/v1/formularios/{id}/validar
Content-Type: application/json
```

**Body:**

```json
{
  "estado": "validado",
  "observaciones": "Documento legible y válido"
}
```

**Estados válidos:** `validado`, `rechazado`

**Efecto en simpatizante:**
- Si el formulario se valida y el simpatizante está en `pendiente_evidencia`, pasa a `validado`

**Respuesta:**

```json
{
  "message": "Formulario validado exitosamente",
  "data": {
    "id": "uuid",
    "estado": "validado"
  }
}
```

### 13. Eliminar Formulario

```http
DELETE /api/v1/formularios/{id}
```

Elimina el archivo físico y el registro de la base de datos.

**Respuesta:**

```json
{
  "message": "Formulario eliminado exitosamente"
}
```

## 🔍 Filtros Avanzados

### Búsqueda por Texto

```http
GET /api/v1/simpatizantes?buscar=juan
```

Busca en:
- Nombres (ILIKE)
- Apellidos (ILIKE)
- Cédula (por índice ciego HMAC si es numérico)

### Combinación de Filtros

```http
GET /api/v1/simpatizantes?campana_id=xxx&territorio_id=yyy&estado=validado&sin_puesto=true&orden_campo=apellidos&orden_dir=asc
```

### Simpatizantes Sin Asignar

```http
# Sin puesto de votación
GET /api/v1/simpatizantes?sin_puesto=true

# Sin formulario adjunto
GET /api/v1/simpatizantes?sin_formulario=true

# Sin líder asignado
GET /api/v1/simpatizantes?lider_id=null
```

## 🔒 Seguridad y Privacidad

### Cifrado Automático

Los siguientes campos se cifran automáticamente con AES-256-GCM:
- `cedula` → `cedula_cifrada`
- `telefono` → `telefono_cifrado`
- `correo` → `correo_cifrado`
- `direccion` → `direccion_cifrada`

El descifrado es automático al acceder mediante accessors.

### Índice Ciego para Búsqueda

La cédula también se guarda como `cedula_hash` (HMAC-SHA256) para permitir búsquedas sin descifrar:

```php
// Buscar por cédula sin descifrar todos los registros
$hash = hash_hmac('sha256', $cedula, config('encryption.cedula_hash_key'));
$simpatizante = Simpatizante::where('cedula_hash', $hash)->first();
```

### Control de Acceso

- Solo se muestran simpatizantes de territorios y campañas accesibles por el usuario
- Superadministradores ven todos
- Otros roles solo ven lo asignado + descendientes

### Auditoría Automática

Todas las operaciones de escritura (POST, PUT, DELETE) se registran automáticamente en la tabla `auditoria` con:
- Usuario que ejecuta
- IP y User-Agent
- Datos modificados (sanitizados)
- Resultado (exitoso/fallido)

## 📊 Flujo de Trabajo Típico

### 1. Captura Manual

```
1. POST /simpatizantes
   → Estado: borrador
   
2. POST /formularios (subir foto de cédula)
   → Estado simpatizante: pendiente_evidencia
   
3. POST /formularios/{id}/validar
   → Estado simpatizante: validado
   
4. POST /simpatizantes/{id}/asignar-puesto-mesa
   → Simpatizante listo para votación
```

### 2. Importación Masiva

```
1. POST /importaciones (archivo CSV/XLSX)
   → Crea simpatizantes en lote
   → Estado: borrador
   → lote_id asignado
   
2. Validación individual o masiva
   
3. Asignación masiva de puestos/mesas (opcional)
```

### 3. Asignación a Líder

```
1. GET /simpatizantes?territorio_id=xxx&sin_lider=true
   
2. POST /simpatizantes/{id}/asignar-lider
   (repetir para cada simpatizante)
```

## 🚨 Errores Comunes

### 422 - Duplicado

```json
{
  "message": "Ya existe un simpatizante con esta cédula en la campaña",
  "duplicados": [...]
}
```

**Solución:** Verificar si es el mismo simpatizante o actualizar el existente.

### 403 - Sin acceso al territorio

```json
{
  "message": "No tiene acceso a este territorio"
}
```

**Solución:** El usuario no tiene asignado ese territorio. Verificar asignaciones en `usuario_territorio`.

### 422 - Puesto incompatible

```json
{
  "message": "El puesto de votación no pertenece al territorio del simpatizante"
}
```

**Solución:** Seleccionar un puesto del mismo territorio.

### 422 - Mesa no existe

```json
{
  "message": "La mesa 15 no existe en este puesto (máximo: 12)"
}
```

**Solución:** Verificar `total_mesas` del puesto.

## 📚 Referencias

- **Cifrado**: `docs/ENCRYPTION.md`
- **Control de Acceso**: `docs/TERRITORIAL_AUTHORIZATION.md`
- **Roles y Permisos**: `docs/ROLES_AND_PERMISSIONS.md`
- **Esquema de BD**: `database/DATABASE_SCHEMA.md`
