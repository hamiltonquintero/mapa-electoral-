# Sistema de Cifrado - Mapa Electoral Caldas

## Resumen

El sistema implementa cifrado de campos sensibles usando **AES-256-GCM** (Authenticated Encryption with Associated Data) y un **índice ciego HMAC-SHA256** para búsqueda sin descifrado.

---

## Características

### 1. Cifrado AES-256-GCM

**Algoritmo:** Advanced Encryption Standard con Galois/Counter Mode
**Tamaño de clave:** 256 bits (32 bytes)
**IV:** 128 bits (16 bytes) aleatorio por registro
**Tag de autenticación:** 128 bits (16 bytes)

**Ventajas:**
- AEAD (Authenticated Encryption with Associated Data)
- Detecta manipulación de datos
- IV aleatorio previene ataques de análisis de patrones
- Estándar NIST, ampliamente aceptado

### 2. Índice Ciego HMAC-SHA256

**Algoritmo:** Hash-based Message Authentication Code con SHA-256
**Tamaño de clave:** 256 bits (32 bytes)
**Salida:** 256 bits (64 caracteres hex)

**Características:**
- Permite búsqueda exacta sin descifrar
- Deterministico (misma entrada = mismo hash)
- Resistente a rainbow tables por uso de clave secreta
- No reversible

---

## Configuración

### Generar Claves

```bash
# Generar nuevas claves
php artisan mapa:generate-encryption-keys

# Ver claves sin modificar archivo
php artisan mapa:generate-encryption-keys --show

# Forzar en producción (peligroso)
php artisan mapa:generate-encryption-keys --force
```

### Variables de Entorno

```env
# Clave para cifrado de campos (AES-256)
FIELD_ENCRYPTION_KEY=base64EncodedKey256Bits==

# Clave para índice ciego (HMAC)
CEDULA_HASH_KEY=base64EncodedKey256Bits==
```

**IMPORTANTE:**
- Las claves deben ser de 256 bits (32 bytes)
- Deben estar codificadas en base64
- Nunca incluir en control de versiones
- Respaldar en gestor de secretos seguro

---

## Uso

### En Modelos (Trait HasEncryptedFields)

```php
use App\Traits\HasEncryptedFields;

class Simpatizante extends Model
{
    use HasEncryptedFields;

    protected $encrypted = [
        'cedula_cifrada',
        'telefono_cifrado',
        'correo_cifrado',
        'direccion_cifrada',
    ];

    // Accessors automáticos
    public function getCedulaAttribute(): ?string
    {
        return $this->decryptField($this->cedula_cifrada);
    }

    // Mutators automáticos
    public function setCedulaAttribute(?string $value): void
    {
        if (!empty($value)) {
            $this->attributes['cedula_cifrada'] = $value;
            $this->attributes['cedula_hash'] = $this->generateBlindIndex($value);
        }
    }
}
```

### Usando el Servicio Directamente

```php
use App\Services\EncryptionService;

$encryption = app(EncryptionService::class);

// Cifrar
$encrypted = $encryption->encrypt('valor secreto');

// Descifrar
$decrypted = $encryption->decrypt($encrypted);

// Índice ciego
$hash = $encryption->generateBlindIndex('1234567890');

// Cifrar múltiples campos
$data = [
    'cedula' => '1234567890',
    'telefono' => '3001234567',
];
$encrypted = $encryption->encryptFields($data, ['cedula', 'telefono']);
```

### Usando la Facade

```php
use App\Facades\Encryption;

$encrypted = Encryption::encrypt('valor');
$decrypted = Encryption::decrypt($encrypted);
$hash = Encryption::generateBlindIndex('busqueda');
```

---

## Búsqueda por Índice Ciego

### Búsqueda de Cédula

```php
// Scope en el modelo
public function scopeBuscarPorCedula($query, string $cedula)
{
    $hash = $this->generateBlindIndex($cedula);
    return $query->where('cedula_hash', $hash);
}

// Uso
$simpatizante = Simpatizante::buscarPorCedula('1234567890')->first();
```

### Detección de Duplicados

```php
$cedula = '1234567890';
$hash = Encryption::generateBlindIndex($cedula);

$existe = Simpatizante::where('campana_id', $campanaId)
    ->where('cedula_hash', $hash)
    ->exists();
```

---

## Comandos Artisan

### Probar Sistema de Cifrado

```bash
php artisan mapa:test-encryption
```

Verifica:
- Configuración de claves
- Cifrado y descifrado AES-256-GCM
- Índice ciego HMAC-SHA256
- Detección de datos cifrados
- Cifrado de múltiples campos

### Rotar Claves

```bash
php artisan mapa:rotate-encryption-keys \
    --old-field-key=clave_anterior_field \
    --old-cedula-key=clave_anterior_cedula \
    --batch-size=100
```

Re-cifra todos los datos existentes con nuevas claves.

**ADVERTENCIA:** Proceso irreversible. Haga backup antes.

---

## Estructura del Dato Cifrado

### Formato

```
Base64( IV || Tag || Ciphertext )
```

- **IV** (16 bytes): Initialization Vector aleatorio
- **Tag** (16 bytes): Tag de autenticación GCM
- **Ciphertext** (variable): Texto cifrado

### Ejemplo

