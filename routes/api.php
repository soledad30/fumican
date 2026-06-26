<?php

use App\Http\Controllers\Reservas\ReservaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
Route::post('/generar-qr', [ReservaController::class, 'qr'])->name('reservations.qr');
Route::post('/verificar-pago', [ReservaController::class, 'verificarPago']);

