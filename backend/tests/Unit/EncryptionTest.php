<?php

namespace Tests\Unit;

use App\Services\EncryptionService;
use Tests\TestCase;

class EncryptionTest extends TestCase
{
    private EncryptionService $svc;

    protected function setUp(): void
    {
        parent::setUp();
        // Usar clave de 32 bytes para tests
        config(['encryption.field_key'       => base64_encode(random_bytes(32))]);
        config(['encryption.cedula_hash_key' => base64_encode(random_bytes(32))]);

        $this->svc = new EncryptionService();
    }

    public function test_cifrado_y_descifrado_son_inversos(): void
    {
        $original  = 'Número de cédula: 1234567890';
        $cifrado   = $this->svc->encrypt($original);
        $descifrado = $this->svc->decrypt($cifrado);

        $this->assertEquals($original, $descifrado);
    }

    public function test_cada_cifrado_produce_valor_diferente(): void
    {
        $texto   = '1234567890';
        $cifrado1 = $this->svc->encrypt($texto);
        $cifrado2 = $this->svc->encrypt($texto);

        // IV aleatorio → valores distintos
        $this->assertNotEquals($cifrado1, $cifrado2);
        // Pero ambos descifran al mismo valor
        $this->assertEquals($texto, $this->svc->decrypt($cifrado1));
        $this->assertEquals($texto, $this->svc->decrypt($cifrado2));
    }

    public function test_indice_ciego_es_determinista(): void
    {
        $cedula = '1234567890';
        $hash1  = $this->svc->generateBlindIndex($cedula);
        $hash2  = $this->svc->generateBlindIndex($cedula);

        $this->assertEquals($hash1, $hash2);
    }

    public function test_indices_diferentes_para_cedulas_diferentes(): void
    {
        $hash1 = $this->svc->generateBlindIndex('1111111111');
        $hash2 = $this->svc->generateBlindIndex('2222222222');

        $this->assertNotEquals($hash1, $hash2);
    }

    public function test_descifrar_valor_invalido_lanza_excepcion(): void
    {
        $this->expectException(\Exception::class);
        $this->svc->decrypt('valor_no_cifrado_invalido');
    }

    public function test_cifrar_string_vacio(): void
    {
        $cifrado   = $this->svc->encrypt('');
        $descifrado = $this->svc->decrypt($cifrado);

        $this->assertEquals('', $descifrado);
    }
}
