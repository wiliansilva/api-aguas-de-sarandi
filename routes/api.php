<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\ClienteController;
use App\Http\Controllers\Api\V1\DocumentoController;
use App\Http\Controllers\Api\V1\LinhaDigitavelController;

Route::middleware('auth.basic.custom')->prefix('v1')->group(function () {
    Route::get( '/clientes/consulta',           [ClienteController::class,        'consulta']);
    Route::get( '/documentos/{ligacao}',         [DocumentoController::class,       'index']);
    Route::post('/documentos/linha-digitavel',   [LinhaDigitavelController::class,  'gerar']);
});