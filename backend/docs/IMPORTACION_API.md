# API de Importación Masiva

Sistema para importar miles de simpatizantes desde archivos CSV o XLSX con validación, detección de duplicados y capacidad de reversión completa.

## 🎯 Características

- **Formatos Soportados**: CSV, XLSX, XLS (máximo 10MB)
- **Validación Previa**: Verifica estructura y datos antes de procesar
- **Detección de Duplicados**: Por cédula usando índice ciego HMAC-SHA256
- **Normalización Automática**: Capitaliza nombres, limpia espacios, valida emails
- **Procesamiento por Lotes**: Agrupa registros importados para fácil gestión
- **Reversión Completa**: Elimina todos los registros de una importación fallida
- **Auditoría**: Registra cada importación con detalle de errores

## 📋 Estructura del Archivo

### Columnas Requeridas

| Columna | Tipo | Descripción | Validación |
|---------|------|-------------|------------|
| `nombres` | string | **Requerido** | Máx 100 caracteres |
| `apellidos` | string | **Requerido** | Máx 100 caracteres |
| `cedula` | string | **Requerido** | Solo números, máx 20 caracteres |

### Columnas Opcionales

| Columna | Tipo | Descripción | Validación |
|---------|------|-------------|------------|
| `telefono` | string | Opcional | Formato telefónico válido |
| `correo` | string | Opcional | Email válido |
| `direccion` | string | Opcional | Máx 255 caracteres |
| `territorio_id` | UUID | Opcional | Si no se proporciona, usa territorio_default_id |
| `observaciones` | string | Opcional | Máx 500 caracteres |

### Ejemplo de Archivo CSV

```csv
nombres,apellidos,cedula,telefono,correo,direccion,territorio_id,observaciones
Juan Carlos,Pérez Gómez,1234567890,3001234567,juan@example.com,Calle 20 # 23-40,,Contactar en la tarde
María Fernanda,López Silva,9876543210,3109876543,maria@example.com,Carrera 15 # 10-20,,
```

### Ejemplo de Archivo XLSX

| nombres | apellidos | cedula | telefono | correo | direccion | territorio_id | observaciones |
|---------|-----------|--------|----------|--------|-----------|---------------|---------------|
| Juan Carlos | Pérez Gómez | 1234567890 | 3001234567 | juan@example.com | Calle 20 # 23-40 | | Contactar en la tarde |
| María Fernanda | López Silva | 9876543210 | 3109876543 | maria@example.com | Carrera 15 # 10-20 | | |

## 🔐 Autenticación y Permisos

**Permisos necesarios:**
- `importacion.realizar`: Subir y procesar archivos
- `importacion.revertir`: Revertir importaciones

Todos los endpoints requieren:
- `Authorization: Bearer {token}`
- `Accept: application/json`
- Middleware: `auth:sanctum`, `2fa`, `audit`

## 📍 Endpoints

### 1. Descargar Plantilla

```http
GET /api/v1/importaciones/plantilla
```

Descarga un archivo CSV de ejemplo con las columnas correctas.

**Respuesta:**

```json
{
  "csv": "nombres,apellidos,cedula,telefono,correo,direccion,territorio_id,observaciones\nJuan Carlos,Pérez Gómez,1234567890,...",
  "columnas": {
    "nombres": "Requerido",
    "apellidos": "Requerido",
    "cedula": "Requerido (solo números)",
    "telefono": "Opcional",
    "correo": "Opcional (email válido)",
    "direccion": "Opcional",
    "territorio_id": "Opcional (UUID, si no se proporciona usa default)",
    "observaciones": "Opcional"
  },
  "instrucciones": [
    "1. El archivo debe ser CSV o XLSX",
    "2. La primera fila debe contener los nombres de las columnas",
    "3. Los campos \"nombres\", \"apellidos\" y \"cedula\" son obligatorios",
    "4. La cédula debe contener solo números",
    "5. El email debe tener formato válido",
    "6. Si no se proporciona territorio_id, se usará el territorio por defecto"
  ]
}
```

### 2. Validar Archivo (Sin Procesar)

