# Sistema Web de Georreferenciación de Apoyos Electorales - Caldas

Sistema multiusuario para registrar, consultar y visualizar en un mapa interactivo a las personas que apoyan candidatos políticos en el departamento de Caldas, Colombia.

## Características Principales

- **Mapa Interactivo Jerárquico**: Navegación drill-down desde departamento hasta mesa de votación
- **Control de Acceso Estricto**: Roles y territorios con políticas de autorización granulares
- **Cifrado de Datos Sensibles**: AES-256-GCM para campos personales
- **Autenticación 2FA**: TOTP obligatorio para roles críticos
- **Auditoría Completa**: Registro de todas las acciones sensibles
- **Importación Masiva**: Con validación, normalización y reversión
- **Vistas Materializadas**: Para rendimiento con millones de registros
- **Geodatos PostGIS**: Consultas espaciales optimizadas

## Requisitos del Sistema

- PHP 8.3+
- PostgreSQL 16 + PostGIS
- Redis 7+
- Composer
- Node.js 20+ (para frontend)

## Stack Tecnológico

### Backend
- Laravel 11
- PostgreSQL 16 con PostGIS
- Redis (caché, colas, sesiones)
- Spatie Laravel Permission
- Google2FA

### Frontend (separado en /frontend)
- Vue 3
- Vite
- Tailwind CSS
- MapLibre GL JS

## Instalación

### 1. Clonar el repositorio

```bash
git clone <repository-url>
cd mapa-electoral
```

### 2. Configuración con Docker (recomendado)

```bash
docker-compose up -d
docker-compose exec app composer install
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan migrate --seed
```

### 3. Configuración manual

```bash
# Instalar dependencias
composer install

# Copiar archivo de entorno
cp .env.example .env

# Generar claves
php artisan key:generate
php artisan mapa:generate-encryption-keys

# Ejecutar migraciones
php artisan migrate --seed
```

## Configuración de Claves de Cifrado

El sistema requiere claves adicionales para cifrado de campos sensibles:

```bash
php artisan mapa:generate-encryption-keys
```

Esto genera:
- `FIELD_ENCRYPTION_KEY`: Para cifrado AES-256-GCM de datos personales
- `CEDULA_HASH_KEY`: Para índice ciego de cédulas (HMAC-SHA256)

## Roles del Sistema

| Rol | Descripción | Alcance |
|-----|-------------|---------|
| **Superadministrador** | Control total del sistema | Todo el departamento |
| **Administrador de Campaña** | Gestión de una campaña | Su campaña completa |
| **Coordinador Municipal** | Responsable de municipio(s) | Sus municipios asignados |
| **Líder de Zona** | Responsable de zona | Sus zonas asignadas |
| **Digitador** | Captura de formularios | Solo lo que captura |
| **Auditor** | Solo lectura | Según asignación |

## Sistema de Autorización Territorial

El sistema implementa **control de acceso automático** por territorio y campaña:

### Middleware Disponibles

- **`territory.scope`**: Filtra automáticamente por territorios asignados + descendientes
- **`campaign.scope`**: Filtra automáticamente por campañas asignadas
- **`audit`**: Registra acciones sensibles en tabla de auditoría

### Uso en Rutas

```php
// Aplicar filtros territoriales y de campaña
Route::middleware(['auth:sanctum', 'territory.scope', 'campaign.scope'])->group(function () {
    Route::get('/simpatizantes', [SimpatizanteController::class, 'index']);
});

// Auditar acciones de escritura
Route::middleware(['auth:sanctum', 'audit'])->group(function () {
    Route::post('/simpatizantes', [SimpatizanteController::class, 'store']);
    Route::put('/simpatizantes/{id}', [SimpatizanteController::class, 'update']);
});
```

### Uso en Controladores

```php
use App\Traits\AppliesUserScope;

class SimpatizanteController extends Controller
{
    use AppliesUserScope;

    public function index(Request $request)
    {
        $user = $request->user();
        
        // Método 1: Usar scope del modelo (recomendado)
        $simpatizantes = Simpatizante::accesiblePorUsuario($user)
            ->paginate(50);
        
        // Método 2: Usar trait en el controlador
        $query = $this->applyUserScope(Simpatizante::query(), $user);
        $simpatizantes = $query->paginate(50);
        
        return response()->json($simpatizantes);
    }
}
```

Ver documentación completa en: [`docs/TERRITORIAL_AUTHORIZATION.md`](docs/TERRITORIAL_AUTHORIZATION.md)

## Documentación Completa

El sistema incluye documentación exhaustiva en el directorio `/docs`:

- **[ENCRYPTION.md](docs/ENCRYPTION.md)** - Sistema de cifrado de campos sensibles
- **[TWO_FACTOR_AUTHENTICATION.md](docs/TWO_FACTOR_AUTHENTICATION.md)** - Configuración y uso de 2FA
- **[ROLES_AND_PERMISSIONS.md](docs/ROLES_AND_PERMISSIONS.md)** - Roles, permisos y políticas
- **[TERRITORIAL_AUTHORIZATION.md](docs/TERRITORIAL_AUTHORIZATION.md)** - Control de acceso territorial
- **[MAPA_API.md](docs/MAPA_API.md)** - API del mapa interactivo (15 endpoints)
- **[SIMPATIZANTES_API.md](docs/SIMPATIZANTES_API.md)** - API de simpatizantes (13 endpoints)
- **[IMPORTACION_API.md](docs/IMPORTACION_API.md)** - API de importación masiva (6 endpoints)
- **[VISTAS_MATERIALIZADAS.md](docs/VISTAS_MATERIALIZADAS.md)** - Vistas materializadas y rendimiento
- **[AUDITORIA.md](docs/AUDITORIA.md)** - Sistema de auditoría completo

