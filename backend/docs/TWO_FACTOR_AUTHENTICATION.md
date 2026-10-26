# Autenticación de Dos Factores (2FA) - Mapa Electoral Caldas

## Resumen

El sistema implementa autenticación de dos factores (2FA) usando **TOTP** (Time-based One-Time Password) compatible con Google Authenticator, Authy, Microsoft Authenticator y otras apps similares.

---

## Características

### Algoritmo: TOTP (RFC 6238)

- **Base:** HMAC-SHA1
- **Intervalo:** 30 segundos
- **Ventana de validación:** 4 intervalos (±2 minutos)
- **Longitud del código:** 6 dígitos

### Obligatoriedad por Rol

**2FA Obligatorio:**
- Superadministrador
- Administrador de Campaña

**2FA Opcional:**
- Coordinador Municipal
- Líder de Zona
- Digitador
- Auditor

### Seguridad

- Secreto cifrado en base de datos con `Crypt::encryptString()`
- Rate limiting en login (5 intentos por minuto por IP)
- Bloqueo temporal tras 5 intentos fallidos (30 minutos)
- Auditoría completa de intentos de login y verificación 2FA
- Códigos de recuperación (8 códigos de un solo uso)

---

## Flujo de Autenticación

### Login Sin 2FA

```
1. POST /api/v1/auth/login
   { email, password }
   
2. ← { user, token }
```

### Login Con 2FA

```
1. POST /api/v1/auth/login
   { email, password }
   
2. ← { requires_2fa: true }

3. POST /api/v1/auth/verify-2fa
   { code: "123456" }
   
4. ← { user, token }
```

---

## API Endpoints

### Autenticación Principal

#### POST /api/v1/auth/login

Paso 1: Login con email y contraseña.

**Request:**
```json
{
  "email": "user@example.com",
  "password": "securepassword"
}
```

**Response (sin 2FA):**
```json
{
  "message": "Login exitoso",
  "user": {
    "id": 1,
    "nombres": "Juan",
    "apellidos": "Pérez",
    "email": "user@example.com",
    "nombre_completo": "Juan Pérez",
    "roles": ["Coordinador Municipal"],
    "permissions": ["simpatizante.ver", "simpatizante.crear"],
    "google2fa_enabled": false
  },
  "token": "1|abc123..."
}
```

**Response (con 2FA):**
```json
{
  "message": "Verificación 2FA requerida",
  "requires_2fa": true
}
```

**Errores:**
- `401` - Credenciales incorrectas
- `403` - Usuario inactivo o bloqueado
- `429` - Demasiados intentos

---

#### POST /api/v1/auth/verify-2fa

Paso 2: Verificar código 2FA.

**Request:**
```json
{
  "code": "123456"
}
```

**Response:**
```json
{
  "message": "Login exitoso",
  "user": { ... },
  "token": "1|abc123..."
}
```

**Errores:**
- `400` - No hay sesión 2FA pendiente
- `401` - Código inválido

---

#### POST /api/v1/auth/logout

