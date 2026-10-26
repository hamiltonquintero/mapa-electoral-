# Sistema Web de Georreferenciación de Apoyos Electorales — Caldas

Sistema multiusuario de producción para registrar, consultar y visualizar en un mapa interactivo a las personas que apoyan candidatos políticos en el departamento de Caldas (Colombia), organizados por municipio → barrio/vereda → puesto de votación → mesa.

**Responsable del producto:** Hamilton Quintero García  
**Responsable jurídico:** Yhon  
**Alcance:** 27 municipios del departamento de Caldas

---

## Estado de implementación

| # | Módulo | Estado |
|---|--------|--------|
| 1 | Estructura de directorios Laravel 11 | ✅ Completo |
| 2 | Docker (PostgreSQL+PostGIS, Redis, Nginx, PHP-FPM) | ✅ Completo |
| 3 | 22 migraciones de base de datos | ✅ Completo |
| 4 | 14 modelos Eloquent con relaciones | ✅ Completo |
| 5 | Sistema de cifrado AES-256-GCM | ✅ Completo |
| 6 | Autenticación con 2FA TOTP | ✅ Completo |
| 7 | Roles y permisos (6 roles, 30+ permisos) | ✅ Completo |
| 8 | Middleware de autorización territorial | ✅ Completo |
| 9 | API REST mapa interactivo (40+ endpoints) | ✅ Completo |
| 10 | Módulo captura de simpatizantes | ✅ Completo |
| 11 | Importación masiva CSV/XLSX | ✅ Completo |
| 12 | Vistas materializadas y estadísticas | ✅ Completo |
| 13 | Módulo de auditoría completo | ✅ Completo |
| 14 | Frontend Vue 3 + MapLibre GL JS | ✅ Completo |
| 15 | Interfaces de administración | ✅ Completo |
| 16 | Reportes y tableros | ✅ Completo |
| 17 | Seguridad: CSP, CORS, rate limiting | ✅ Completo |
| 18 | Tests automatizados | ✅ Completo |
| 19 | Documentación técnica y manual de usuario | ✅ Completo |

**Progreso: 19/19 tareas (100%)**

---

## Inicio rápido

```bash
# 1. Clonar repositorio
git clone <url-repositorio>
cd mapa-electoral

# 2. Configurar entorno
cp backend/.env.example backend/.env
# Editar backend/.env con tus valores

# 3. Generar claves de cifrado (OBLIGATORIO antes de arrancar)
make artisan CMD="mapa:generate-encryption-keys"

# 4. Levantar contenedores
make up

# 5. Preparar base de datos
make artisan CMD="key:generate"
make artisan CMD="migrate --seed"

# 6. Compilar y servir frontend (desarrollo)
make fe-install
make fe-dev
# → http://localhost:5173

# 7. Crear el primer Superadministrador
make artisan CMD="mapa:create-user"
```

## Estructura del proyecto

```
mapa-electoral/
├── backend/               # API Laravel 11 (PHP 8.3)
│   ├── app/
│   │   ├── Console/       # Comandos Artisan personalizados (10)
│   │   ├── Http/
│   │   │   ├── Controllers/Api/  # 14 controladores REST
│   │   │   ├── Middleware/       # 6 middlewares personalizados
│   │   │   └── Requests/        # Form Requests validados
│   │   ├── Models/        # 14 modelos Eloquent con relaciones
│   │   ├── Policies/      # 5 políticas de autorización
│   │   ├── Services/      # EncryptionService, TwoFactorAuthService, ImportacionService
│   │   └── Traits/        # HasEncryptedFields, AppliesUserScope
│   ├── config/            # Configuración (encryption, cors, mapaelectoral, etc.)
│   ├── database/
│   │   ├── factories/     # 5 factories para tests
│   │   ├── migrations/    # 22 migraciones (incl. PostGIS, vistas materializadas)
│   │   └── seeders/       # RolePermissionSeeder (6 roles, 30+ permisos)
│   ├── docs/              # 9 documentos técnicos
│   ├── routes/api.php     # 50+ endpoints API
│   └── tests/             # 7 test suites (Feature + Unit)
├── frontend/              # SPA Vue 3 + Vite
│   └── src/
│       ├── components/    # 25+ componentes reutilizables
│       ├── layouts/       # AppLayout (sidebar + header)
│       ├── router/        # Rutas + guards de auth/permisos
│       ├── services/      # Capa HTTP (Axios)
│       ├── stores/        # Pinia (auth, mapa, toast)
│       └── views/         # 15 vistas organizadas por módulo
├── docker/                # Dockerfiles y configs
├── docker-compose.yml
├── Makefile               # Comandos útiles
├── DEPLOYMENT.md          # Guía de despliegue a producción
└── MANUAL_USUARIO.md      # Manual por rol de usuario
```

