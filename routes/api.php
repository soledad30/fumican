<?php

use App\Http\Controllers\Pagos\PagoFacilController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/generar-qr', [PagoFacilController::class, 'generarQr'])->name('pagos.qr.generar');
Route::post('/verificar-pago', [PagoFacilController::class, 'verificarPago'])->name('pagos.qr.verificar');
