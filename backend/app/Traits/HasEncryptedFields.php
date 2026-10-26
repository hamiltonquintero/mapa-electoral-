<?php

namespace App\Traits;

use Illuminate\Support\Facades\Crypt;

trait HasEncryptedFields
{
    /**
     * Cifrar un campo.
     */
    public function encryptField(string $value): string
    {
        $key = config('encryption.field_key');
        
        if (empty($key)) {
            throw new \RuntimeException('Field encryption key not configured');
        }

        // Generar IV aleatorio
        $iv = random_bytes(16);
        
        // Cifrar usando AES-256-GCM
        $ciphertext = openssl_encrypt(
            $value,
            'aes-256-gcm',
            base64_decode($key),
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($ciphertext === false) {
            throw new \RuntimeException('Encryption failed');
        }

        // Retornar: IV + Tag + Ciphertext (todo en base64)
        return base64_encode($iv . $tag . $ciphertext);
    }

    /**
     * Descifrar un campo.
     */
    public function decryptField(?string $encrypted): ?string
    {
        if (empty($encrypted)) {
            return null;
        }

        $key = config('encryption.field_key');
        
        if (empty($key)) {
            throw new \RuntimeException('Field encryption key not configured');
        }

        try {
            $data = base64_decode($encrypted);
            
            // Extraer IV (16 bytes), Tag (16 bytes) y Ciphertext
            $iv = substr($data, 0, 16);
            $tag = substr($data, 16, 16);
            $ciphertext = substr($data, 32);

            $plaintext = openssl_decrypt(
                $ciphertext,
                'aes-256-gcm',
                base64_decode($key),
                OPENSSL_RAW_DATA,
                $iv,
                $tag
            );

            if ($plaintext === false) {
                throw new \RuntimeException('Decryption failed');
            }

            return $plaintext;
        } catch (\Exception $e) {
            \Log::error('Decryption error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Generar hash ciego para búsqueda (HMAC-SHA256).
     */
    public function generateBlindIndex(string $value): string
    {
        $key = config('encryption.cedula_hash_key');
        
        if (empty($key)) {
            throw new \RuntimeException('Cedula hash key not configured');
        }

        return hash_hmac('sha256', $value, $key);
    }

    /**
     * Obtener campos cifrados definidos en el modelo.
     */
    protected function getEncryptedFields(): array
    {
        return $this->encrypted ?? [];
    }

    /**
     * Boot del trait - interceptar get/set.
     */
    protected static function bootHasEncryptedFields()
    {
        // Cifrar al guardar
        static::saving(function ($model) {
            foreach ($model->getEncryptedFields() as $field) {
                if (isset($model->attributes[$field]) && !empty($model->attributes[$field])) {
                    // Solo cifrar si no está ya cifrado (comprobar longitud base64)
                    if (!self::looksEncrypted($model->attributes[$field])) {
                        $model->attributes[$field] = $model->encryptField($model->attributes[$field]);
                    }
                }
            }
        });
    }

    /**
     * Verificar si un valor ya está cifrado.
     */
    protected static function looksEncrypted(string $value): bool
    {
        // Un valor cifrado en base64 con IV+Tag+Ciphertext tiene mínimo ~60 caracteres
        return strlen($value) > 50 && base64_decode($value, true) !== false;
    }
}