## Stack tecnológico

### Backend
| Capa | Tecnología |
|------|-----------|
| Framework | Laravel 11 (PHP 8.3) |
| Base de datos | PostgreSQL 16 + PostGIS 3.4 |
| Caché / Colas / Sesiones | Redis 7 |
| Autenticación | Laravel Sanctum + TOTP 2FA |
| Autorización | Spatie Laravel Permission |
| Cifrado | AES-256-GCM + HMAC-SHA256 blind index |
| Servidor web | Nginx + PHP-FPM |
| Contenedores | Docker + Docker Compose |

### Frontend
| Capa | Tecnología |
|------|-----------|
| Framework | Vue 3 (Composition API) |
| Build | Vite 5 |
| Estilos | Tailwind CSS 3 |
| Estado | Pinia |
| Routing | Vue Router 4 |
| Mapa | MapLibre GL JS 4 |
| HTTP | Axios |
| Gráficas | Chart.js + vue-chartjs |
| Iconos | Heroicons |

## Seguridad

- **Cifrado en reposo:** AES-256-GCM para cédula, teléfono, correo y dirección
- **Blind index:** HMAC-SHA256 para búsqueda de cédulas sin descifrar
- **2FA TOTP:** Obligatorio para Superadministrador y Administrador de Campaña
- **Autorización territorial:** Filtros automáticos por territorio en cada consulta
- **Auditoría:** Registro inmutable de todas las acciones sensibles
- **Headers de seguridad:** CSP, HSTS, X-Frame-Options, etc.
- **Rate limiting:** 5 req/min en login, 120 req/min en API general
- **CORS:** Configurado para los orígenes del frontend

## Documentación técnica

| Documento | Contenido |
|-----------|-----------|
| [ENCRYPTION.md](backend/docs/ENCRYPTION.md) | Sistema AES-256-GCM y gestión de claves |
| [TWO_FACTOR_AUTHENTICATION.md](backend/docs/TWO_FACTOR_AUTHENTICATION.md) | TOTP, QR codes, recuperación |
| [ROLES_AND_PERMISSIONS.md](backend/docs/ROLES_AND_PERMISSIONS.md) | 6 roles, 30+ permisos, políticas |
| [TERRITORIAL_AUTHORIZATION.md](backend/docs/TERRITORIAL_AUTHORIZATION.md) | Scopes territoriales, middleware |
| [MAPA_API.md](backend/docs/MAPA_API.md) | 15 endpoints del mapa interactivo |
| [SIMPATIZANTES_API.md](backend/docs/SIMPATIZANTES_API.md) | 13 endpoints de simpatizantes |
| [IMPORTACION_API.md](backend/docs/IMPORTACION_API.md) | Asistente importación CSV/XLSX |
| [VISTAS_MATERIALIZADAS.md](backend/docs/VISTAS_MATERIALIZADAS.md) | Conteos pre-calculados, rendimiento |
| [AUDITORIA.md](backend/docs/AUDITORIA.md) | Sistema de auditoría, alertas, GDPR |
| [DEPLOYMENT.md](DEPLOYMENT.md) | Guía completa de despliegue a producción |
| [MANUAL_USUARIO.md](MANUAL_USUARIO.md) | Manual de uso por rol |

## Tests

```bash
# Ejecutar todos los tests
make artisan CMD="test"

# Solo tests unitarios
make artisan CMD="test --testsuite=Unit"

# Solo tests de Feature
make artisan CMD="test --testsuite=Feature"

# Con cobertura de código
make artisan CMD="test --coverage"
```

## Comandos Artisan útiles

```bash
# Cifrado
php artisan mapa:generate-encryption-keys  # Genera FIELD_ENCRYPTION_KEY y CEDULA_HASH_KEY
php artisan mapa:test-encryption           # Verifica el sistema de cifrado
php artisan mapa:rotate-encryption-keys   # Rota y re-cifra la base

# Usuarios
php artisan mapa:create-user              # Crear usuario interactivo
php artisan mapa:assign-role              # Asignar rol a usuario
php artisan mapa:list-permissions         # Ver permisos por rol

# Mantenimiento
php artisan mapa:refresh-views            # Refrescar vistas materializadas
php artisan mapa:enforce-2fa              # Verificar usuarios sin 2FA
php artisan auditoria:limpiar --dias=730  # Limpiar auditoría antigua

# Base de datos
php artisan migrate --seed                # Migrar y sembrar
```

## Licencia

Propietario — Todos los derechos reservados.  
Sistema de uso restringido al equipo de campaña autorizado.
