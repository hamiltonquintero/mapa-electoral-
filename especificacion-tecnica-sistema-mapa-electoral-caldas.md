# Especificación técnica — Sistema Web de Georreferenciación de Apoyos Electorales (Caldas)

**Versión:** 1.0 (borrador)
**Fecha:** 20 de octubre de 2026
**Autor / Responsable del producto:** Hamilton Quintero García
**Alcance geográfico:** Departamento de Caldas (27 municipios)
**Tipo de sistema:** Aplicación web multiusuario, de producción, escalable a todo el departamento

> Nota: los aspectos jurídicos (autorización de tratamiento de datos, política de tratamiento, responsable del tratamiento) están a cargo de Yhon. Este documento define los requisitos técnicos y deja los puntos de integración que el componente jurídico necesita (evidencia de consentimiento, supresión, auditoría).

---

## 1. Objetivo

Construir una plataforma web que permita registrar, consultar y visualizar en un mapa interactivo del departamento de Caldas a las personas que apoyan a uno o varios candidatos (gobernación, alcaldía, asamblea), organizadas por **municipio → corregimiento / vereda / barrio → puesto de votación → mesa**, mostrando conteos agregados en cada nivel y, según el rol del usuario, el detalle de cada persona.

### 1.1 Objetivos específicos

1. Centralizar en una sola base de datos la información recopilada en reuniones y actividades de campo.
2. Visualizar la distribución territorial del apoyo mediante mapa con navegación jerárquica (drill-down).
3. Controlar estrictamente quién ve qué, según territorio y rol.
4. Mantener trazabilidad: cada registro vinculado a su formulario físico firmado (escaneo/foto).
5. Soportar operación a escala departamental: cientos de miles de registros y múltiples usuarios simultáneos.
6. Proteger los datos personales con cifrado, auditoría y mecanismos de borrado.

### 1.2 Fuera de alcance (explícito)

- Envío masivo de mensajes, SMS o WhatsApp (puede evaluarse como módulo futuro, sujeto a revisión jurídica).
- Cualquier funcionalidad orientada a presionar, condicionar o controlar el voto de las personas, o a registrar si "cumplieron" con votar.
- Integración con sistemas de la Registraduría o consultas automáticas al censo electoral.
- Aplicación móvil nativa (la versión web será responsive; PWA en fase posterior).

---

## 2. Usuarios y roles

| Rol | Descripción | Alcance de datos | Permisos principales |
|---|---|---|---|
| **Superadministrador** | Dueño del sistema (Hamilton) | Todo el departamento, todas las campañas | Gestión total, usuarios, catálogos, auditoría, exportaciones, borrado definitivo, configuración |
| **Administrador de campaña** | Responsable de una campaña/candidato | Toda su campaña | Gestión de usuarios de su campaña, reportes, exportaciones autorizadas |
| **Coordinador municipal** | Responsable de un municipio | Solo su(s) municipio(s) | Ver mapa y detalle de su municipio, asignar líderes, revisar capturas |
| **Líder de zona** | Responsable de vereda/barrio/corregimiento | Solo su(s) zona(s) | Ver y editar registros de su zona |
| **Digitador** | Captura formularios en papel | Solo lo que captura (y su lote) | Crear registros y subir escaneos; sin acceso a consulta global ni exportación |
| **Auditor (solo lectura)** | Revisión de calidad y trazabilidad | Según asignación | Consulta, sin edición ni exportación |

Reglas:

- El control de acceso se aplica **en el servidor y en la base de datos (filtros por territorio en cada consulta)**, nunca solo ocultando elementos en la interfaz.
- Un usuario puede tener varios territorios asignados.
- Los permisos se definen como políticas (por ejemplo: `simpatizante.ver`, `simpatizante.editar`, `simpatizante.exportar`, `territorio.administrar`).

---

## 3. Alcance funcional

### 3.1 Módulo de autenticación y seguridad de cuenta

