<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
Route::post('/test-product', function (Request $request) {
    try {
        // Log everything
        error_log("=== TEST PRODUCT ENDPOINT HIT ===");
        error_log("Headers: " . json_encode(getallheaders()));
        error_log("Files: " . json_encode($_FILES));
        error_log("POST: " . json_encode($_POST));
        
        // Check if file exists
        if (!$request->hasFile('img')) {
            error_log("NO FILE FOUND!");
            return response()->json(['error' => 'No file uploaded'], 400);
        }
        
        $file = $request->file('img');
        error_log("File found: " . $file->getClientOriginalName());
        error_log("File size: " . $file->getSize());
        
        // Try to move the file to a temp location
        $tempPath = '/tmp/' . uniqid() . '_' . $file->getClientOriginalName();
        error_log("Moving to: " . $tempPath);
        
        $file->move('/tmp', $tempPath);
        error_log("File moved successfully!");
        
        return response()->json([
            'success' => true,
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'temp_path' => $tempPath
        ]);
        
    } catch (\Exception $e) {
        error_log("ERROR: " . $e->getMessage());
        error_log("FILE: " . $e->getFile() . ":" . $e->getLine());
        error_log("TRACE: " . $e->getTraceAsString());
        
        return response()->json([
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ], 500);
    }
});

// ----------------------------------Authentication Routes--------------------------------------------

Route::post('/register', [AuthController::class, 'registerVendor']);
Route::post('/login', [AuthController::class, 'loginVendor']);



Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logoutVendor']);
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
    ROute::put('/update-status', [OrderController::class, 'updateStatus']);
});

// --------------------------------Dashboard Routes--------------------------------------------
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'Dashboard']);
});
