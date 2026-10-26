# Manual de Usuario — Mapa Electoral Caldas

## Acceso al sistema

### Inicio de sesión
1. Abrir el navegador y entrar a la URL del sistema
2. Ingresar **correo electrónico** y **contraseña**
3. Si tienes 2FA activado, ingresar el código de 6 dígitos de tu app (Google Authenticator, Authy)

> ⚠️ Después de 5 intentos fallidos, la cuenta se bloquea 30 minutos automáticamente.

---

## Mapa Electoral (pantalla principal)

El mapa es el núcleo del sistema. Muestra la distribución de apoyos por territorio.

### Navegación por niveles
| Nivel | Qué muestra |
|-------|-------------|
| Departamento | Los 27 municipios coloreados por cantidad de apoyos |
| Municipio | Barrios, veredas y corregimientos del municipio |
| Zona | Puestos de votación de esa zona |
| Puesto | Desglose por mesa |

**Para navegar:**
- Haz clic en un municipio o zona → entra al siguiente nivel
- Usa el **breadcrumb** (migas de pan) arriba del panel para volver atrás
- Los colores: azul claro = pocos apoyos, azul oscuro = muchos apoyos

### Panel lateral
El panel izquierdo muestra:
- **Breadcrumb**: tu posición en la jerarquía
- **Estadísticas**: total validados, porcentaje validado, pendientes, territorios
- **Filtros**: por campaña y estado
- **Lista**: municipios o puestos con sus conteos

### Buscador global
Usa la barra de búsqueda del encabezado para encontrar:
- Un **municipio o zona** por nombre → navega al mapa
- Una **persona** por nombre → abre su ficha

---

## Registro de simpatizantes

### Crear un nuevo registro

1. Ir a **Simpatizantes → Nuevo**
2. Completar los datos obligatorios: nombres, apellidos, cédula, municipio/zona
3. **Cargar el formulario físico**: arrastra el escaneo (JPG/PNG/PDF) o haz clic en el área de carga
4. Indicar la **fecha de firma** del formulario físico
5. Clic en **Crear simpatizante**

> 📌 Si la cédula ya existe en la misma campaña, el sistema te avisa con un mensaje de duplicado.

### Estados de un registro

| Estado | Significado |
|--------|-------------|
| Borrador | Recién creado, sin revisar |
| Pendiente evidencia | Importado sin formulario físico adjunto |
| Validado | Revisado y confirmado por coordinador |
| Rechazado | No cumple requisitos |

### Buscar y filtrar

En la lista de **Simpatizantes** puedes filtrar por:
- Nombre o apellido (búsqueda parcial)
- Cédula (exacta)
- Estado del registro
- Municipio o zona
- Lote de captura

---

## Importación masiva

Para importar una base de datos existente en Excel:

1. Ir a **Importación**
2. **Paso 1**: Descargar la plantilla CSV de ejemplo. Llenar el archivo.
3. **Paso 2**: Subir el archivo (CSV o Excel, máx 10 MB)
4. **Paso 3**: Revisar los resultados de validación:
   - Filas correctas: se importarán
   - Filas con error: ver columna y mensaje de error
   - Puedes marcar "saltar duplicados"
5. **Paso 4**: Confirmar la importación

> 💡 Los registros importados quedan en estado **pendiente_evidencia** hasta que se adjunten sus formularios físicos.

### Columnas del archivo de importación

| Columna | Obligatorio | Notas |
|---------|-------------|-------|
| nombres | ✓ | |
| apellidos | ✓ | |
| cedula | ✓ | Solo numérica, sin puntos ni espacios |
| telefono | | Formato colombiano |
| correo | | |
| direccion | | |
| territorio_id | | ID del municipio/zona en el sistema |
| observaciones | | |

---

## Reportes y tableros

### Tablero general
Muestra el resumen de apoyos con:
- Totales por estado
- Evolución de capturas por día
- Top 10 municipios
- Productividad por digitador

### Calidad de datos
Identifica registros incompletos:
- Sin puesto de votación asignado
- Sin mesa
- Sin zona geográfica
- Sin formulario físico

### Productividad
Muestra el rendimiento de cada digitador en el período seleccionado.

---

## Administración (solo roles con permisos)

### Usuarios
- Crear, editar y desactivar usuarios del sistema
- Asignar roles y territorios
- El rol determina qué puede ver y hacer cada usuario

### Campañas y candidatos
- Gestionar las campañas electorales y sus candidatos
- Cada candidato tiene nombre, cargo (gobernación/alcaldía/asamblea) y color

### Puestos de votación
- Registrar y editar puestos con nombre, dirección, número de mesas y coordenadas
- Las coordenadas permiten mostrarlos en el mapa

---

## Auditoría

El sistema registra automáticamente toda acción sensible:
- Inicios y cierres de sesión
- Creación, edición y eliminación de registros
- Exportaciones de datos
- Cambios de permisos

Solo usuarios con permiso `auditoria.ver` pueden consultar los logs.

**Exportar auditoría:** en la pantalla de Auditoría, clic en "Exportar CSV". Se requiere indicar el motivo de la exportación. La exportación misma queda registrada.

---

## Mi perfil

En la esquina superior derecha → ícono de usuario → **Mi perfil**:
- Ver datos de cuenta y rol asignado
- **Activar o desactivar el 2FA**: escanea el QR con Google Authenticator o Authy
- Ver tus permisos activos

---

## Preguntas frecuentes

**¿Cómo recupero mi contraseña?**
Usa el enlace "¿Olvidaste tu contraseña?" en la pantalla de login. Recibirás un correo con un enlace de un solo uso.

**¿Por qué no veo todos los municipios?**
Tu acceso está limitado al territorio que te asignó el administrador. Si necesitas acceso a más territorios, comunícalo.

**¿Puedo exportar la lista de personas?**
Solo los roles autorizados (`simpatizante.exportar`) pueden exportar. Toda exportación requiere indicar el motivo y queda auditada.

**¿Qué hago si veo un duplicado?**
Al crear un registro, el sistema detecta duplicados automáticamente. Si el sistema lo avisa, verifica antes de continuar. Si ya está en la base y es el mismo votante, no crees un duplicado.

**¿Cómo asigno un puesto de votación a un simpatizante?**
En la **ficha de simpatizante** → "Cambiar estado" / "Asignar puesto". También se puede hacer en lote por importación.

---

*Sistema de uso restringido — solo personal autorizado del equipo de campaña.*
