<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\ClienteController;
use App\Http\Controllers\Api\V1\DocumentoController;

Route::middleware('auth.basic.custom')->prefix('v1')->group(function () {
    Route::get('/clientes/consulta', [ClienteController::class, 'consulta']);
    Route::get('/documentos/{ligacao}', [DocumentoController::class, 'index']);
});