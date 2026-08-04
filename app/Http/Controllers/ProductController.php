<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\User;
// use App\Services\SupabaseStorageService;
// use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    //
   
    public function createProduct(Request $request)
    {
        $validated = $request->validate([
            'product_name' => 'required|string|max:255',
            'description' => 'required|string|max:65535',
            'price' => 'required|decimal:0,2|min:0',
            'quantity' => 'required|integer|min:0',
            'img' => 'required|url|max:2048',
            'status' => 'nullable|in:AVAILABLE,OUT_OF_STOCK,Available,Out_Of_Stock',
        ]);

        $validated['status'] = $validated['quantity'] === 0 ? 'OUT_OF_STOCK' : 'AVAILABLE';
        $product = Product::create(['vendor_id' => $request->user()->id, ...$validated]);

        return response()->json([
            'message' => 'Success',
            'product' => $product
        ], 201);
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
        $foundProduct = Product::where('id', $id)
            ->where('vendor_id', $request->user()->id)
            ->firstOrFail();

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
            'price'        => 'decimal:0,2|min:0',
            'quantity'     => 'integer|min:0',
            'status'       => 'in:AVAILABLE,OUT_OF_STOCK,Available,Out_Of_Stock',
            'img'          => 'url',
        ]);

      

        if (array_key_exists('quantity', $validated)) {
            $validated['status'] = $validated['quantity'] === 0 ? 'OUT_OF_STOCK' : 'AVAILABLE';
        } elseif (isset($validated['status'])) {
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
            'business_name'=>$vendor->business_name,
            'phone_number'=>$vendor->phone_number,
            'profile_picture'=>$vendor->profile_picture,
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
