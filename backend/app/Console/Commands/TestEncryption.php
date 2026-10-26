<?php

namespace App\Console\Commands;

use App\Services\EncryptionService;
use Illuminate\Console\Command;

class TestEncryption extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mapa:test-encryption';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verificar configuración y funcionamiento del sistema de cifrado';

    /**
     * Execute the console command.
     */
    public function handle(EncryptionService $encryption)
    {
        $this->info('=== VERIFICACIÓN DEL SISTEMA DE CIFRADO ===');
        $this->newLine();

        // 1. Validar configuración
        $this->info('1. Validando configuración de claves...');
        $errors = $encryption->validateConfiguration();

        if (!empty($errors)) {
            $this->error('✗ Errores encontrados:');
            foreach ($errors as $error) {
                $this->line("  - {$error}");
            }
            $this->newLine();
            $this->warn('Ejecute: php artisan mapa:generate-encryption-keys');
            return 1;
        }

        $this->info('  ✓ Claves configuradas correctamente');
        $this->newLine();

        // 2. Probar cifrado de campo
        $this->info('2. Probando cifrado AES-256-GCM...');
        $testValue = 'Cédula de prueba: 1234567890';
        
        try {
            $encrypted = $encryption->encrypt($testValue);
            $this->line("  Valor original: {$testValue}");
            $this->line("  Valor cifrado: " . substr($encrypted, 0, 50) . '...');
            $this->line("  Longitud cifrado: " . strlen($encrypted) . ' caracteres');

            $decrypted = $encryption->decrypt($encrypted);
            
            if ($decrypted === $testValue) {
                $this->info('  ✓ Cifrado y descifrado funcionan correctamente');
            } else {
                $this->error('  ✗ Error: El descifrado no coincide con el original');
                return 1;
            }
        } catch (\Exception $e) {
            $this->error('  ✗ Error en cifrado: ' . $e->getMessage());
            return 1;
        }

        $this->newLine();

        // 3. Probar índice ciego
        $this->info('3. Probando índice ciego HMAC-SHA256...');
        $testCedula = '1234567890';
        
        try {
            $hash1 = $encryption->generateBlindIndex($testCedula);
            $hash2 = $encryption->generateBlindIndex($testCedula);
            
            $this->line("  Cédula: {$testCedula}");
            $this->line("  Hash: {$hash1}");
            
            if ($hash1 === $hash2) {
                $this->info('  ✓ Índice ciego es determinístico (misma entrada = mismo hash)');
            } else {
                $this->error('  ✗ Error: Los hashes no coinciden');
                return 1;
            }

            // Verificar que diferentes valores producen diferentes hashes
            $hash3 = $encryption->generateBlindIndex('0987654321');
            if ($hash1 !== $hash3) {
                $this->info('  ✓ Diferentes valores producen diferentes hashes');
            } else {
                $this->error('  ✗ Error: Diferentes valores producen el mismo hash');
                return 1;
            }

        } catch (\Exception $e) {
            $this->error('  ✗ Error en índice ciego: ' . $e->getMessage());
            return 1;
        }

        $this->newLine();

        // 4. Probar detección de datos cifrados
        $this->info('4. Probando detección de datos cifrados...');
        
        $plainText = 'texto sin cifrar';
        $cipherText = $encryption->encrypt('texto cifrado');
        
        if (!$encryption->looksEncrypted($plainText)) {
            $this->info('  ✓ Detecta correctamente texto sin cifrar');
        } else {
            $this->error('  ✗ Falso positivo: detecta texto plano como cifrado');
        }

        if ($encryption->looksEncrypted($cipherText)) {
            $this->info('  ✓ Detecta correctamente texto cifrado');
        } else {
            $this->error('  ✗ Falso negativo: no detecta texto cifrado');
        }

        $this->newLine();

        // 5. Probar cifrado de múltiples campos
        $this->info('5. Probando cifrado de múltiples campos...');
        
        $data = [
            'nombres' => 'Juan',
            'cedula' => '1234567890',
            'telefono' => '3001234567',
            'correo' => 'juan@example.com',
        ];

        $encrypted = $encryption->encryptFields($data, ['cedula', 'telefono', 'correo']);
        $decrypted = $encryption->decryptFields($encrypted, ['cedula', 'telefono', 'correo']);

        if ($decrypted === $data) {
            $this->info('  ✓ Cifrado y descifrado de múltiples campos correcto');
        } else {
            $this->error('  ✗ Error en cifrado de múltiples campos');
            return 1;
        }

        $this->newLine();

        // 6. Información de seguridad
        $this->info('6. Información de seguridad:');
        $this->line('  - Algoritmo: AES-256-GCM (AEAD)');
        $this->line('  - Tamaño de clave: 256 bits');
        $this->line('  - IV: Aleatorio por registro (128 bits)');
        $this->line('  - Tag de autenticación: 128 bits');
        $this->line('  - Índice ciego: HMAC-SHA256');

        $this->newLine();
        $this->info('=== ✓ TODAS LAS PRUEBAS PASARON EXITOSAMENTE ===');
        $this->newLine();

        $this->comment('Recomendaciones de seguridad:');
        $this->line('1. Respalde las claves en un gestor de secretos seguro');
        $this->line('2. No comparta las claves en repositorios git');
        $this->line('3. Rote las claves periódicamente en producción');
        $this->line('4. Use variables de entorno o gestores de secretos (AWS KMS, Vault)');
        $this->line('5. Implemente políticas de acceso mínimo a las claves');

        return 0;
    }
}