```http
POST /api/v1/importaciones/validar
Content-Type: multipart/form-data
```

Valida la estructura y datos del archivo sin crear registros.

**Form Data:**

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `archivo` | file | CSV o XLSX (máx 10MB) |
| `campana_id` | UUID | Campaña destino |
| `territorio_default_id` | UUID | Territorio por defecto si no se especifica en el archivo |

**Respuesta Exitosa:**

```json
{
  "data": {
    "valido": true,
    "total_filas": 150,
    "errores": [],
    "advertencias": [
      "Fila 25: Email inválido, se omitirá",
      "Fila 67: Teléfono con formato incorrecto"
    ],
    "duplicados": [
      {
        "fila": 45,
        "cedula": "1234567890",
        "nombre": "Juan Carlos Pérez Gómez",
        "territorio": "Barrio Centro"
      }
    ],
    "estadisticas": {
      "total": 150,
      "errores": 0,
      "advertencias": 2,
      "duplicados": 1,
      "validos": 149
    }
  },
  "columnas_esperadas": {...}
}
```

**Respuesta con Errores:**

```json
{
  "data": {
    "valido": false,
    "total_filas": 150,
    "errores": [
      "Fila 12: El campo 'nombres' es requerido",
      "Fila 23: Cédula debe contener solo números",
      "Fila 45: El campo 'apellidos' es requerido"
    ],
    "advertencias": [],
    "duplicados": [],
    "estadisticas": {
      "total": 150,
      "errores": 3,
      "advertencias": 0,
      "duplicados": 0,
      "validos": 147
    }
  },
  "columnas_esperadas": {...}
}
```

### 3. Procesar Importación

```http
POST /api/v1/importaciones
Content-Type: multipart/form-data
```

Procesa el archivo y crea los simpatizantes.

**Form Data:**

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `archivo` | file | CSV o XLSX (máx 10MB) |
| `campana_id` | UUID | Campaña destino |
| `territorio_default_id` | UUID | Territorio por defecto |
| `nombre_lote` | string | Opcional. Nombre del lote (default: "Importación YYYY-MM-DD HH:mm") |
| `saltar_duplicados` | boolean | Opcional. Si es true, omite registros duplicados |

**Respuesta (201):**

```json
{
  "message": "Importación completada exitosamente",
  "data": {
    "id": "uuid-importacion",
    "total_filas": 150,
    "filas_exitosas": 148,
    "filas_fallidas": 2,
    "estado": "completada",
    "lote_id": "uuid-lote"
  }
}
```

**Notas:**
- Los simpatizantes se crean en estado `borrador`
- Todos quedan vinculados al mismo `lote_id`
- Si hay errores, se guardan en el campo `errores` de la importación
- La importación es transaccional: si falla completamente, no se crea nada

### 4. Listar Importaciones

```http
GET /api/v1/importaciones?campana_id={uuid}&estado={estado}&revertida={true|false}
```

**Parámetros Query (opcionales):**
- `campana_id`: Filtrar por campaña
- `estado`: procesando, completada, fallida
- `revertida`: true/false
- `por_pagina`: Resultados por página (default: 20)

**Respuesta:**

```json
{
  "data": [
    {
      "id": "uuid",
      "nombre_archivo": "simpatizantes_enero.csv",
      "campana_id": "uuid",
      "lote_id": "uuid",
      "total_filas": 150,
      "filas_procesadas": 150,
      "filas_exitosas": 148,
      "filas_fallidas": 2,
      "estado": "completada",
      "revertida": false,
      "creado_por": "uuid",
      "creador": {
        "id": "uuid",
        "nombre_completo": "María López"
      },
      "created_at": "2024-01-15T10:30:00.000Z",
      "completada_en": "2024-01-15T10:32:15.000Z",
      "revertida_en": null
    }
  ],
  "links": {...},
  "meta": {...}
}
```

### 5. Ver Detalle de Importación

```http
GET /api/v1/importaciones/{id}
```

Incluye los errores detallados si los hubo.

**Respuesta:**

