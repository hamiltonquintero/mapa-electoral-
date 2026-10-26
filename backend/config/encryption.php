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

    'field_key' => env('FIELD_ENCRYPTION_KEY'),
    
    'cedula_hash_key' => env('CEDULA_HASH_KEY'),
    
    'cipher' => 'AES-256-GCM',
    
    // Campos que deben ser cifrados
    'encrypted_fields' => [
        'cedula',
        'telefono',
        'correo',
        'direccion',
    ],

];
