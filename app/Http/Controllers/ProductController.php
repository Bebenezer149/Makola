<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function createProduct(Request $request)
    {
        $validated = $request->validate([
            'product_name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:65535'],
            'price' => ['required', 'numeric', 'min:0'],
            'quantity' => ['required', 'integer', 'min:0'],
            'img' => ['required', 'url', 'max:2048'],
            'images' => ['nullable', 'array', 'max:5'],
            'images.*' => ['nullable', 'url'],
            'category' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string'],
        ]);

        $validated['status'] = ($validated['quantity'] ?? 0) === 0 ? 'OUT_OF_STOCK' : 'AVAILABLE';

        $product = Product::create([
            'vendor_id' => $request->user()->id,
            ...$validated,
        ]);

        return response()->json([
            'message' => 'Success',
            'product' => $product,
        ], 201);
    }

    public function fetchProducts(Request $request)
    {
        $foundProducts = Product::where('vendor_id', auth()->id())->get();

        return response()->json([
            'message' => 'Products Retrieved Successfully',
            'products' => $foundProducts,
        ]);
    }

    public function fetchOneProduct(Request $request)
    {
        $id = $request->input('id');

        $foundProduct = Product::where('id', $id)
            ->where('vendor_id', $request->user()->id)
            ->firstOrFail();

        return response()->json([
            'message' => 'Product retrieved successfully',
            'product' => $foundProduct,
        ]);
    }

    public function updateProduct(Request $request)
    {
        $id = $request->input('id');
        $product = Product::findOrFail($id);

        if ((int) $product->vendor_id !== (int) $request->user()->id) {
            return response()->json([
                'message' => 'Cannot update product',
            ], 403);
        }

        $validated = $request->validate([
            'product_name' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string', 'max:65535'],
            'category' => ['nullable', 'string', 'max:255'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'quantity' => ['sometimes', 'integer', 'min:0'],
            'img' => ['sometimes', 'nullable', 'url', 'max:2048'],
            'images' => ['nullable', 'array', 'max:5'],
            'images.*' => ['nullable', 'url'],
            'status' => ['sometimes', 'string'],
        ]);

        if (array_key_exists('quantity', $validated)) {
            $validated['status'] = $validated['quantity'] === 0 ? 'OUT_OF_STOCK' : 'AVAILABLE';
        } elseif (isset($validated['status'])) {
            $validated['status'] = $this->normalizeProductStatus($validated['status']);
        }

        $product->update($validated);

        return response()->json([
            'message' => 'Product updated successfully',
            'product' => $product,
        ], 200);
    }

    public function deleteProduct(Request $request)
    {
        $id = $request->input('id');
        $foundProduct = Product::findOrFail($id);

        if ((int) $foundProduct->vendor_id !== (int) auth()->id()) {
            return response()->json([
                'message' => 'Cannot delete Product',
            ], 403);
        }

        $foundProduct->delete();

        return response()->json([
            'message' => 'Product deleted successfully',
        ], 200);
    }

    public function showStore(Request $request)
    {
        $link = $request->query('link');

        $vendor = User::where('link', $link)->firstOrFail();

        $foundProduct = Product::where('vendor_id', $vendor->id)->get();

        return response()->json([
            'message' => 'products retrieved successfully',
            'products' => $foundProduct,
            'business_name' => $vendor->business_name,
            'phone_number' => $vendor->phone_number,
            'email' => $vendor->email,
            'profile_picture' => $vendor->profile_picture,
            'banner' => $vendor->banner,
        ]);
    }

    private function normalizeProductStatus(string $status): string
    {
        $normalized = strtoupper(str_replace([' ', '-'], '_', trim($status)));

        return match ($normalized) {
            'AVAILABLE' => 'AVAILABLE',
            'OUT_OF_STOCK' => 'OUT_OF_STOCK',
            default => 'AVAILABLE',
        };
    }
}
