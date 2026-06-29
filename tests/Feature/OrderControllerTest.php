<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_crud_endpoints_work(): void
    {
        $payload = [
            'vendor_id' => 1,
            'customer_name' => 'Alice Mensah',
            'phone_number' => '0200000000',
            'delivery_to' => 'Accra Central',
            'additional_notes' => 'Leave at the reception',
            'status' => 'AVAILABLE',
            'payment_method' => 'CASH',
        ];

        $storeResponse = $this->postJson('/orders', $payload);
        $storeResponse->assertStatus(201)
            ->assertJsonPath('customer_name', 'Alice Mensah');

        $this->getJson('/orders')
            ->assertStatus(200)
            ->assertJsonCount(1);

        $this->getJson('/orders/1')
            ->assertStatus(200)
            ->assertJsonPath('customer_name', 'Alice Mensah');

        $this->putJson('/orders/1', array_merge($payload, [
            'customer_name' => 'Alice Boateng',
            'payment_method' => 'MOMO',
        ]))
            ->assertStatus(200)
            ->assertJsonPath('customer_name', 'Alice Boateng')
            ->assertJsonPath('payment_method', 'MOMO');

        $this->deleteJson('/orders/1')
            ->assertStatus(200)
            ->assertJson(['message' => 'Order deleted successfully']);
    }
}
