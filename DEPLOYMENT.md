# Guía de Despliegue — Mapa Electoral Caldas

## Requisitos del servidor

| Componente | Versión mínima |
|---|---|
| PHP | 8.3 |
| PostgreSQL | 16 con PostGIS 3.4 |
| Redis | 7 |
| Nginx | 1.24 |
| Node.js | 20 (solo build frontend) |
| Docker | 24 (opcional) |

## Instalación con Docker (recomendado)

```bash
# 1. Clonar el repositorio
git clone <url-repo>
cd mapa-electoral

# 2. Copiar y editar variables de entorno
cp backend/.env.example backend/.env
# Editar backend/.env con valores de producción

# 3. Generar claves de cifrado
make artisan CMD="mapa:generate-encryption-keys"

# 4. Levantar contenedores
make up

# 5. Instalar dependencias PHP
make composer

# 6. Ejecutar migraciones y seeders
make artisan CMD="migrate --seed"

# 7. Generar clave de aplicación
make artisan CMD="key:generate"

# 8. Compilar frontend
make fe-install
make fe-build

# 9. Limpiar caché de configuración
make artisan CMD="optimize"
```

## Variables de entorno críticas

```env
# OBLIGATORIO generar con: php artisan mapa:generate-encryption-keys
FIELD_ENCRYPTION_KEY=<base64-32-bytes>
CEDULA_HASH_KEY=<base64-32-bytes>

# Base de datos
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=mapa_electoral
DB_USERNAME=mapa_electoral_user
DB_PASSWORD=<contraseña-segura-min-32-chars>

# Redis
REDIS_HOST=redis
REDIS_PASSWORD=<password-redis>

# Sesión (usar redis en producción)
SESSION_DRIVER=redis
SESSION_LIFETIME=20

# Cola (usar redis en producción)
QUEUE_CONNECTION=redis

# Caché (usar redis en producción)
CACHE_DRIVER=redis

# Entorno
APP_ENV=production
APP_DEBUG=false
APP_URL=https://mapaelectoral.caldas.gov.co

# 2FA
GOOGLE2FA_ENABLED=true
```

## Checklist antes de producción

### Seguridad
- [ ] `APP_DEBUG=false` en producción
- [ ] `APP_ENV=production`
- [ ] Claves `FIELD_ENCRYPTION_KEY` y `CEDULA_HASH_KEY` generadas (no las del ejemplo)
- [ ] Contraseña de base de datos segura (mínimo 32 caracteres, aleatoria)
- [ ] HTTPS con certificado válido (Let's Encrypt o corporativo)
- [ ] HSTS habilitado en Nginx
- [ ] Ejecutar `php artisan optimize` para compilar configuración
- [ ] Verificar 2FA obligatorio para Superadministrador y Admin de Campaña
- [ ] Revisar permisos de directorios de storage (755 o 750)
- [ ] Asegurarse de que `storage/` no sea accesible directamente desde web

### Base de datos
- [ ] Usuario de la aplicación NO tiene privilegios de superusuario
- [ ] Usuario separado para migraciones (solo en deployment, no en runtime)
- [ ] Backups automáticos diarios configurados y probados
- [ ] Prueba de restauración realizada
- [ ] `VACUUM ANALYZE` programado semanalmente

### Monitoreo
- [ ] Logs de Nginx y PHP-FPM revisados
- [ ] Alerta por errores 5xx configurada
- [ ] Alerta por disco > 80% configurada
- [ ] Health check `/up` retorna 200

### Funcional
- [ ] Migraciones ejecutadas: `php artisan migrate --force`
- [ ] Seeders ejecutados: `php artisan db:seed`
- [ ] Vistas materializadas creadas y refrescadas
- [ ] Scheduler corriendo (cron o Docker)
- [ ] Queue worker corriendo
- [ ] Claves de cifrado verificadas: `php artisan mapa:test-encryption`

## Comandos de mantenimiento frecuentes

```bash
# Refrescar vistas materializadas manualmente
php artisan mapa:refresh-views

# Verificar usuarios sin 2FA (roles críticos)
php artisan mapa:enforce-2fa

# Limpiar auditoría antigua (>2 años, cron mensual)
php artisan auditoria:limpiar --dias=730 --auto

# Rotar claves de cifrado
php artisan mapa:rotate-encryption-keys

# Ver permisos de un rol
php artisan mapa:list-permissions --rol="Coordinador Municipal"

# Crear usuario inicial (Superadministrador)
php artisan mapa:create-user
```

## Backup y recuperación

```bash
# Backup completo
pg_dump -Fc -U mapa_electoral_user mapa_electoral > backup_$(date +%Y%m%d).dump

# Backup solo estructura
pg_dump -s -U mapa_electoral_user mapa_electoral > schema_$(date +%Y%m%d).sql

# Restaurar
pg_restore -U mapa_electoral_user -d mapa_electoral backup_20261020.dump

# Backup de formularios escaneados
tar -czf formularios_$(date +%Y%m%d).tar.gz backend/storage/app/formularios/
```

## Arquitectura de contenedores

```
Internet → Nginx (puerto 443/80)
                  │
         ┌────────┴────────┐
         │                 │
    PHP-FPM (app)    Static files (Vue build)
         │
   ┌─────┴─────┐
PostgreSQL   Redis
(puerto 5432) (puerto 6379)
```

## Escalado horizontal

Para soportar más de 150 usuarios concurrentes:

1. **Réplica de lectura PostgreSQL** — conectar reportes y estadísticas al secondary
2. **Redis Sentinel o Cluster** — para alta disponibilidad de caché/sesiones
3. **Múltiples instancias PHP-FPM** tras Nginx load balancer
4. **CDN para assets estáticos** (archivos Vue compilados)
