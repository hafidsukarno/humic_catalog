<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PartnerController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AdminController;

Route::post('/login', [AuthController::class, 'login']);
// Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);

Route::get('/public/partners', [PartnerController::class, 'publicIndex']);

Route::get('/public/products/title', [ProductController::class, 'publicTitle']);
Route::get('/public/products', [ProductController::class, 'publicIndex']); 
Route::get('/public/products/{slug}', [ProductController::class, 'publicShow']);


Route::get('/dashboard/status', [DashboardController::class, 'status']);
Route::get('/dashboard/counts', [DashboardController::class, 'counts']);



Route::middleware('auth:sanctum')->group(function () {
    Route::get('/admin/products/{category}', [ProductController::class, 'getByCategory']);
    Route::get('admin/products/{category}/search', [ProductController::class, 'search']);


    #inteship
    Route::post('/admin/products/internship', [ProductController::class, 'storeInternship']);
    Route::get('admin/products/internship/{slug}', [ProductController::class, 'detailInternship']);
    Route::put('admin/products/internship/update/{slug}', [ProductController::class, 'updateInternship']);
    Route::delete('admin/products/internship/delete/{slug}', [ProductController::class, 'deleteInternship']);


    
    #research
    Route::post('admin/products/research', [ProductController::class, 'storeResearch']);
    Route::get('admin/products/research/{slug}', [ProductController::class, 'detailResearch']);
    Route::put('admin/products/research/update/{slug}', [ProductController::class, 'updateResearch']);
    Route::delete('admin/products/research/delete/{slug}', [ProductController::class, 'deleteResearch']);
 

    
    
    # Partner 
    Route::post('/admin/partners', [PartnerController::class, 'store']);
    Route::get('/admin/partners', [PartnerController::class, 'index']);
    Route::put('/admin/partners/{slug}', [PartnerController::class, 'update']);
    Route::delete('/admin/partners/{slug}', [PartnerController::class, 'destroy']);




    Route::put('/admin/setting', [AdminController::class, 'updateAdmin']);



    Route::post('/logout', [AuthController::class, 'logout']);
});
