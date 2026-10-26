<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string encrypt(string $value, ?string $key = null)
 * @method static string|null decrypt(string $encrypted, ?string $key = null)
 * @method static string generateBlindIndex(string $value, ?string $key = null)
 * @method static bool looksEncrypted(string $value)
 * @method static array encryptFields(array $data, array $fields)
 * @method static array decryptFields(array $data, array $fields)
 * @method static string generateKey()
 * @method static array validateConfiguration()
 *
 * @see \App\Services\EncryptionService
 */
class Encryption extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'encryption';
    }
}
