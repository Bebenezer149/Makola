<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ResetPasswordController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;




// ----------------------------------Authentication Routes--------------------------------------------

Route::post('/register', [AuthController::class, 'registerVendor'])->middleware('throttle:registration');
Route::post('/login', [AuthController::class, 'loginVendor'])->middleware('throttle:login');



Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logoutVendor']);
    Route::put('update-profile', [AuthController::class, 'updateUser']);
    Route::get('/user', [AuthController::class, 'fetchUser']);
});
// ------------------------------------Product Routes------------------------------------------

Route::get('/show-products', [ProductController::class, 'showStore']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/create-product', [ProductController::class, 'createProduct']);
    Route::get('/products', [ProductController::class, 'fetchProducts']);
    Route::get('/get-one-product', [ProductController::class, 'fetchOneProduct']);
    Route::put('/update-product', [ProductController::class, 'updateProduct']);
    Route::delete('delete-product', [ProductController::class, 'deleteProduct']);
});

// -------------------------------------Order Routes-----------------------------------------------
// Customers do not need an account to place an order. The vendor is derived
// from the selected products; clients must never supply a vendor id.
Route::post('/create-order', [OrderController::class, 'createOrder'])->middleware('throttle:orders');
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/orders', [OrderController::class, 'fetchOrders']);
    Route::get('/get-one-order', [OrderController::class, 'fetchOneOrder']);
    Route::put('/update-order', [OrderController::class, 'updateOrder']);
    Route::delete('delete-order', [OrderController::class, 'deleteOrder']);
    Route::put('/update-status', [OrderController::class, 'updateStatus']);
});

// --------------------------------Dashboard Routes--------------------------------------------
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'Dashboard']);
});

// Reset password Endpoint Test

Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLink'])->middleware('throttle:password-reset');
Route::post('/reset-password', [ResetPasswordController::class, 'resetPassword']);
