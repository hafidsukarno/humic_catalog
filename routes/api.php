<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PartnerController;
use App\Http\Controllers\ProductController;

Route::post('/login', [AuthController::class, 'login']);
// Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);

Route::get('/public/partners', [PartnerController::class, 'publicIndex']);

Route::get('/public/products/title', [ProductController::class, 'publicTitle']);
Route::get('/public/products', [ProductController::class, 'publicIndex']); 
Route::get('/public/products/{slug}', [ProductController::class, 'publicShow']);


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/admin/products', [ProductController::class, 'index']);
    
    Route::get('/admin/products/{slug}', [ProductController::class, 'show']);
    Route::put('/admin/products/{slug}', [ProductController::class, 'update']);
    Route::delete('/admin/products/{slug}', [ProductController::class, 'destroy']);  
    Route::post('/admin/products', [ProductController::class, 'store']);        
    
    
    Route::post('/admin/partners', [PartnerController::class, 'store']);
    Route::get('/admin/partners', [PartnerController::class, 'index']);
    Route::put('/admin/partners/{slug}', [PartnerController::class, 'update']);
    Route::delete('/admin/partners/{slug}', [PartnerController::class, 'destroy']);




    // Route::get('/partners/{slug}', [PartnerController::class, 'show']);



    Route::post('/logout', [AuthController::class, 'logout']);
});
