<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OrderPaymentGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_unpaid_order_is_hidden_and_cannot_be_confirmed_or_delivered(): void
    {
        $vendor = $this->createVendor();
        $order = $this->createOrder($vendor);
        $this->actingAs($vendor, 'sanctum');

        $this->getJson('/api/orders')
            ->assertOk()
            ->assertJsonCount(0, 'order');
        $this->getJson('/api/dashboard')
            ->assertOk()
            ->assertJsonPath('total_orders', 0)
            ->assertJsonPath('pending_orders', 0);

        $this->putJson('/api/update-status', [
            'id' => $order->id,
            'status' => 'CONFIRMED',
        ])->assertUnprocessable();

        $this->postJson('/api/delivered', [
            'token' => $order->order_confirmation_token,
            'status' => 'DELIVERED',
        ])->assertUnprocessable();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'PENDING',
        ]);
        $this->assertDatabaseCount('ledgers', 0);
    }

    public function test_paid_callback_exposes_order_and_delivery_credits_seller_once(): void
    {
        Http::fake();

        $vendor = $this->createVendor();
        $order = $this->createOrder($vendor);
        $payment = $order->payments()->firstOrFail();
        $callback = [
            'data' => [
                'externalref' => $payment->reference,
                'txstatus' => 1,
            ],
        ];

        $this->postJson('/api/moolre/callback', $callback)->assertOk();
        $this->postJson('/api/moolre/callback', $callback)->assertOk();

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'payment_status' => 'PAID',
        ]);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'PENDING',
        ]);
        Http::assertSentCount(1);

        $this->actingAs($vendor, 'sanctum');
        $this->getJson('/api/orders')
            ->assertOk()
            ->assertJsonCount(1, 'order');
        $this->getJson('/api/dashboard')
            ->assertOk()
            ->assertJsonPath('total_orders', 1)
            ->assertJsonPath('pending_orders', 1);

        $this->putJson('/api/update-status', [
            'id' => $order->id,
            'status' => 'CONFIRMED',
        ])->assertOk();

        $deliveryRequest = [
            'token' => $order->order_confirmation_token,
            'status' => 'DELIVERED',
        ];

        $this->postJson('/api/delivered', $deliveryRequest)->assertOk();
        $this->postJson('/api/delivered', $deliveryRequest)->assertOk();

        $this->assertDatabaseHas('ledgers', [
            'vendor_id' => $vendor->id,
            'available_balance' => '45.00',
        ]);
    }

    private function createVendor(): User
    {
        return User::create([
            'first_name' => 'Order',
            'last_name' => 'Vendor',
            'business_name' => 'Order Vendor',
            'phone_number' => '233200000001',
            'email' => 'order-vendor@example.com',
            'password' => Hash::make('password'),
        ]);
    }

    private function createOrder(User $vendor): Order
    {
        $order = Order::create([
            'vendor_id' => $vendor->id,
            'customer_name' => 'Buyer',
            'phone_number' => '233200000002',
            'delivery_to' => 'Accra',
            'additional_notes' => 'Leave at reception',
            'payment_method' => 'MOMO',
            'status' => 'PENDING',
            'total_amount' => 45,
            'order_confirmation_token' => 'order-token-123',
        ]);

        Payment::create([
            'order_id' => $order->id,
            'vendor_id' => $vendor->id,
            'amount' => 45,
            'reference' => 'BS-'.$order->id.'-PAYMENT',
            'payment_status' => 'PENDING',
        ]);

        return $order;
    }
}
