<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProductImagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_images_are_created_returned_and_removed_on_update(): void
    {
        $vendor = User::create([
            'first_name' => 'Product',
            'last_name' => 'Vendor',
            'business_name' => 'Product Vendor',
            'phone_number' => '233200000001',
            'email' => 'product-vendor@example.com',
            'password' => Hash::make('password'),
        ]);
        $this->actingAs($vendor, 'sanctum');

        $images = [
            'https://images.example.com/product-front.jpg',
            'https://images.example.com/product-side.jpg',
        ];

        $response = $this->postJson('/api/create-product', [
            'product_name' => 'Test product',
            'description' => 'Product description',
            'price' => 12,
            'quantity' => 4,
            'img' => 'https://images.example.com/product-main.jpg',
            'images' => $images,
        ]);

        $response->assertCreated()
            ->assertJsonPath('product.product_image.0.secondary_url', $images[0])
            ->assertJsonPath('product.product_image.1.secondary_url', $images[1]);

        $productId = $response->json('product.id');
        $this->assertDatabaseCount('product_images', 2);

        $this->putJson("/api/update-product?id={$productId}", [
            'images' => [$images[1]],
        ])->assertOk()
            ->assertJsonPath('product.product_image.0.secondary_url', $images[1]);

        $this->assertDatabaseCount('product_images', 1);

        $this->putJson("/api/update-product?id={$productId}", [
            'images_present' => true,
            'images' => [],
        ])->assertOk()
            ->assertJsonPath('product.product_image', []);

        $this->assertDatabaseCount('product_images', 0);

        $this->getJson('/api/products')
            ->assertOk()
            ->assertJsonPath('products.0.product_image', []);
    }
}
