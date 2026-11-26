<?php

namespace Tests\Feature;

use App\Models\Ledger;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CommissionFeeTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_adds_two_percent_capped_at_five_and_credits_vendor_subtotal(): void
    {
        Http::fake([
            '*' => Http::response(['data' => []], 200),
        ]);

        $vendor = $this->createVendor();
        $belowCapProduct = $this->createProduct($vendor, 100);
        $aboveCapProduct = $this->createProduct($vendor, 1000);

        $belowCapOrder = $this->placeOrder($belowCapProduct);
        $this->assertSame('100.00', $belowCapOrder->subtotal_amount);
        $this->assertSame('2.00', $belowCapOrder->commission_amount);
        $this->assertSame('102.00', $belowCapOrder->total_amount);
        Http::assertSent(fn ($request) => str_contains($request->url(), '/embed/link')
            && $request->data()['amount'] === '102.00'
        );

        $aboveCapOrder = $this->placeOrder($aboveCapProduct);
        $this->assertSame('1000.00', $aboveCapOrder->subtotal_amount);
        $this->assertSame('5.00', $aboveCapOrder->commission_amount);
        $this->assertSame('1005.00', $aboveCapOrder->total_amount);
        Http::assertSent(fn ($request) => str_contains($request->url(), '/embed/link')
            && $request->data()['amount'] === '1005.00'
        );

        $payment = $aboveCapOrder->payments()->firstOrFail();
        $this->postJson('/api/moolre/callback', [
            'data' => [
                'externalref' => $payment->reference,
                'txstatus' => 1,
            ],
        ])->assertOk();

        $this->actingAs($vendor, 'sanctum');
        $this->putJson('/api/update-status', [
            'id' => $aboveCapOrder->id,
            'status' => 'CONFIRMED',
        ])->assertOk();

        $this->postJson('/api/delivered', [
            'token' => $aboveCapOrder->order_confirmation_token,
            'status' => 'DELIVERED',
        ])->assertOk();

        $this->assertDatabaseHas('ledgers', [
            'vendor_id' => $vendor->id,
            'available_balance' => '1000.00',
        ]);
    }

    public function test_withdrawal_fee_is_taken_from_requested_amount_and_failed_payout_refunds_gross_amount(): void
    {
        Http::fake([
            '*' => Http::response(['data' => ['txstatus' => 0]], 200),
        ]);

        $vendor = $this->createVendor();
        $ledger = Ledger::create([
            'vendor_id' => $vendor->id,
            'available_balance' => 100,
            'payout_amount' => 0,
            'status' => 'live',
        ]);

        $response = $this->actingAs($vendor, 'sanctum')->postJson('/api/transfer', [
            'amount' => 100,
            'receiver' => '233200000099',
            'channel' => 1,
            'idempotency_key' => 'withdrawal-commission-test',
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('payout_status', 'REFUNDED')
            ->assertJsonPath('amounts.requested_amount', '100.00')
            ->assertJsonPath('amounts.service_fee', '3.00')
            ->assertJsonPath('amounts.transfer_amount', '97.00');

        Http::assertSent(fn ($request) => str_contains($request->url(), '/open/transact/transfer')
            && $request->data()['amount'] === '97.00'
        );

        $this->assertDatabaseHas('payouts', [
            'vendor_id' => $vendor->id,
            'payout_amount' => '100.00',
            'service_fee' => '3.00',
            'transfer_amount' => '97.00',
            'payout_status' => 'REFUNDED',
        ]);
        $this->assertDatabaseHas('ledgers', [
            'id' => $ledger->id,
            'available_balance' => '100.00',
        ]);
    }

    private function createVendor(): User
    {
        return User::create([
            'first_name' => 'Commission',
            'last_name' => 'Vendor',
            'business_name' => 'Commission Vendor',
            'phone_number' => '233200000001',
            'email' => 'commission-vendor@example.com',
            'password' => Hash::make('password'),
        ]);
    }

    private function createProduct(User $vendor, int $price): Product
    {
        return Product::create([
            'vendor_id' => $vendor->id,
            'product_name' => 'Commission Test Product',
            'description' => 'Product used for fee tests',
            'category' => 'Others',
            'price' => $price,
            'quantity' => 5,
            'img' => 'placeholder',
            'status' => 'AVAILABLE',
        ]);
    }

    private function placeOrder(Product $product): Order
    {
        $response = $this->postJson('/api/create-order', [
            'customer_name' => 'Buyer',
            'phone_number' => '233200000002',
            'delivery_to' => 'Accra',
            'additional_notes' => 'Fee calculation test',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ]);

        $response->assertCreated();

        return Order::findOrFail($response->json('order.id'));
    }
}
