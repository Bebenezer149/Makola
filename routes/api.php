<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;




// ----------------------------------Authentication Routes--------------------------------------------

Route::post('/register', [AuthController::class, 'registerVendor']);
Route::post('/login', [AuthController::class, 'loginVendor']);



Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logoutVendor'])->name('login');
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
Route::post('/create-order', [OrderController::class, 'createOrder']);
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

// Email Test Route

Route::get('/test-email', function () {
    try {
        Mail::raw('This is a BlueSpace test email.', function ($message) {
            $message->to('YOUR_EMAIL@gmail.com')
                    ->subject('BlueSpace Test');
        });

        return response()->json([
            'success' => true,
            'message' => 'Mail sent successfully'
        ]);
    } catch (Throwable $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ], 500);
    }
});
