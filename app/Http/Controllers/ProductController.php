<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\User;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    //
   public function createProduct(Request $request)
    {
        try {
            Log::info('=== PRODUCT CREATION STARTED ===');
            Log::info('User ID: ' . auth()->id());
            Log::info('Content-Type: ' . $request->header('Content-Type'));
            
            // Check if file exists
            Log::info('Has file? ' . ($request->hasFile('img') ? 'YES' : 'NO'));
            
            if ($request->hasFile('img')) {
                $file = $request->file('img');
                Log::info('File name: ' . $file->getClientOriginalName());
                Log::info('File size: ' . $file->getSize() . ' bytes');
                Log::info('File mime: ' . $file->getMimeType());
                Log::info('Is valid? ' . ($file->isValid() ? 'YES' : 'NO'));
            }

            // Validate
            Log::info('Starting validation...');
            $validated = $request->validate([
                'product_name' => 'required|string|max:255',
                'description' => 'required|string|max:255',
                'price' => 'required|numeric',
                'quantity' => 'required|integer',
                'img' => 'required|image|max:5120',
                'status' => 'required|in:Available,Out_Of_Stock'
            ]);
            Log::info('Validation passed!');

            // Upload to Cloudinary
            Log::info('Uploading to Cloudinary...');
            try {
                $uploaded = Cloudinary::upload(
                    $request->file('img')->getRealPath(),
                    ['folder' => 'makola-products']
                );
                $validated['img'] = $uploaded->getSecurePath();
                Log::info('Cloudinary upload successful! URL: ' . $validated['img']);
            } catch (\Exception $e) {
                Log::error('Cloudinary error: ' . $e->getMessage());
                return response()->json([
                    'error' => 'Cloudinary upload failed: ' . $e->getMessage()
                ], 500);
            }

            // Create product
            Log::info('Creating product in database...');
            $product = Product::create([
                'vendor_id' => auth()->id(),
                ...$validated
            ]);
            Log::info('Product created! ID: ' . $product->id);

            return response()->json([
                'message' => 'Product created successfully',
                'product' => $product
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed: ' . json_encode($e->errors()));
            return response()->json(['errors' => $e->errors()], 422);
            
        } catch (\Exception $e) {
            Log::error('GENERAL ERROR: ' . $e->getMessage());
            Log::error('File: ' . $e->getFile() . ':' . $e->getLine());
            Log::error('Trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }
    public function fetchProducts(Request $request)
    {
        $foundProducts = Product::where('vendor_id', auth()->id())->get();
        return response()->json(
            [
                'message' => 'Products Retrieved Successfully',
                'products' => $foundProducts
            ]
        );
    }

    public function fetchOneProduct(Request $request)
    {
        $id = $request->input('id');
        $foundProduct = Product::findOrFail($id)->first()->get();

        if ($foundProduct->vendor_id !== auth()->id()) {
            return response()->json([
                'message' => 'Cannot access product'
            ], 403);
        }

        return response()->json([
            'message' => 'Product retrieved successfully',
            'product' => $foundProduct
        ]);
    }

    public function updateProduct(Request $request)
    {
        $id = $request->input('id');

        $product = Product::findOrFail($id);

        if ($product->vendor_id !== auth()->id()) {
            return response()->json([
                'message' => 'Cannot update product',
            ], 403);
        }

        $validated = $request->validate([
            'product_name' => 'string|max:255',
            'description'  => 'string',
            'price'        => 'integer',
            'quantity'     => 'integer',
            'status'       => 'in:AVAILABLE,OUT_OF_STOCK,Available,Out_Of_Stock',
            'img'          => 'image|max:2048',
        ]);

        if ($request->hasFile('img')) {
            $path = $request->file('img')->store('products', 'public');
            $validated['img'] = asset('storage/' . $path);
        }

        if (isset($validated['status'])) {
            $validated['status'] = $this->normalizeProductStatus($validated['status']);
        }

        $product->update($validated);

        return response()->json([
            'message' => 'Product updated successfully',
            'product' => $product
        ]);
    }

    public function deleteProduct(Request $request)
    {
        $id = $request->input('id');
        $foundProduct = Product::findOrFail($id);

        if ($foundProduct->vendor_id !== auth()->id()) {
            return response()->json([
                'message' => 'Cannot delete Product'
            ], 403);
        } else {
            $foundProduct->delete();

            return response()->json([
                'message' => 'Product deleted successfully'
            ], 201);
        }
    }

    public function showStore(Request $request,)
    {
        $link = $request->link;

        $vendor = User::where('link', $link)->firstOrFail();

        $foundProduct = Product::where('vendor_id', $vendor->id)->get();

        return response()->json([
            'message' => 'products retrieved successfully',
            'products' => $foundProduct
        ]);
    }

    private function normalizeProductStatus(string $status): string
    {
        return match (strtoupper(str_replace(' ', '_', $status))) {
            'AVAILABLE' => 'AVAILABLE',
            'OUT_OF_STOCK' => 'OUT_OF_STOCK',
            default => $status,
        };
    }
}