```json
{
  "data": {
    "id": "uuid",
    "nombre_archivo": "simpatizantes_enero.csv",
    "campana": {
      "id": "uuid",
      "nombre": "Campaña 2024"
    },
    "lote": {
      "id": "uuid",
      "nombre": "Importación 2024-01-15 10:30"
    },
    "total_filas": 150,
    "filas_procesadas": 150,
    "filas_exitosas": 148,
    "filas_fallidas": 2,
    "estado": "completada",
    "revertida": false,
    "errores": [
      {
        "fila": 45,
        "error": "Cédula duplicada"
      },
      {
        "fila": 89,
        "error": "Email inválido"
      }
    ],
    "creado_por": {
      "id": "uuid",
      "nombre_completo": "María López"
    },
    "created_at": "2024-01-15T10:30:00.000Z",
    "completada_en": "2024-01-15T10:32:15.000Z",
    "revertida_en": null
  }
}
```

### 6. Revertir Importación

```http
POST /api/v1/importaciones/{id}/revertir
```

**Permiso:** `importacion.revertir`

Elimina TODOS los simpatizantes creados en esta importación (busca por `lote_id`).

**Respuesta:**

```json
{
  "message": "Importación revertida exitosamente. Se eliminaron 148 registros.",
  "data": {
    "id": "uuid-importacion",
    "registros_eliminados": 148,
    "revertida_en": "2024-01-15T15:00:00.000Z"
  }
}
```

**Errores:**

```json
{
  "message": "Esta importación ya fue revertida"
}
```

## 🔄 Flujo de Trabajo

### Opción 1: Validar y Procesar

```
1. GET /importaciones/plantilla
   → Descargar plantilla y llenarla
   
2. POST /importaciones/validar
   → Verificar errores y duplicados
   → Corregir archivo si hay errores
   
3. POST /importaciones
   → Procesar y crear simpatizantes
   → Estado: completada
   
4. GET /simpatizantes?lote_id={id}
   → Revisar registros importados
   
5. (Opcional) POST /importaciones/{id}/revertir
   → Si algo salió mal, eliminar todo
```

### Opción 2: Procesar Directo

```
1. POST /importaciones
   (con saltar_duplicados: true)
   → Procesar y omitir duplicados
   
2. Ver resultado en respuesta
   → filas_exitosas vs filas_fallidas
```

## 📊 Normalización Automática

El sistema aplica estas transformaciones:

### Nombres y Apellidos
```
"JUAN CARLOS"     → "Juan Carlos"
"  maría  "       → "María"
"lopez garcía"    → "Lopez García"
```

### Emails
```
"JUAN@EXAMPLE.COM" → "juan@example.com"
"  correo@test.com  " → "correo@test.com"
"email-invalido"   → null (se omite)
```

### Cédulas
```
"  1234567890  " → "1234567890"
"123-456-789"    → (error: solo números)
```

### Teléfonos
```
"  300 123 4567  " → "300 123 4567"
"+57 300 1234567" → "+57 300 1234567"
```

## 🚨 Errores Comunes

### 422 - Archivo Vacío

```json
{
  "data": {
    "valido": false,
    "errores": ["El archivo está vacío o no tiene el formato correcto"],
    "total_filas": 0
  }
}
```

**Solución:** Verificar que el archivo tenga header y al menos una fila de datos.

### 422 - Columnas Incorrectas

```json
{
  "data": {
    "valido": false,
    "errores": [
      "Fila 2: El campo 'nombres' es requerido",
      "Fila 3: El campo 'apellidos' es requerido"
    ]
  }
}
```

**Solución:** Verificar que el archivo tenga todas las columnas requeridas con los nombres correctos (minúsculas).

### 422 - Duplicados

```json
{
  "message": "Importación completada exitosamente",
  "data": {
    "filas_exitosas": 145,
    "filas_fallidas": 5
  }
}
```

**Solución:** 
1. Ver detalle de la importación para identificar filas fallidas
2. O usar `saltar_duplicados: true` en la próxima importación

### 403 - Sin acceso

```json
{
  "message": "No tiene acceso a esta campaña"
}
```

