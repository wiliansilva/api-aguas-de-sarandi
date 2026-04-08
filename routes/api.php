<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ClienteController;

Route::middleware('auth.basic.custom')->group(function () {
    Route::get('/v1/clientes/consulta', [ClienteController::class, 'consulta']);
});