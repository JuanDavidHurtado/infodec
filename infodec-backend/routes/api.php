<?php

use App\Http\Controllers\CBudget;
use App\Http\Controllers\CCountry;
use App\Http\Controllers\CTrip;
use App\Http\Controllers\CHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::middleware(['web'])->group(function () {
    // Pantalla 1
    Route::get('/country', [CCountry::class, 'list']);

    // Pantalla 2
    Route::post('/budget', [CBudget::class, 'save']);

    // Clima
    Route::get('/weather', [CTrip::class, 'weather']);

    // Cambio de moneda
    Route::get('/fx', [CTrip::class, 'fx']);

    // Pantalla 3 (resumen + guarda historial)
    Route::post('/finalize', [CTrip::class, 'finalize']);

    // Menú: Ver historial
    Route::get('/history', [CHistory::class, 'history']);
});
