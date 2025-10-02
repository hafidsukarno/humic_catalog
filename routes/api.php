<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CatalogController;

Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);

Route::get('/catalogs', [CatalogController::class, 'index']);
Route::get('/catalogs/{id}', [CatalogController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/catalogs/admin', [CatalogController::class, 'store']);
    Route::get('/catalogs/admin', [CatalogController::class, 'index']);
    Route::get('/catalogs/admin/{id}', [CatalogController::class, 'show']);
    Route::put('/catalogs/admin/{id}', [CatalogController::class, 'update']);
    Route::delete('/catalogs/admin/{id}', [CatalogController::class, 'destroy']);
});