- Inicio de sesión con correo y contraseña.
- Contraseñas con hash Argon2id (o bcrypt con costo alto).
- **Doble factor (TOTP)** obligatorio para Superadministrador y Administrador de campaña; opcional/recomendado para coordinadores.
- Bloqueo temporal tras intentos fallidos; limitación de tasa (rate limiting) por IP y por usuario.
- Cierre de sesión por inactividad (por ejemplo, 20 minutos).
- Recuperación de contraseña por correo con token de un solo uso y expiración corta.
- Registro de sesiones activas y posibilidad de cerrarlas remotamente.

### 3.2 Módulo de mapa interactivo (núcleo del sistema)

**Navegación jerárquica:**

1. **Nivel 0 — Departamento:** mapa de Caldas con los 27 municipios coloreados por cantidad de apoyos (escala de color configurable, mapa de calor por rangos). Tarjeta resumen con total departamental.
2. **Nivel 1 — Municipio:** al hacer clic, el mapa acerca (zoom) al municipio y muestra sus subdivisiones (corregimientos, veredas, barrios) con conteo por cada una.
3. **Nivel 2 — Zona (vereda/barrio/corregimiento):** muestra los puestos de votación de la zona como marcadores con conteo.
4. **Nivel 3 — Puesto de votación:** panel con desglose por mesa (mesa 1, mesa 2, …) y total.
5. **Nivel 4 — Mesa:** listado de personas (según permisos del rol).

**Funciones del mapa:**

- Migas de pan (breadcrumb) para subir de nivel: Caldas › Manizales › Barrio X › Puesto Y › Mesa 3.
- Panel lateral con conteos, porcentajes y gráfico de barras del nivel actual.
- Filtros: candidato, cargo, campaña, estado del registro, rango de fechas de captura, líder responsable.
- Buscador de territorio (por nombre) y buscador de persona (según permisos).
- Capas conmutables: límites municipales, veredas/barrios, puestos de votación, mapa de calor.
- Cambio de mapa base (calles / satélite / minimalista).
- Los conteos se muestran **solo** para territorios dentro del alcance del usuario; los demás aparecen atenuados o sin dato.
- Registros sin zona o sin puesto asignado se agrupan en "Sin ubicar" y se muestran en un tablero de pendientes de calidad de datos.

### 3.3 Módulo de registro de personas (simpatizantes)

**Campos del registro:**

| Campo | Tipo | Notas |
|---|---|---|
| Nombres | texto | obligatorio |
| Apellidos | texto | obligatorio |
| Número de cédula | texto | obligatorio; cifrado + índice ciego (ver §6) |
| Teléfono | texto | opcional; cifrado |
| Correo | texto | opcional; cifrado |
| Municipio | FK territorio | obligatorio |
| Corregimiento / vereda / barrio | FK territorio | opcional al inicio |
| Dirección o referencia | texto | opcional; cifrado |
| Puesto de votación | FK puesto | opcional al inicio (se completa luego) |
| Mesa | entero | opcional al inicio |
| Candidato(s) al que apoya | FK candidato (N:M) | obligatorio al menos uno |
| Líder / referido por | FK usuario | opcional |
| Formulario físico | FK formulario | **obligatorio** para registros nuevos |
| Fecha de firma del formulario | fecha | obligatorio |
| Observaciones | texto | opcional |
| Estado | enum | `borrador`, `validado`, `observado`, `retirado` |

**Funciones:**

- Formulario de captura optimizado para digitación rápida (teclado, tabulación, autocompletado de municipio/zona/puesto).
- Subida de foto/escaneo del formulario (JPG/PNG/PDF), con visor integrado junto al formulario de captura (pantalla dividida: documento a la izquierda, campos a la derecha).
- Detección de duplicados en tiempo real por cédula (usando el índice ciego) con aviso al digitador y opción de "fusionar" o "marcar como duplicado".
- Validaciones: formato de cédula (solo numérica, longitud razonable), teléfono colombiano, campos obligatorios, coherencia territorial (el puesto debe pertenecer al municipio).
- Edición con historial de cambios (quién, cuándo, qué valor cambió).
- Cambio de estado a `retirado` y **borrado definitivo** (supresión) con registro de auditoría sin datos personales.
- Captura por lotes: un digitador abre un "lote" (por ejemplo, reunión del 12/10 en vereda X) y todos los registros quedan asociados al lote, facilitando revisión.

