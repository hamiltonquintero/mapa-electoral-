<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return ['message' => 'Mapa Electoral Caldas API', 'version' => '1.0'];
});
