<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Field Encryption Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración para el cifrado de campos sensibles usando AES-256-GCM.
    | La clave debe ser diferente a APP_KEY y rotarse periódicamente.
    |
    */

    'field_key' => env('FIELD_ENCRYPTION_KEY', '0ISss93dcasVrFxIJIkf17bmKi8WbIq2JN9p6UWMLxA='),
    
    'cedula_hash_key' => env('CEDULA_HASH_KEY', 'hU2S0xmtnZKH/H2Zzhx4WxAIk6AeImsdpw4kmV/zzHI='),
    
    'cipher' => 'AES-256-GCM',
    
    // Campos que deben ser cifrados
    'encrypted_fields' => [
        'cedula',
        'telefono',
        'correo',
        'direccion',
    ],

];