### 3.4 Módulo de importación de datos existentes

Dado que ya existe una base recopilada en reuniones, el sistema debe incluir un **asistente de importación**:

1. Carga de archivo CSV/XLSX.
2. Mapeo de columnas del archivo a campos del sistema (con plantilla descargable).
3. Normalización: mayúsculas/minúsculas, eliminación de espacios, limpieza de cédulas (puntos, comas, espacios), unificación de nombres de municipios (tabla de sinónimos).
4. Validación previa con reporte de errores por fila (cédula inválida, municipio no reconocido, duplicados dentro del archivo y contra la base).
5. Vista previa y confirmación antes de insertar ("importación en seco").
6. Importación transaccional por lotes; posibilidad de **revertir** una importación completa.
7. Registros importados sin formulario escaneado quedan marcados como `pendiente_evidencia` y aparecen en un tablero para ir vinculando los formularios físicos de forma gradual.
8. Fase posterior de completado: carga masiva de puestos y mesas (CSV: cédula → puesto, mesa) a medida que se vayan averiguando.

### 3.5 Módulo de catálogos territoriales y puestos de votación

- **Territorios:** tabla jerárquica única (departamento → municipio → corregimiento/vereda/barrio) con geometría (polígonos) en PostGIS.
- Carga inicial de límites municipales (DANE, MGN) y, cuando existan, polígonos de veredas y barrios; mientras no existan, el nivel inferior puede manejarse como lista (sin polígono) con centroide o marcador.
- **Puestos de votación:** nombre, dirección, municipio, zona, coordenadas (punto), número de mesas.
- CRUD de catálogos con importación CSV; solo Superadministrador.
- Herramienta para asignar manualmente un punto geográfico a un puesto (clic en el mapa).

### 3.6 Módulo de candidatos y campañas

- Registro de campañas y candidatos: nombre, cargo (gobernación / alcaldía / asamblea), municipio (si aplica), color corporativo.
- Aislamiento por campaña: los datos de una campaña no son visibles para usuarios de otra (multi-tenant lógico por `campana_id`).
- Un simpatizante puede apoyar a varios candidatos de la misma campaña (por ejemplo, gobernación + alcaldía + asamblea).

### 3.7 Módulo de reportes y tableros

- Tablero general: total de apoyos, por municipio, por candidato, por cargo, evolución en el tiempo (capturas por día/semana).
- Tablero de calidad de datos: registros sin puesto, sin mesa, sin zona, sin evidencia, duplicados sospechosos.
- Tablero de productividad: capturas por digitador, por líder y por lote.
- Reportes agregados exportables (PDF/XLSX) **sin datos personales** (solo conteos).
- Exportación con datos personales: solo roles autorizados, con motivo obligatorio, marca de agua con usuario y fecha, límites de volumen y registro en auditoría.

### 3.8 Módulo de administración

- Gestión de usuarios, roles y asignación de territorios.
- Gestión de campañas, candidatos, catálogos.
- Parámetros del sistema (tiempo de sesión, tamaño de archivos, rangos de colores del mapa).
- Visor de auditoría con filtros (usuario, acción, fecha, entidad).
- Gestión de solicitudes de titulares: consulta, rectificación y supresión (flujo con estados y fecha límite para el seguimiento jurídico de Yhon).

### 3.9 Módulo de auditoría

Se registra (tabla de solo inserción, sin posibilidad de edición desde la aplicación):

- Inicios y cierres de sesión, intentos fallidos.
- Consulta de detalle de una persona.
- Creación, edición, cambio de estado y borrado de registros.
- Exportaciones (quién, qué filtros, cuántas filas, motivo).
- Cambios de permisos y asignaciones.
- Importaciones y reversiones.

Cada evento guarda: usuario, rol, IP, agente de usuario, fecha/hora (UTC), acción, entidad y referencia (ID, nunca datos personales en claro).

---

## 4. Arquitectura propuesta

### 4.1 Vista general

