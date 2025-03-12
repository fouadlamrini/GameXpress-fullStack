<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductImageController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');


Route::post('/login', [AuthController::class, 'login'] );
Route::post('/register', [AuthController::class, 'register'] );
Route::post('/logout', [AuthController::class, 'logout'] )->middleware('auth:sanctum');
Route::apiResource('categories', CategoryController::class);

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('products', ProductController::class);
    Route::apiResource('products.images', ProductImageController::class)->shallow();
    Route::apiResource('categories', CategoryController::class);
    Route::get('/dashboard', [HomeController::class, 'index']);
});