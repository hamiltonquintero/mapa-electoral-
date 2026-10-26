<?php

namespace App\Console\Commands;

use App\Models\Simpatizante;
use App\Models\Formulario;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RotateEncryptionKeys extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mapa:rotate-encryption-keys
                            {--old-field-key= : Clave anterior de cifrado de campos}
                            {--old-cedula-key= : Clave anterior de hash de cédulas}
                            {--batch-size=100 : Tamaño del lote para procesamiento}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rotar claves de cifrado (re-cifrar datos existentes con nuevas claves)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!$this->option('old-field-key') || !$this->option('old-cedula-key')) {
            $this->error('Debe proporcionar las claves antiguas con --old-field-key y --old-cedula-key');
            return 1;
        }

        $this->warn('ROTACIÓN DE CLAVES DE CIFRADO');
        $this->warn('Este proceso re-cifrará todos los datos sensibles con las nuevas claves.');
        $this->newLine();

        if (!$this->confirm('¿Desea continuar?')) {
            $this->info('Operación cancelada.');
            return 0;
        }

        $batchSize = (int) $this->option('batch-size');
        $oldFieldKey = $this->option('old-field-key');
        $oldCedulaKey = $this->option('old-cedula-key');
        $newFieldKey = config('encryption.field_key');
        $newCedulaKey = config('encryption.cedula_hash_key');

        if (empty($newFieldKey) || empty($newCedulaKey)) {
            $this->error('Las nuevas claves no están configuradas en .env');
            return 1;
        }

        DB::beginTransaction();

        try {
            // Rotar simpatizantes
            $this->info('Rotando claves de simpatizantes...');
            $this->rotateSimpatizantes($oldFieldKey, $oldCedulaKey, $newFieldKey, $newCedulaKey, $batchSize);

            // Rotar formularios
            $this->info('Rotando claves de formularios...');
            $this->rotateFormularios($oldFieldKey, $newFieldKey, $batchSize);

            DB::commit();

            $this->newLine();
            $this->info('✓ Rotación de claves completada exitosamente');
            $this->newLine();

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Error durante la rotación: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    /**
     * Rotar claves de simpatizantes.
     */
    protected function rotateSimpatizantes($oldFieldKey, $oldCedulaKey, $newFieldKey, $newCedulaKey, $batchSize)
    {
        $total = Simpatizante::count();
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        Simpatizante::chunk($batchSize, function ($simpatizantes) use ($oldFieldKey, $oldCedulaKey, $newFieldKey, $newCedulaKey, $bar) {
            foreach ($simpatizantes as $simpatizante) {
                // Descifrar con clave antigua
                $cedula = $this->decryptWithKey($simpatizante->cedula_cifrada, $oldFieldKey);
                $telefono = $simpatizante->telefono_cifrado ? $this->decryptWithKey($simpatizante->telefono_cifrado, $oldFieldKey) : null;
                $correo = $simpatizante->correo_cifrado ? $this->decryptWithKey($simpatizante->correo_cifrado, $oldFieldKey) : null;
                $direccion = $simpatizante->direccion_cifrada ? $this->decryptWithKey($simpatizante->direccion_cifrada, $oldFieldKey) : null;

                // Cifrar con clave nueva
                $simpatizante->cedula_cifrada = $this->encryptWithKey($cedula, $newFieldKey);
                $simpatizante->cedula_hash = $this->generateHashWithKey($cedula, $newCedulaKey);
                
                if ($telefono) {
                    $simpatizante->telefono_cifrado = $this->encryptWithKey($telefono, $newFieldKey);
                }
                if ($correo) {
                    $simpatizante->correo_cifrado = $this->encryptWithKey($correo, $newFieldKey);
                }
                if ($direccion) {
                    $simpatizante->direccion_cifrada = $this->encryptWithKey($direccion, $newFieldKey);
                }

                $simpatizante->saveQuietly(); // Sin disparar eventos
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
    }

    /**
     * Rotar claves de formularios.
     */
    protected function rotateFormularios($oldFieldKey, $newFieldKey, $batchSize)
    {
        $total = Formulario::count();
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        Formulario::chunk($batchSize, function ($formularios) use ($oldFieldKey, $newFieldKey, $bar) {
            foreach ($formularios as $formulario) {
                $ruta = $this->decryptWithKey($formulario->archivo_ruta, $oldFieldKey);
                $formulario->archivo_ruta = $this->encryptWithKey($ruta, $newFieldKey);
                $formulario->saveQuietly();
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
    }

    /**
     * Descifrar con clave específica.
     */
    protected function decryptWithKey($encrypted, $key)
    {
        $data = base64_decode($encrypted);
        $iv = substr($data, 0, 16);
        $tag = substr($data, 16, 16);
        $ciphertext = substr($data, 32);

        return openssl_decrypt(
            $ciphertext,
            'aes-256-gcm',
            base64_decode($key),
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );
    }

    /**
     * Cifrar con clave específica.
     */
    protected function encryptWithKey($value, $key)
    {
        $iv = random_bytes(16);
        $ciphertext = openssl_encrypt(
            $value,
            'aes-256-gcm',
            base64_decode($key),
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        return base64_encode($iv . $tag . $ciphertext);
    }

    /**
     * Generar hash con clave específica.
     */
    protected function generateHashWithKey($value, $key)
    {
        return hash_hmac('sha256', $value, $key);
    }
}