```
[Navegador del usuario]
        │ HTTPS (TLS 1.2+)
        ▼
[Nginx / reverse proxy + WAF básico + rate limiting]
        │
        ├──► [Aplicación web (backend)]  ──►  [PostgreSQL 16 + PostGIS]
        │            │                              ▲
        │            ├──► [Redis] (caché, colas, sesiones)
        │            ├──► [Workers de cola] (importaciones, exportaciones, tiles)
        │            └──► [Almacenamiento de archivos cifrado] (escaneos)
        │
        └──► [Servidor de teselas vectoriales / GeoJSON simplificado]
```

### 4.2 Stack tecnológico recomendado

| Capa | Tecnología | Justificación |
|---|---|---|
| Backend | **Laravel 11 (PHP 8.3)** *(alternativa: Node.js + NestJS)* | Productividad alta, autenticación, políticas de autorización, colas, migraciones y cifrado integrados; coherente con la experiencia previa en PHP |
| Base de datos | **PostgreSQL 16 + PostGIS** | Consultas espaciales, jerarquías con CTE recursivos, vistas materializadas, particionado |
| Caché / colas | **Redis** | Caché de conteos, colas de trabajos, rate limiting |
| Frontend | **Vue 3 + Vite + Tailwind** *(o Blade + Alpine/Livewire para simplificar)* | SPA ágil para el mapa y formularios de captura |
| Mapas | **MapLibre GL JS** (vectorial, libre) | Rendimiento con miles de elementos, estilos personalizados, sin costo de licencia |
| Mapa base | OpenStreetMap vía proveedor de teselas (MapTiler/Stadia) o teselas propias | Evaluar costo al escalar; opción de autoalojar |
| Geodatos | Teselas vectoriales (pg_tileserv / Martin) o GeoJSON simplificado por nivel | Evita cargar polígonos pesados en el navegador |
| Servidor web | **Nginx** + PHP-FPM | Estándar, rendimiento |
| Contenedores | **Docker + Docker Compose** (futuro: Kubernetes si se requiere) | Reproducibilidad y despliegue |
| CI/CD | GitHub Actions | Pruebas automáticas y despliegue controlado |
| Monitoreo | Prometheus + Grafana o servicio gestionado; logs centralizados | Detección temprana de fallas |

### 4.3 Despliegue

- **Producción:** servidor (VPS o nube) dedicado a la aplicación, **no hosting compartido ni cPanel**.
- Entornos separados: desarrollo, pruebas (staging con datos ficticios) y producción.
- **Nunca usar datos reales fuera de producción.**
- Región del servidor: preferiblemente Colombia o región con latencia baja; la decisión de ubicación de datos se coordina con el frente jurídico.
- Dominio propio con HTTPS (HSTS) y certificados automáticos.
- Acceso administrativo a servidores solo por SSH con llave y, de ser posible, VPN.

---

## 5. Modelo de datos (resumen)

### 5.1 Entidades principales

**`campanas`**: id, nombre, descripción, activa, creada_en.

**`candidatos`**: id, campana_id, nombre, cargo (`gobernacion`|`alcaldia`|`asamblea`), territorio_id (municipio, si aplica), color.

**`territorios`**: id, padre_id, tipo (`departamento`|`municipio`|`corregimiento`|`vereda`|`barrio`), nombre, codigo_divipola, geom (MultiPolygon, SRID 4326), centroide (Point).
Índices: GiST sobre `geom`, B-tree sobre `padre_id`, `tipo`.

**`puestos_votacion`**: id, territorio_id, nombre, direccion, geom (Point), total_mesas, codigo_registraduria (opcional).

**`simpatizantes`**: id (UUID), campana_id, nombres, apellidos, cedula_cifrada, cedula_hash (índice ciego), telefono_cifrado, correo_cifrado, direccion_cifrada, territorio_id, puesto_id, mesa, lider_id, formulario_id, lote_id, estado, creado_por, creado_en, actualizado_en.
Índice único: (`campana_id`, `cedula_hash`).

**`simpatizante_candidato`**: simpatizante_id, candidato_id.

**`formularios`**: id, consecutivo, archivo_ruta (cifrado), hash_sha256 del archivo, fecha_firma, subido_por, subido_en.

**`lotes`**: id, descripcion, territorio_id, fecha, creado_por.

**`usuarios`**, **`roles`**, **`usuario_territorio`** (asignación de alcance), **`usuario_campana`**.

