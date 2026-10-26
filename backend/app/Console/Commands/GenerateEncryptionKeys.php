<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GenerateEncryptionKeys extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mapa:generate-encryption-keys 
                            {--show : Display the keys instead of modifying files}
                            {--force : Force the operation to run when in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generar claves de cifrado para campos sensibles (AES-256 y HMAC)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Verificar entorno de producción
        if ($this->laravel->environment('production') && !$this->option('force')) {
            $this->error('Este comando es peligroso en producción. Use --force para ejecutar.');
            return 1;
        }

        // Generar clave para cifrado de campos (AES-256 requiere 256 bits = 32 bytes)
        $fieldKey = base64_encode(random_bytes(32));
        
        // Generar clave para hash ciego de cédulas (256 bits)
        $cedulaHashKey = base64_encode(random_bytes(32));

        if ($this->option('show')) {
            $this->newLine();
            $this->line('Claves generadas:');
            $this->newLine();
            $this->line("FIELD_ENCRYPTION_KEY={$fieldKey}");
            $this->line("CEDULA_HASH_KEY={$cedulaHashKey}");
            $this->newLine();
            $this->info('Copie estas claves a su archivo .env');
            return 0;
        }

        // Leer archivo .env
        $envPath = $this->laravel->environmentFilePath();
        $envContent = file_get_contents($envPath);

        // Verificar si las claves ya existen
        $hasFieldKey = Str::contains($envContent, 'FIELD_ENCRYPTION_KEY=');
        $hasCedulaKey = Str::contains($envContent, 'CEDULA_HASH_KEY=');

        if ($hasFieldKey || $hasCedulaKey) {
            if (!$this->confirm('Las claves de cifrado ya existen. ¿Desea regenerarlas? ADVERTENCIA: Los datos cifrados con las claves anteriores no podrán descifrarse.')) {
                $this->info('Operación cancelada.');
                return 0;
            }
        }

        // Actualizar o agregar claves
        if ($hasFieldKey) {
            $envContent = preg_replace(
                '/FIELD_ENCRYPTION_KEY=.*/',
                "FIELD_ENCRYPTION_KEY={$fieldKey}",
                $envContent
            );
        } else {
            $envContent .= "\nFIELD_ENCRYPTION_KEY={$fieldKey}";
        }

        if ($hasCedulaKey) {
            $envContent = preg_replace(
                '/CEDULA_HASH_KEY=.*/',
                "CEDULA_HASH_KEY={$cedulaHashKey}",
                $envContent
            );
        } else {
            $envContent .= "\nCEDULA_HASH_KEY={$cedulaHashKey}";
        }

        // Guardar archivo .env
        file_put_contents($envPath, $envContent);

        $this->newLine();
        $this->info('✓ Claves de cifrado generadas exitosamente');
        $this->newLine();
        $this->line('FIELD_ENCRYPTION_KEY: Generada (32 bytes, base64)');
        $this->line('CEDULA_HASH_KEY: Generada (32 bytes, base64)');
        $this->newLine();
        $this->warn('IMPORTANTE:');
        $this->warn('1. Respalde estas claves en un lugar seguro');
        $this->warn('2. No comparta estas claves en control de versiones');
        $this->warn('3. Si regenera las claves, los datos existentes no podrán descifrarse');
        $this->warn('4. En producción, considere usar un gestor de secretos (AWS KMS, HashiCorp Vault)');
        $this->newLine();

        return 0;
    }
}
