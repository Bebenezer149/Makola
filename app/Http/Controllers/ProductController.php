<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\User;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    //
    public function createProduct(Request $request)
    {


        $validated = $request->validate([
            'product_name' => 'required|string|max:255',
            'description' => 'required|string|max:255',
            'price' => 'required|integer',
            'quantity' => 'required|integer',
            'img' => 'required|image|max:5120',
            'status' => 'required|in:AVAILABLE,OUT_OF_STOCK,Available,Out_Of_Stock',
        ]);

        $validated['status'] = $this->normalizeProductStatus($validated['status']);
        $uploaded = Cloudinary::upload(
            $request->file('img')->getRealPath(),
            [
                'folder' => 'makola-products'
            ]
        );

        $validated['img'] = $uploaded->getSecurePath();
        $product = Product::create([
            'vendor_id' => auth()->id(),
            ...$validated
        ]);
        return response()->json([
            'message' => 'Product Created Successfully',
            'product' => $product
        ]);
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