**`auditoria`**: id, usuario_id, rol, accion, entidad, entidad_id, ip, user_agent, detalle_json (sin datos personales), creado_en.

**`solicitudes_titular`**: id, tipo (`consulta`|`rectificacion`|`supresion`), simpatizante_id (o referencia), estado, fecha_recepcion, fecha_limite, resuelta_en, notas.

**`importaciones`**: id, archivo, estado, filas_total, filas_ok, filas_error, reversible_hasta, creado_por.

### 5.2 Vistas materializadas para conteos (rendimiento)

- `mv_conteo_territorio` (campana_id, candidato_id, territorio_id, total).
- `mv_conteo_puesto_mesa` (campana_id, puesto_id, mesa, total).

Se refrescan por trabajo programado (cada pocos minutos) o por evento tras capturas/importaciones. El mapa consulta estas vistas, **no** cuenta filas de `simpatizantes` en cada clic.

### 5.3 Consulta jerárquica

Los descendientes de un territorio se obtienen con CTE recursivo sobre `territorios.padre_id`, o con una columna auxiliar `ruta` (ltree) para consultas más rápidas.

---

## 6. Seguridad y protección de datos (requisitos técnicos)

1. **Cifrado en tránsito:** TLS 1.2+ en todo el sistema; HSTS.
2. **Cifrado en reposo:**
   - Cifrado a nivel de campo (AES-256-GCM) para cédula, teléfono, correo y dirección.
   - Claves de cifrado gestionadas fuera de la base de datos (variables de entorno protegidas o gestor de secretos/KMS), con rotación documentada.
   - Cifrado de disco del servidor y de las copias de seguridad.
3. **Índice ciego de la cédula:** HMAC-SHA256 con clave secreta para búsqueda exacta y detección de duplicados sin descifrar toda la base.
4. **Archivos de formularios:** almacenados fuera del directorio público, servidos solo mediante URL firmadas de corta duración y con verificación de permisos.
5. **Autorización:** políticas por rol y territorio aplicadas en cada consulta (global scopes) y pruebas automatizadas que verifiquen que un usuario no puede ver datos de otro territorio.
6. **Protección contra ataques comunes:** CSRF, XSS (escape de salida y CSP estricta), inyección SQL (consultas parametrizadas / ORM), carga segura de archivos (validación de tipo real, límite de tamaño, antivirus opcional), cabeceras de seguridad.
7. **Mínimo privilegio en base de datos:** el usuario de la aplicación no es superusuario; usuarios distintos para migraciones y para lectura de reportes.
8. **Copias de seguridad:** diarias, cifradas, con retención definida y prueba de restauración periódica; copia fuera del servidor principal.
9. **Retención y borrado:** tras la fecha de cierre del proceso electoral definida por el responsable, política configurable de eliminación o anonimización de datos, ejecutada por tarea controlada y auditada.
10. **Exportaciones:** limitadas por rol, con motivo, marca de agua y auditoría; no se permite exportar la base completa a roles territoriales.
11. **Registro de incidentes:** procedimiento para detectar, contener y notificar incidentes de seguridad, con bitácora técnica.
12. **Datos sensibles en logs:** prohibido registrar cédulas, nombres o teléfonos en logs de aplicación o de errores.
13. **Pruebas de seguridad:** revisión de dependencias (Dependabot/composer audit), análisis estático y prueba de penetración básica antes de la puesta en producción.

---

## 7. Requisitos no funcionales

### 7.1 Escala y rendimiento

| Aspecto | Meta |
|---|---|
| Registros de personas | Diseñado para **1.000.000+** (holgura sobre el censo electoral de Caldas) |
| Usuarios activos | 500 registrados; **150 concurrentes** en picos |
| Carga del mapa departamental | < 2 s en conexión 4G |
| Cambio de nivel (drill-down) | < 1 s |
| Búsqueda por cédula | < 500 ms |
| Importación | 100.000 filas en < 10 min (en segundo plano) |
| Disponibilidad objetivo | 99,5 % mensual, con mayor exigencia en semanas previas a las elecciones |

