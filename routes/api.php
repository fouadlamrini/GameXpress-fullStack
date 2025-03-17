<?php

use App\Http\Controllers\Api\V1\Admin\CategoryController;
use App\Http\Controllers\Api\V1\Admin\DashboardController;
use App\Http\Controllers\Api\V1\Admin\ProductController;
use App\Http\Controllers\Api\V1\Admin\UserController;
use App\Http\Controllers\Api\V1\Admin\ProductImageController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;


Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

Route::prefix('v1')->group(function () {

    Route::prefix('admin')->group(function () {

        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/register', [AuthController::class, 'register']);

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::get('/dashboard', [DashboardController::class, 'index']);


            Route::middleware('role:product_manager|super_admin')->group(function () {
                Route::apiResource('categories', CategoryController::class);
                Route::put('categories/{category}/subcategories/{subcategory}', [CategoryController::class, 'updateSubcategory']);
                Route::delete('categories/{category}/subcategories/{subcategory}', [CategoryController::class, 'destroySubcategory']);
                Route::get('categories/{category}/subcategories', [CategoryController::class, 'indexSubcategory']);
                Route::get('categories/{category}/subcategories/{subcategory}', [CategoryController::class, 'showSubcategory']);
                Route::apiResource('products', ProductController::class);
                Route::post('products/{product}/images', [ProductImageController::class, 'store']);
                Route::delete('products/{product}/images/{image}', [ProductImageController::class, 'destroy']);
                Route::put('products/{product}/images/{image}/set-primary', [ProductImageController::class, 'setPrimary']);
                Route::get('products/{product}/images', [ProductImageController::class, 'index']);
                Route::get('products/{product}/images/{image}', [ProductImageController::class, 'show']);            
            });
            Route::middleware('role:user_manager|super_admin')->group(function () {
                Route::apiResource('users', UserController::class);
            });
        });
    });
});

Route::prefix('v1/admin')->middleware(['auth:sanctum'])->group(function () {
    Route::middleware('role:user_manager|super_admin')->group(function () {
        Route::apiResource('users', UserController::class);
    });
});