## Estructura de Directorios

```
backend/
├── app/
│   ├── Console/          # Comandos Artisan
│   ├── Http/
│   │   ├── Controllers/  # Controladores API
│   │   ├── Middleware/   # Middleware personalizado
│   │   └── Requests/     # Form Requests
│   ├── Models/           # Modelos Eloquent
│   ├── Policies/         # Políticas de autorización
│   ├── Services/         # Lógica de negocio
│   └── Traits/           # Traits reutilizables
├── config/               # Archivos de configuración
├── database/
│   ├── migrations/       # Migraciones de BD
│   ├── seeders/          # Seeders
│   └── factories/        # Factories
├── routes/
│   ├── api.php          # Rutas API
│   └── web.php          # Rutas web
└── storage/
    └── app/
        └── formularios/  # Escaneos de formularios
```

## API Endpoints

Todos los endpoints requieren autenticación y están bajo el prefijo `/api/v1`.

### Autenticación
- `POST /auth/login` - Iniciar sesión
- `POST /auth/verify-2fa` - Verificar código 2FA
- `GET /auth/me` - Información del usuario actual
- `POST /auth/logout` - Cerrar sesión

### Mapa Interactivo
- `GET /mapa/resumen` - Conteos por territorio
- `GET /mapa/puestos` - Puestos de votación
- `GET /mapa/puestos/{id}/mesas` - Conteo por mesa

### Simpatizantes
- `GET /simpatizantes` - Listado (filtrado por alcance)
- `POST /simpatizantes` - Crear registro
- `GET /simpatizantes/{id}` - Detalle
- `PUT /simpatizantes/{id}` - Actualizar
- `DELETE /simpatizantes/{id}` - Eliminar (supresión)

### Importaciones
- `POST /importaciones` - Importar CSV/XLSX
- `POST /importaciones/{id}/revertir` - Revertir importación

### Reportes
- `GET /reportes/general` - Tablero general
- `GET /reportes/calidad-datos` - Calidad de datos
- `GET /reportes/productividad` - Productividad

### Auditoría
- `GET /auditoria` - Listar eventos con filtros
- `GET /auditoria/resumen` - Estadísticas y métricas
- `GET /auditoria/alertas` - Alertas de seguridad
- `POST /auditoria/exportar` - Exportar logs a CSV

## Seguridad

### Cifrado
- **En tránsito**: TLS 1.2+ obligatorio
- **En reposo**: AES-256-GCM para campos sensibles
- **Índice ciego**: HMAC-SHA256 para búsqueda sin descifrado

### Autenticación
- Contraseñas con Argon2id
- 2FA obligatorio para roles críticos
- Bloqueo tras intentos fallidos
- Sesiones con timeout de 20 minutos

### Autorización
- Políticas por rol y territorio
- Global scopes en cada consulta
- Auditoría de acciones sensibles

### Protecciones
- Rate limiting por IP y usuario
- CSRF protection
- XSS prevention (CSP estricta)
- SQL injection prevention (ORM)
- Validación de archivos

## Testing

```bash
# Ejecutar todos los tests
php artisan test

# Tests específicos
php artisan test --filter=SimpatizanteTest
php artisan test --filter=AuthorizationTest

# Con cobertura
php artisan test --coverage
```

## Comandos Artisan Personalizados

```bash
# Generar claves de cifrado
php artisan mapa:generate-encryption-keys

# Refrescar vistas materializadas
php artisan mapa:refresh-views

# Limpiar auditoría antigua (>2 años)
php artisan auditoria:limpiar --dias=730

# Ver ayuda de comandos
php artisan auditoria:limpiar --help

# Importar catálogos territoriales
php artisan mapa:import-territorios {archivo.csv}

# Importar puestos de votación
php artisan mapa:import-puestos {archivo.csv}
```

## Mantenimiento

### Copias de Seguridad

```bash
# Backup de base de datos
pg_dump -U mapa_electoral_user mapa_electoral | gzip > backup_$(date +%Y%m%d).sql.gz

# Backup de archivos
tar -czf formularios_$(date +%Y%m%d).tar.gz storage/app/formularios/
```

### Monitoreo

El sistema genera logs en:
- `storage/logs/laravel.log` - Log general
- Auditoría en tabla `auditoria`
- Métricas en Redis

## Variables de Entorno Importantes

```env
# Aplicación
APP_NAME="Mapa Electoral Caldas"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://mapaelectoral.com

# Base de datos
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_DATABASE=mapa_electoral
DB_USERNAME=mapa_electoral_user
DB_PASSWORD=<contraseña-segura>

# Cifrado (GENERAR CON COMANDO)
FIELD_ENCRYPTION_KEY=
CEDULA_HASH_KEY=

# 2FA
GOOGLE2FA_ENABLED=true

# Sesiones
SESSION_LIFETIME=20
SESSION_DRIVER=redis

# Mapa
MAP_CENTER_LAT=5.0689
MAP_CENTER_LNG=-75.5174
```

## Troubleshooting

### Error: "FIELD_ENCRYPTION_KEY no está configurada"
Ejecutar: `php artisan mapa:generate-encryption-keys`

### Error: "PostGIS extension not found"
Instalar PostGIS en PostgreSQL y verificar que el usuario tenga permisos.

### Sesiones no persisten
Verificar que Redis esté corriendo y configurado correctamente.

## Contribución

Este es un proyecto privado. Para contribuir, contactar al equipo de desarrollo.

## Licencia

Propietario - Todos los derechos reservados.

## Contacto

- **Responsable del producto**: Hamilton Quintero García
- **Responsable jurídico**: Yhon
