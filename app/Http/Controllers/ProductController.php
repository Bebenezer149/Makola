<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\User;
// use App\Services\SupabaseStorageService;
// use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    //
   
    public function createProduct(Request $request)
    {
        try {

            $validated = $request->validate([
                'product_name' => 'required|string|max:255',
                'description' => 'required|string|max:255',
                'price' => 'required|numeric',
                'quantity' => 'required|numeric',
                'img' => 'required|url',
                'status' => 'required|in:AVAILABLE,OUT_OF_STOCK,Available,Out_Of_Stock',
            ]);

            Log::info('Validation passed');


            $payload = array_merge(
                ['vendor_id' => auth()->id()],
                $validated
            );
            $product = Product::create($payload);

            Log::info('Product created');

            return response()->json([
                'message' => 'Success',
                'product' => $product
            ]);
        } catch (\Throwable $e) {

            Log::error($e);

            return response()->json([
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
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
            'price'        => 'numeric',
            'quantity'     => 'numeric',
            'status'       => 'in:AVAILABLE,OUT_OF_STOCK,Available,Out_Of_Stock',
            'img'          => 'url',
        ]);

      

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
            'products' => $foundProduct,
            'business_name'=>$vendor->business_name
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