Cerrar sesión y revocar token.

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
  "message": "Logout exitoso"
}
```

---

#### GET /api/v1/auth/me

Obtener información del usuario actual.

**Response:**
```json
{
  "user": {
    "id": 1,
    "nombres": "Juan",
    "apellidos": "Pérez",
    "email": "user@example.com",
    "nombre_completo": "Juan Pérez",
    "activo": true,
    "ultimo_acceso": "2024-01-20T10:30:00Z",
    "google2fa_enabled": true,
    "roles": ["Superadministrador"],
    "permissions": ["sistema.administrar", ...],
    "campanas": [{ "id": 1, "nombre": "Campaña 2024" }],
    "territorios": [{ "id": 10, "nombre": "Manizales", "tipo": "municipio" }]
  }
}
```

---

### Gestión de 2FA

#### GET /api/v1/2fa/status

Obtener estado de 2FA del usuario.

**Response:**
```json
{
  "enabled": true,
  "verified_at": "2024-01-15T10:00:00Z",
  "required": true
}
```

---

#### POST /api/v1/2fa/setup

Iniciar configuración de 2FA (generar QR).

**Response:**
```json
{
  "message": "Escanee el código QR con Google Authenticator",
  "secret": "JBSWY3DPEHPK3PXP",
  "qr_code_svg": "<svg>...</svg>",
  "qr_code_url": "otpauth://totp/...",
  "manual_entry_key": "JBSWY3DPEHPK3PXP"
}
```

---

#### POST /api/v1/2fa/enable

Habilitar 2FA (verificar código).

**Request:**
```json
{
  "code": "123456",
  "password": "userpassword"
}
```

**Response:**
```json
{
  "message": "2FA habilitado exitosamente",
  "recovery_codes": [
    "ABC1234567",
    "DEF8901234",
    ...
  ],
  "warning": "Guarde estos códigos de recuperación en un lugar seguro..."
}
```

**Errores:**
- `400` - 2FA ya habilitado o no iniciado
- `401` - Contraseña o código inválido

---

#### POST /api/v1/2fa/disable

Deshabilitar 2FA.

**Request:**
```json
{
  "password": "userpassword",
  "code": "123456"
}
```

**Response:**
```json
{
  "message": "2FA deshabilitado exitosamente"
}
```

**Errores:**
- `403` - 2FA es obligatorio para el rol (no se puede deshabilitar)
- `401` - Contraseña o código inválido

---

#### POST /api/v1/2fa/verify

Verificar código 2FA (sin habilitar, solo test).

**Request:**
```json
{
  "code": "123456"
}
```

**Response:**
```json
{
  "valid": true,
  "message": "Código válido"
}
```

---

#### POST /api/v1/2fa/recovery-codes/regenerate

Regenerar códigos de recuperación.

**Request:**
```json
{
  "password": "userpassword"
}
```

**Response:**
```json
{
  "recovery_codes": ["ABC1234567", ...],
  "warning": "Los códigos anteriores ya no son válidos"
}
```

---

## Configuración del Cliente

### Google Authenticator

1. Descargar app desde:
   - Android: Google Play Store
   - iOS: App Store

2. En la app, seleccionar "Escanear código QR" o "Introducir clave"

3. Escanear el QR proporcionado por `/2fa/setup`

4. Introducir código de 6 dígitos en `/2fa/enable`

### Entrada Manual

Si no puede escanear QR:

1. Seleccionar "Introducir clave" en la app
2. Usar `manual_entry_key` de `/2fa/setup`
3. Configurar:
   - **Cuenta:** Email del usuario
   - **Clave:** `manual_entry_key`
   - **Tipo:** Basado en tiempo
   - **Algoritmo:** SHA1
   - **Dígitos:** 6
   - **Periodo:** 30 segundos

---

## Middleware

### Check2FA

Verifica que el usuario haya completado la verificación 2FA en la sesión actual.

**Uso:**
```php
Route::middleware(['auth:sanctum', '2fa'])->group(function () {
    // Rutas protegidas
});
```

### Require2FA

Verifica que los usuarios con roles críticos tengan 2FA habilitado.

**Uso:**
```php
Route::middleware(['auth:sanctum', 'require.2fa'])->group(function () {
    // Rutas que requieren 2FA obligatorio
});
```

---

## Comandos Artisan

### Verificar Usuarios sin 2FA

```bash
# Ver usuarios con roles críticos sin 2FA
php artisan mapa:enforce-2fa

# Filtrar por rol específico
php artisan mapa:enforce-2fa --role="Superadministrador"

# Modo simulación (no envía notificaciones)
php artisan mapa:enforce-2fa --dry-run
```

---

## Auditoría

Todos los eventos relacionados con 2FA quedan registrados:

- `login_exitoso` - Login completado
- `login_fallido` - Intento fallido
- `login_pendiente_2fa` - Requiere verificación 2FA
- `2fa_fallido` - Código 2FA inválido
- `2fa_setup_iniciado` - Inicio de configuración
- `2fa_habilitado` - 2FA habilitado
- `2fa_deshabilitado` - 2FA deshabilitado
- `2fa_habilitacion_fallida` - Intento fallido de habilitar
- `2fa_codigos_regenerados` - Códigos de recuperación regenerados
- `usuario_bloqueado` - Usuario bloqueado por intentos fallidos

**Consulta de auditoría:**
```php
Auditoria::where('accion', 'LIKE', '%2fa%')
    ->orderBy('creado_en', 'desc')
    ->get();