Estrategias: vistas materializadas, caché en Redis, teselas vectoriales, paginación y carga diferida, índices espaciales y compuestos, colas para tareas pesadas, posibilidad de réplica de lectura para reportes.

### 7.2 Usabilidad

- Interfaz en español.
- **Diseño responsive** (celular, tablet, escritorio); en campo, líderes usarán principalmente celular.
- Accesibilidad básica (contraste, tamaños de fuente, navegación por teclado en formularios de captura).
- Capturas con pocos clics; atajos de teclado para digitadores.
- Mapa usable en pantallas pequeñas (paneles colapsables).

### 7.3 Mantenibilidad

- Código versionado en Git, ramas y revisión de cambios.
- Migraciones de base de datos versionadas.
- Pruebas automatizadas (unitarias, de integración y de autorización) con cobertura mínima del 70 % en la lógica crítica.
- Documentación técnica y manual de usuario por rol.

### 7.4 Observabilidad

- Logs estructurados (sin datos personales), métricas de rendimiento, alertas por caída, errores 5xx, uso de disco y demora de colas.

---

## 8. Interfaz de usuario (pantallas principales)

1. **Inicio de sesión / doble factor.**
2. **Mapa** (pantalla principal): mapa + panel lateral + filtros + migas de pan.
3. **Listado de personas** con filtros, búsqueda y paginación (según permisos).
4. **Ficha de persona** (detalle, historial, visor del formulario).
5. **Captura de formulario** (vista dividida documento/campos, lote activo).
6. **Importación** (asistente de 5 pasos: carga, mapeo, validación, vista previa, confirmación).
7. **Tableros** (general, calidad de datos, productividad).
8. **Administración** (usuarios, roles, territorios, campañas, candidatos, catálogos).
9. **Auditoría.**
10. **Solicitudes de titulares.**

---

## 9. API interna (resumen de endpoints)

Prefijo `/api/v1`, autenticación por sesión (SPA) con protección CSRF.

| Método | Ruta | Descripción |
|---|---|---|
| GET | `/mapa/resumen?nivel=&territorio_id=&candidato_id=` | Conteos por hijos del territorio |
| GET | `/mapa/puestos?territorio_id=` | Puestos con conteo |
| GET | `/mapa/puestos/{id}/mesas` | Conteo por mesa |
| GET | `/simpatizantes` | Listado filtrado y paginado (según alcance) |
| POST | `/simpatizantes` | Crear (requiere formulario) |
| GET | `/simpatizantes/{id}` | Detalle (auditado) |
| PUT | `/simpatizantes/{id}` | Editar (historial) |
| DELETE | `/simpatizantes/{id}` | Supresión definitiva (auditada) |
| POST | `/formularios` | Subir escaneo |
| POST | `/importaciones` | Iniciar importación (en seco / real) |
| POST | `/importaciones/{id}/revertir` | Revertir |
| GET | `/reportes/*` | Reportes agregados |
| POST | `/exportaciones` | Solicitar exportación (motivo obligatorio) |
| GET | `/auditoria` | Consulta de eventos |
| CRUD | `/usuarios`, `/territorios`, `/puestos`, `/candidatos`, `/campanas` | Administración |

---

## 10. Reglas de negocio clave

1. No se crea un simpatizante nuevo sin formulario físico asociado (excepto importaciones marcadas `pendiente_evidencia`).
2. Un mismo número de cédula no puede existir dos veces dentro de la misma campaña.
3. El puesto de votación debe pertenecer al municipio del registro.
4. Un usuario solo ve y modifica registros dentro de su territorio y campaña asignados.
5. Toda consulta de detalle, edición, exportación o borrado queda auditada.
6. El borrado definitivo elimina datos personales y el archivo del formulario, conservando solo un registro de auditoría sin datos personales.
7. Los conteos agregados del mapa no revelan datos personales y pueden verse a nivel de alcance asignado.
8. Las importaciones son transaccionales y reversibles dentro de un plazo definido.

---

## 11. Plan de implementación por fases