**Solución:** El usuario no tiene asignada la campaña especificada. Verificar `usuario_campana`.

### 500 - Error de procesamiento

```json
{
  "message": "Error al procesar importación",
  "error": "Detailed error message"
}
```

**Solución:** Ver logs del servidor o contactar soporte.

## 💡 Mejores Prácticas

### 1. Siempre Validar Primero

```javascript
// ❌ MAL: Procesar sin validar
await uploadFile('/importaciones', file);

// ✅ BIEN: Validar, revisar, luego procesar
const validacion = await uploadFile('/importaciones/validar', {
  archivo: file,
  campana_id: campanaId,
  territorio_default_id: territorioId
});

if (validacion.data.valido) {
  await uploadFile('/importaciones', {...});
} else {
  showErrors(validacion.data.errores);
}
```

### 2. Manejar Duplicados

```javascript
const options = {
  archivo: file,
  campana_id: campanaId,
  territorio_default_id: territorioId,
  saltar_duplicados: true  // Omitir duplicados automáticamente
};
```

### 3. Usar Lotes Descriptivos

```javascript
const options = {
  archivo: file,
  campana_id: campanaId,
  territorio_default_id: territorioId,
  nombre_lote: `Importación Barrio Centro - Enero 2024`
};
```

### 4. Revisar Antes de Confirmar

```javascript
const importacion = await processImport(options);

// Mostrar resumen al usuario
console.log(`
  Total: ${importacion.total_filas}
  Exitosos: ${importacion.filas_exitosas}
  Fallidos: ${importacion.filas_fallidas}
`);

// Si hay muchos errores, ofrecer revertir
if (importacion.filas_fallidas > 10) {
  const confirmar = confirm('¿Desea revertir esta importación?');
  if (confirmar) {
    await revertImport(importacion.id);
  }
}
```

### 5. Monitorear Grandes Importaciones

Para archivos con miles de registros, considerar:

```javascript
// Dividir en lotes más pequeños
const chunkSize = 500;
const chunks = chunkArray(data, chunkSize);

for (const chunk of chunks) {
  const result = await uploadChunk(chunk);
  updateProgress(result);
  await sleep(1000); // Pausa entre lotes
}
```

## 🔍 Consultar Simpatizantes Importados

### Por Lote

```http
GET /api/v1/simpatizantes?lote_id={uuid}
```

### Por Fecha de Importación

```http
GET /api/v1/simpatizantes?created_at_desde=2024-01-15&created_at_hasta=2024-01-15
```

### Estadísticas del Lote

```http
GET /api/v1/lotes/{id}/estadisticas
```

## 📊 Auditoría

Todas las importaciones se registran en:

1. **Tabla `importaciones`**: Metadata y estadísticas
2. **Tabla `lotes`**: Agrupación de registros
3. **Tabla `auditoria`**: Log completo de la acción
4. **Campo `simpatizantes.lote_id`**: Vinculación individual

Consultar auditoría:

```http
GET /api/v1/auditoria?accion=importacion_creada&desde=2024-01-01
```

## 🧪 Testing

### Ejemplo de Archivo de Prueba

```csv
nombres,apellidos,cedula,telefono,correo
Juan,Pérez,1234567890,3001234567,juan@test.com
María,López,9876543210,3109876543,maria@test.com
Carlos,Gómez,5555555555,3205555555,carlos@test.com
```

### Casos de Prueba

1. **Archivo válido completo** → 100% exitoso
2. **Con duplicados** → Algunos fallidos
3. **Con emails inválidos** → Advertencias, pero procesa
4. **Sin campos requeridos** → Errores, no procesa
5. **Archivo vacío** → Error inmediato
6. **Formato incorrecto** → Error de lectura

## 📚 Referencias

- **API de Simpatizantes**: `docs/SIMPATIZANTES_API.md`
- **Cifrado de Datos**: `docs/ENCRYPTION.md`
- **Control de Acceso**: `docs/TERRITORIAL_AUTHORIZATION.md`
- **Esquema de BD**: `database/DATABASE_SCHEMA.md`