```

---

## Seguridad

### Protección de Secretos

- Secretos cifrados con `Crypt::encryptString()`
- No se exponen en logs ni errores
- Solo accesibles por el propio usuario

### Rate Limiting

**Login:**
- 5 intentos por minuto por IP
- Bloqueo de 60 segundos tras exceder límite

**Cuenta:**
- 5 intentos fallidos = bloqueo de 30 minutos
- Reseteo automático tras login exitoso

### Ventana de Validación

- **Window:** 4 intervalos (±2 minutos)
- Tolera diferencias de reloj entre cliente y servidor
- Balance entre seguridad y usabilidad

---

## Códigos de Recuperación

### Generación

- 8 códigos por defecto
- 10 caracteres alfanuméricos
- Formato: `ABC1234567`

### Uso

Los códigos de recuperación permiten acceder si:
- Se pierde el dispositivo 2FA
- No funciona la app de autenticación
- Se necesita acceso de emergencia

**Importante:**
- Cada código es de un solo uso
- Se deben guardar en lugar seguro offline
- Regenerar si se pierden o usan todos

---

## Testing

### Test Manual

```bash
# 1. Configurar 2FA
curl -X POST http://localhost:8000/api/v1/2fa/setup \
  -H "Authorization: Bearer {token}"

# 2. Obtener código de Google Authenticator

# 3. Habilitar 2FA
curl -X POST http://localhost:8000/api/v1/2fa/enable \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{"code":"123456","password":"userpass"}'

# 4. Logout
curl -X POST http://localhost:8000/api/v1/auth/logout \
  -H "Authorization: Bearer {token}"

# 5. Login (requerirá 2FA)
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"userpass"}'
```

### Test Automatizado

```php
public function test_2fa_login_flow()
{
    $user = User::factory()->create([
        'google2fa_enabled' => true,
        'google2fa_secret' => Crypt::encryptString('SECRET'),
    ]);

    // Login inicial
    $response = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertJson(['requires_2fa' => true]);

    // Verificar 2FA
    $code = app(TwoFactorAuthService::class)
        ->getSecret($user);
    
    $validCode = (new Google2FA())->getCurrentOtp($code);

    $response = $this->postJson('/api/v1/auth/verify-2fa', [
        'code' => $validCode,
    ]);

    $response->assertOk()
        ->assertJsonStructure(['token', 'user']);
}
```

---

## Troubleshooting

### "Código 2FA inválido" persistente

**Causas:**
1. Reloj desincronizado entre servidor y cliente
2. Secreto corrupto en BD
3. Window de validación muy estrecho

**Soluciones:**
```bash
# Verificar hora del servidor
date

# Sincronizar hora (Linux)
sudo ntpdate pool.ntp.org

# Verificar window en config
grep "window" config/auth.php

# Regenerar secreto 2FA para el usuario
# (desde interfaz admin)
```

### Usuario no puede habilitar 2FA

**Verificar:**
1. Setup fue llamado primero
2. Password es correcto
3. Código es actual (generado en últimos 30s)

### 2FA no se requiere tras login

**Verificar:**
1. Middleware `2fa` está aplicado en rutas
2. Sesión `2fa_verified` está configurada
3. Usuario tiene `google2fa_enabled = true`

---

## Migración de Usuarios Existentes

```bash
# 1. Identificar usuarios con roles críticos sin 2FA
php artisan mapa:enforce-2fa --dry-run

# 2. Notificar a usuarios
php artisan mapa:enforce-2fa

# 3. Establecer fecha límite (ejemplo: 7 días)
# Crear notificación en la interfaz

# 4. Después de la fecha límite, aplicar Require2FA middleware
# en rutas sensibles
```

---

## Referencias

- [RFC 6238: TOTP](https://tools.ietf.org/html/rfc6238)
- [RFC 4226: HOTP](https://tools.ietf.org/html/rfc4226)
- [Google Authenticator](https://github.com/google/google-authenticator)
- [OWASP 2FA](https://cheatsheetseries.owasp.org/cheatsheets/Multifactor_Authentication_Cheat_Sheet.html)

---

Última actualización: 2024-01-01