```
Valor original: "1234567890"
Valor cifrado:  "a3VpT2RxZm9nMjMxMjMxMjMxMjMxMjM0NTY3ODkwMTIzNDU2Nzg5MDEyMzQ1Njc4..."
Longitud:       ~80+ caracteres (depende del tamaño del valor original)
```

---

## Seguridad

### Buenas Prácticas

1. **Gestión de Claves:**
   - Usar gestor de secretos (AWS KMS, HashiCorp Vault, Azure Key Vault)
   - Rotar claves periódicamente (recomendado: cada 90 días)
   - Mantener claves anteriores para recuperación
   - Separar claves de cifrado y hash

2. **Almacenamiento:**
   - Nunca guardar claves en código fuente
   - Usar variables de entorno o gestor de secretos
   - Cifrar backups de base de datos
   - Registrar acceso a claves

3. **Acceso:**
   - Principio de mínimo privilegio
   - Auditar acceso a datos descifrados
   - Limitar exportaciones
   - Control de acceso por rol y territorio

4. **Operaciones:**
   - Validar claves al iniciar aplicación
   - Monitorear fallos de descifrado
   - Logs sin datos sensibles
   - Pruebas periódicas del sistema

### Validación

```bash
# En cada despliegue
php artisan mapa:test-encryption

# Verificar configuración
php artisan tinker
>>> app(EncryptionService::class)->validateConfiguration()
```

---

## Rendimiento

### Impacto

- **Cifrado:** ~0.1ms por campo
- **Descifrado:** ~0.1ms por campo
- **Índice ciego:** ~0.05ms por campo

### Optimizaciones

1. **Caché:**
   - Cachear datos descifrados en memoria durante la request
   - No cachear en Redis/Memcached (riesgo de exposición)

2. **Selectivo:**
   - Solo descifrar cuando sea necesario
   - Usar accessors para descifrado on-demand
   - Evitar `SELECT *` con campos cifrados

3. **Lotes:**
   - Procesar importaciones en chunks
   - Usar jobs de cola para exportaciones
   - Batch de 100-500 registros

---

## Recuperación ante Desastres

### Backup de Claves

```bash
# Guardar claves en archivo seguro
echo "FIELD_ENCRYPTION_KEY=$(grep FIELD_ENCRYPTION_KEY .env | cut -d '=' -f2)" > keys_backup_$(date +%Y%m%d).txt
echo "CEDULA_HASH_KEY=$(grep CEDULA_HASH_KEY .env | cut -d '=' -f2)" >> keys_backup_$(date +%Y%m%d).txt

# Cifrar el archivo de backup
gpg --symmetric --cipher-algo AES256 keys_backup_*.txt
```

### Restauración

```bash
# Descifrar backup
gpg --decrypt keys_backup_*.txt.gpg > keys_backup.txt

# Restaurar en .env
cat keys_backup.txt >> .env
```

### Pérdida de Claves

Si se pierden las claves de cifrado:

1. **Los datos cifrados son irrecuperables**
2. Opción A: Reimportar desde fuente original (formularios físicos)
3. Opción B: Solicitar nuevamente los datos a los titulares
4. No hay método de recuperación sin las claves

---

## Cumplimiento Legal

### RGPD / Ley 1581 (Colombia)

El sistema implementa:

✓ **Cifrado de datos personales** (Art. 4 Ley 1581)
✓ **Derecho de acceso** (consulta)
✓ **Derecho de rectificación** (edición con historial)
✓ **Derecho de supresión** (borrado definitivo)
✓ **Auditoría completa** (trazabilidad)
✓ **Seguridad técnica** (cifrado, control de acceso)

### Auditoría

Todas las operaciones sobre datos personales quedan registradas:
- Consulta de detalle
- Edición de campos
- Exportaciones
- Supresiones

---

## Troubleshooting

### Error: "Encryption key not configured"

```bash
php artisan mapa:generate-encryption-keys
php artisan config:clear
```

### Error: "Decryption failed"

Causas posibles:
- Clave incorrecta
- Dato corrupto
- Formato inválido

```bash
# Verificar configuración
php artisan mapa:test-encryption

# Ver logs
tail -f storage/logs/laravel.log | grep "Decryption error"
```

### Regenerar Claves con Datos Existentes

```bash
# 1. Backup de claves actuales
cp .env .env.backup

# 2. Generar nuevas claves
php artisan mapa:generate-encryption-keys --show > new_keys.txt

# 3. Actualizar .env con nuevas claves
# (editar manualmente o con script)

# 4. Rotar datos existentes
php artisan mapa:rotate-encryption-keys \
    --old-field-key=$(grep FIELD_ENCRYPTION_KEY .env.backup | cut -d '=' -f2) \
    --old-cedula-key=$(grep CEDULA_HASH_KEY .env.backup | cut -d '=' -f2)
```

---

## Referencias

- [NIST SP 800-38D: GCM Mode](https://nvlpubs.nist.gov/nistpubs/Legacy/SP/nistspecialpublication800-38d.pdf)
- [RFC 5116: AEAD Cipher Suites](https://tools.ietf.org/html/rfc5116)
- [RFC 2104: HMAC](https://tools.ietf.org/html/rfc2104)
- [OWASP Cryptographic Storage](https://cheatsheetseries.owasp.org/cheatsheets/Cryptographic_Storage_Cheat_Sheet.html)

---

Última actualización: 2024-01-01
