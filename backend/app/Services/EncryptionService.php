<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class EncryptionService
{
    /**
     * Algoritmo de cifrado.
     */
    private const CIPHER = 'aes-256-gcm';

    /**
     * Tamaño del IV en bytes.
     */
    private const IV_LENGTH = 16;

    /**
     * Tamaño del tag en bytes.
     */
    private const TAG_LENGTH = 16;

    /**
     * Cifrar un valor.
     *
     * @param string $value
     * @param string|null $key
     * @return string
     * @throws \RuntimeException
     */
    public function encrypt(string $value, ?string $key = null): string
    {
        $key = $key ?? config('encryption.field_key');

        if (empty($key)) {
            throw new \RuntimeException('Encryption key not configured');
        }

        try {
            // Generar IV aleatorio
            $iv = random_bytes(self::IV_LENGTH);

            // Cifrar usando AES-256-GCM
            $ciphertext = openssl_encrypt(
                $value,
                self::CIPHER,
                base64_decode($key),
                OPENSSL_RAW_DATA,
                $iv,
                $tag
            );

            if ($ciphertext === false) {
                throw new \RuntimeException('Encryption failed: ' . openssl_error_string());
            }

            // Retornar: IV + Tag + Ciphertext (todo en base64)
            return base64_encode($iv . $tag . $ciphertext);

        } catch (\Exception $e) {
            Log::error('Encryption error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw new \RuntimeException('Encryption failed: ' . $e->getMessage());
        }
    }

    /**
     * Descifrar un valor.
     *
     * @param string $encrypted
     * @param string|null $key
     * @return string|null
     */
    public function decrypt(string $encrypted, ?string $key = null): ?string
    {
        if (empty($encrypted)) {
            return null;
        }

        $key = $key ?? config('encryption.field_key');

        if (empty($key)) {
            throw new \RuntimeException('Encryption key not configured');
        }

        try {
            $data = base64_decode($encrypted, true);

            if ($data === false) {
                throw new \RuntimeException('Invalid base64 encoded data');
            }

            // Validar longitud mínima
            $minLength = self::IV_LENGTH + self::TAG_LENGTH;
            if (strlen($data) < $minLength) {
                throw new \RuntimeException('Encrypted data too short');
            }

            // Extraer IV, Tag y Ciphertext
            $iv = substr($data, 0, self::IV_LENGTH);
            $tag = substr($data, self::IV_LENGTH, self::TAG_LENGTH);
            $ciphertext = substr($data, self::IV_LENGTH + self::TAG_LENGTH);

            // Descifrar
            $plaintext = openssl_decrypt(
                $ciphertext,
                self::CIPHER,
                base64_decode($key),
                OPENSSL_RAW_DATA,
                $iv,
                $tag
            );

            if ($plaintext === false) {
                throw new \RuntimeException('Decryption failed: ' . openssl_error_string());
            }

            return $plaintext;

        } catch (\Exception $e) {
            Log::error('Decryption error', [
                'error' => $e->getMessage(),
                'encrypted_length' => strlen($encrypted ?? ''),
            ]);
            throw new \RuntimeException('Decryption failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Generar hash ciego para búsqueda.
     *
     * @param string $value
     * @param string|null $key
     * @return string
     */
    public function generateBlindIndex(string $value, ?string $key = null): string
    {
        $key = $key ?? config('encryption.cedula_hash_key');

        if (empty($key)) {
            throw new \RuntimeException('Blind index key not configured');
        }

        return hash_hmac('sha256', $value, $key);
    }

    /**
     * Verificar si un valor parece estar cifrado.
     *
     * @param string $value
     * @return bool
     */
    public function looksEncrypted(string $value): bool
    {
        // Un valor cifrado en base64 con IV+Tag+Ciphertext tiene mínimo ~60 caracteres
        if (strlen($value) < 50) {
            return false;
        }

        // Verificar que sea base64 válido
        $decoded = base64_decode($value, true);
        if ($decoded === false) {
            return false;
        }

        // Verificar longitud mínima del contenido decodificado
        return strlen($decoded) >= (self::IV_LENGTH + self::TAG_LENGTH);
    }

    /**
     * Cifrar múltiples campos de un array.
     *
     * @param array $data
     * @param array $fields
     * @return array
     */
    public function encryptFields(array $data, array $fields): array
    {
        foreach ($fields as $field) {
            if (isset($data[$field]) && !empty($data[$field])) {
                $data[$field] = $this->encrypt($data[$field]);
            }
        }

        return $data;
    }

    /**
     * Descifrar múltiples campos de un array.
     *
     * @param array $data
     * @param array $fields
     * @return array
     */
    public function decryptFields(array $data, array $fields): array
    {
        foreach ($fields as $field) {
            if (isset($data[$field]) && !empty($data[$field])) {
                $data[$field] = $this->decrypt($data[$field]);
            }
        }

        return $data;
    }

    /**
     * Generar clave de cifrado aleatoria.
     *
     * @return string
     */
    public function generateKey(): string
    {
        return base64_encode(random_bytes(32)); // 256 bits
    }

    /**
     * Validar que las claves estén configuradas correctamente.
     *
     * @return array
     */
    public function validateConfiguration(): array
    {
        $errors = [];

        $fieldKey = config('encryption.field_key');
        if (empty($fieldKey)) {
            $errors[] = 'FIELD_ENCRYPTION_KEY no está configurada';
        } elseif (strlen(base64_decode($fieldKey)) !== 32) {
            $errors[] = 'FIELD_ENCRYPTION_KEY debe ser de 256 bits (32 bytes)';
        }

        $cedulaKey = config('encryption.cedula_hash_key');
        if (empty($cedulaKey)) {
            $errors[] = 'CEDULA_HASH_KEY no está configurada';
        } elseif (strlen(base64_decode($cedulaKey)) !== 32) {
            $errors[] = 'CEDULA_HASH_KEY debe ser de 256 bits (32 bytes)';
        }

        return $errors;
    }
}