| Fase | Contenido | Resultado |
|---|---|---|
| **0. Preparación** | Definir campañas y candidatos, depurar la base existente en Excel, obtener límites DANE de municipios, definir infraestructura | Insumos listos |
| **1. MVP** | Autenticación + 2FA, roles y territorios, importación de la base actual, captura con formulario escaneado, mapa Nivel 0 y 1 (departamento y municipio), auditoría básica | Sistema usable para el equipo núcleo |
| **2. Profundización territorial** | Veredas/barrios/corregimientos, puestos y mesas, carga masiva de puestos, mapa niveles 2–4, tablero de calidad | Cobertura jerárquica completa |
| **3. Escala departamental** | Roles de coordinadores y líderes, vistas materializadas, caché, pruebas de carga, exportaciones controladas, solicitudes de titulares | Operación con muchos usuarios |
| **4. Endurecimiento** | Prueba de penetración, plan de respaldo y recuperación, monitoreo y alertas, manuales | Listo para producción a gran escala |
| **5. Mejoras** | PWA / captura sin conexión, reportes avanzados, plantillas de informes | Evolución continua |

---

## 12. Criterios de aceptación

1. Un usuario con rol de coordinador municipal **no puede** ver ni por la interfaz ni por la API registros de otro municipio (prueba automatizada).
2. Desde el mapa departamental se puede llegar a la lista de personas de una mesa en no más de 5 clics, con conteos coherentes en cada nivel.
3. Toda persona nueva tiene su formulario escaneado visible desde su ficha.
4. La importación de 50.000 filas genera reporte de errores y puede revertirse.
5. Ningún dato personal aparece en claro en la base de datos, en logs ni en copias de seguridad.
6. El mapa departamental carga en menos de 2 segundos con 500.000 registros en la base.
7. Todas las acciones sensibles aparecen en la auditoría.
8. Se puede restaurar una copia de seguridad en un entorno de prueba con éxito.
9. La supresión de un registro elimina datos personales y archivo asociado.

---

## 13. Riesgos técnicos y mitigaciones

| Riesgo | Impacto | Mitigación |
|---|---|---|
| Datos geográficos incompletos (veredas/barrios) | Mapa menos detallado | Iniciar por municipio y puesto; completar polígonos por etapas; nivel inferior en lista |
| Filtración de la base | Muy alto | Cifrado por campo, mínimo privilegio, 2FA, auditoría, exportaciones controladas, pruebas de seguridad |
| Mala calidad de datos importados | Conteos inexactos | Normalización, validación previa, tablero de calidad |
| Caída del servicio en semanas críticas | Alto | Monitoreo, respaldos, plan de recuperación, pruebas de carga |
| Abuso interno (exportar la base) | Alto | Roles estrictos, marca de agua, límites, auditoría |
| Crecimiento no previsto | Medio | Arquitectura con colas, caché, réplicas y escalado horizontal |
| Dependencia de un solo desarrollador | Medio | Documentación, código versionado, scripts de despliegue reproducibles |

---

## 14. Decisiones pendientes

1. ¿Una sola campaña o varias campañas aisladas desde el inicio? (el diseño ya soporta varias).
2. ¿Dónde se alojará el servidor (proveedor y país)? Coordinar con el frente jurídico.
3. Lista definitiva de roles y quiénes ocuparán cada uno.
4. Plazo de conservación y fecha de eliminación/anonimización de datos tras el proceso electoral.
5. Fuente de cartografía de veredas y barrios (DANE, IGAC, Gobernación, alcaldías, OpenStreetMap).
6. Proveedor de teselas de mapa base o autoalojamiento.
7. Formato final del formulario físico (para alinear campos con la captura y numerar consecutivos).

---

## 15. Glosario

- **Drill-down:** navegación de lo general a lo particular (departamento → municipio → zona → puesto → mesa).
- **PostGIS:** extensión de PostgreSQL para datos geográficos.
- **Índice ciego:** hash con clave secreta que permite buscar un valor cifrado sin descifrarlo.
- **Vista materializada:** consulta precalculada y almacenada para acelerar reportes.
- **Teselas vectoriales:** fragmentos de mapa en formato vectorial que se cargan según el zoom.
- **TOTP:** contraseña de un solo uso basada en tiempo (apps como Google Authenticator).
- **DIVIPOLA:** codificación oficial de municipios y centros poblados de Colombia (DANE).
