<?php

use App\Http\Controllers\Api\V1\Admin\CartController;
use App\Http\Controllers\Api\V1\Admin\CategoryController;
use App\Http\Controllers\Api\V1\Admin\DashboardController;
use App\Http\Controllers\Api\V1\Admin\PaymentController;
use App\Http\Controllers\Api\V1\Admin\ProductController;
use App\Http\Controllers\Api\V1\Admin\UserController;
use App\Http\Controllers\Api\V1\Admin\ProductImageController;
use App\Http\Controllers\Api\V1\Admin\RolePermissionController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;


Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::get('/success', [PaymentController::class, 'success'])->name('payment.success');
Route::get('/cancel', [PaymentController::class, 'cancel'])->name('payment.cancel');

Route::prefix('v1')->group(function () {

    Route::prefix('admin')->group(function () {

        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/register', [AuthController::class, 'register']);

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::get('/dashboard', [DashboardController::class, 'index']);

            //charge
            // Route::get('/charge', [PaymentController::class, 'charge']);


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

    Route::prefix('client')->middleware('auth:sanctum')->group(function () {
        Route::prefix('cart')->group(function () {
            Route::get('/', [CartController::class, 'cart']);
            Route::post('items', [CartController::class, 'addItem']);
            Route::put('items/{cartItem}', [CartController::class, 'updateItem']);
            Route::delete('items/{cartItem}', [CartController::class, 'removeItem']);
        });

        Route::get('payments', [PaymentController::class, 'index']);
        Route::get('payments/{payment}', [PaymentController::class, 'show']);
        Route::post('payments', [PaymentController::class, 'charge']);
        Route::get('/orders', [OrderController::class, 'index']);
        Route::get('/orders/{orderId}', [OrderController::class, 'show']);
        Route::post('/orders/{orderId}/cancel', [OrderController::class, 'cancel']);
    });

    Route::prefix('cart')->group(function () {
        Route::get('/', [CartController::class, 'cart']);
        Route::post('items', [CartController::class, 'addItem']);
        Route::put('items/{cartItem}', [CartController::class, 'updateItem']);
        Route::delete('items/{cartItem}', [CartController::class, 'removeItem']);
    });

    Route::prefix('v1')->group(function () {
        Route::prefix('admin')->group(function () {
            Route::middleware('auth:sanctum')->group(function () {
                Route::middleware('role:super_admin')->group(function () {
                    Route::get('/roles', [RolePermissionController::class, 'index']);
                    Route::get('/roles/{roleId}', [RolePermissionController::class, 'show']);
                    Route::post('/roles/{roleId}/add-permission', [RolePermissionController::class, 'addPermission']);
                    Route::post('/roles/{roleId}/remove-permission', [RolePermissionController::class, 'removePermission']);
                    Route::post('/request-role-permission', [RolePermissionController::class, 'requestRolePermission']);
                });
            });
        });
    });
});
