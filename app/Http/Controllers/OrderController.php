<?php

namespace App\Http\Controllers;

use App\Jobs\SendSmsJob;
use App\Models\Ledger;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\MoolreService;
use App\Services\PaymentService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\ValidationException;
use Str;

class OrderController extends Controller
{
    //
    public function createOrder(Request $request)
    {

        $validated = $request->validate([

            'customer_name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:255',
            'delivery_to' => 'required|string|max:255',
            'additional_notes' => 'required|string',
            'items' => 'required|array|min:1|max:100',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $order = DB::transaction(function () use ($validated) {
            // Collect the array into requested quantities and sum each item
            $requestedQuantities = collect($validated['items'])
                ->groupBy('product_id')
                ->map(fn($items) => $items->sum('quantity'));

            $products = Product::whereIn('id', $requestedQuantities->keys())
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($products->count() !== $requestedQuantities->count()) {
                throw ValidationException::withMessages(['items' => 'One or more products are no longer available.']);
            }

            if ($products->pluck('vendor_id')->unique()->count() !== 1) {
                throw ValidationException::withMessages(['items' => 'An order can contain products from only one store.']);
            }

            foreach ($requestedQuantities as $productId => $quantity) {
                if ($products[$productId]->quantity < $quantity) {
                    throw ValidationException::withMessages(['items' => "Insufficient stock for {$products[$productId]->product_name}."]);
                }
            }

            $order = Order::create([
                'vendor_id' => $products->first()->vendor_id,
                'customer_name' => $validated['customer_name'],
                'phone_number' => $validated['phone_number'],
                'delivery_to' => $validated['delivery_to'],
                'additional_notes' => $validated['additional_notes'],
                'payment_method' => "MOMO",
                'status' => 'PENDING',
                'total_amount' => 0,
            ]);

            $total = 0;
            foreach ($requestedQuantities as $productId => $quantity) {
                $product = $products[$productId];
                $subtotal = round((float) $product->price * $quantity, 2);
                $remainingQuantity = $product->quantity - $quantity;

                OrderItem::create([
                    'product_id' => $product->id,
                    'order_id' => $order->id,
                    'unit_price' => $product->price,
                    'quantity' => $quantity,
                    'subtotal' => $subtotal,
                ]);

                $product->update([
                    'quantity' => $remainingQuantity,
                    'status' => $remainingQuantity === 0 ? 'OUT_OF_STOCK' : 'AVAILABLE',
                ]);
                $total += $subtotal;
            }




            $token = Str::random(8);
            $subtotal = round($total, 2);
            $commission = min(round($subtotal * 0.02, 2), 5.00);
            $order->update([
                'subtotal_amount' => $subtotal,
                'commission_amount' => $commission,
                'total_amount' => round($subtotal + $commission, 2),
                'order_confirmation_token' => $token,
            ]);

            return $order;
        }, 3);

        $pay = app(PaymentService::class);
        $paymentResponse = $pay->createPayment($order);

        if ($paymentResponse->getStatusCode() >= 400) {
            $this->releaseOrderReservation($order->id);

            return response()->json([
                'message' => 'Could not start payment for this order.',
                'payment_data' => $paymentResponse->getData(true),
            ], $paymentResponse->getStatusCode());
        }

        return response()->json([
            'message' => 'Order created. Complete payment to submit it to the seller.',
            'order' => $order,
            'payment_data' => $paymentResponse,
        ], 201);
    }

    public function fetchOrders(Request $request)
    {
        $foundOrder = Order::paid()
            ->where('vendor_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->get();


        return response()->json([
            'message' => 'Orders retrieved successfully',
            'order' => $foundOrder
        ]);
    }

    public function fetchOneOrder(Request $request)
    {
        $id = $request->input('id');

        $foundOrder = Order::paid()
            ->where('vendor_id', auth()->id())
            ->where('id', $id)
            ->with('Items.product')
            ->firstOrFail();

        // $foundOrder->load('Items.product');
        return response()->json([
            'message' => 'Found Order Successfully',
            'order_details' => $foundOrder
        ]);
    }
    public function updateOrder(Request $request)
    {
        $id = $request->input('id');
        $foundOrder = Order::findOrFail($id);
        if ($foundOrder->vendor_id !== auth()->id()) {
            return response()->json([
                'message' => 'Cannot update Order'
            ], 403);
        } else {
            $validated = $request->validate([
                'customer_name' => 'required|string|max:255',
                'phone_number' => 'required|string|max:255',
                'delivery_to' => 'required|string|max:255',
                'additional_notes' => 'required|string',
                'payment_method' => 'required|in:MOMO,CASH',

            ]);

            $foundOrder->update($validated);
            $foundOrder->load('Items.product');
            return response()->json([
                'message' => 'Order updated Successfully',
                'order_details' => $foundOrder
            ]);
        }
    }
    public function updateStatus(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer',
            'status' => 'required|in:PENDING,CONFIRMED,DELIVERED,CANCELLED',
        ]);

        $allowedTransitions = [
            'PENDING' => ['CONFIRMED', 'CANCELLED'],
            'CONFIRMED' => ['DELIVERED', 'CANCELLED'],
            'DELIVERED' => [],
            'CANCELLED' => [],
        ];

        $foundOrder = DB::transaction(function () use ($request, $validated, $allowedTransitions) {
            $foundOrder = Order::where('vendor_id', $request->user()->id)
                ->lockForUpdate()
                ->findOrFail($validated['id']);

            if (! $foundOrder->payments()->where('payment_status', 'PAID')->exists()) {
                throw ValidationException::withMessages([
                    'payment' => 'The order cannot be processed until payment is confirmed.',
                ]);
            }

            if (! in_array($validated['status'], $allowedTransitions[$foundOrder->status] ?? [], true)) {
                throw ValidationException::withMessages([
                    'status' => 'This order status transition is not allowed.',
                ]);
            }

            if ($validated['status'] === 'CANCELLED') {
                $items = OrderItem::where('order_id', $foundOrder->id)
                    ->lockForUpdate()
                    ->get();

                foreach ($items as $item) {
                    $product = Product::whereKey($item->product_id)
                        ->lockForUpdate()
                        ->first();

                    if ($product) {
                        $remainingQuantity = $product->quantity + $item->quantity;
                        $product->update([
                            'quantity' => $remainingQuantity,
                            'status' => 'AVAILABLE',
                        ]);
                    }
                }
            }

            if ($validated['status'] === 'DELIVERED') {
                $ledger = Ledger::where('vendor_id', $foundOrder->vendor_id)
                    ->lockForUpdate()
                    ->first();

                if (! $ledger) {
                    $ledger = Ledger::create([
                        'vendor_id' => $foundOrder->vendor_id,
                        'available_balance' => 0,
                        'payout_amount' => 0,
                        'status' => 'live',
                    ]);
                }

                $ledger->increment('available_balance', $foundOrder->subtotal_amount);
            }

            $foundOrder->update(['status' => $validated['status']]);

            return $foundOrder;
        });

        $sms = null;

        if ($validated['status'] === 'CONFIRMED') {
            $sms = MoolreService::sendMessage(
                $foundOrder->phone_number,
                "Hey there, your order has been confirmed and will be dispatched soon. Let us know when it arrives here " .
                    env('FRONTEND_URL') . '/confirm-order/' . $foundOrder->order_confirmation_token .
                    ' Thanks for buying from ' . $request->user()->business_name
            );
        }

        return response()->json([
            'message' => 'Order status updated successfully',
            'order' => $foundOrder,
            'sms' => $sms,
        ]);
    }
    public function deleteOrder(Request $request)
    {
        $id = $request->input('id');
        $deleteOrder = Order::findOrFail($id);

        if ($deleteOrder->vendor_id !== auth()->id()) {
            return response()->json([
                'message' => 'Cannot delete order'
            ], 403);
        } else {
            DB::transaction(function () use ($deleteOrder) {
                OrderItem::where('order_id', $deleteOrder->id)->delete();
                $deleteOrder->delete();
            });

            return response()->json([
                'message' => 'Order deleted successfully'
            ]);
        }
    }

    public function getTokenAndUpdateStatus(Request $request)
    {
        $validated = $request->validate([
            'token' => 'required|string',
            'status' => 'required|string',
        ]);

        if ($validated['status'] !== 'DELIVERED') {
            return response()->json(['message' => "Couldn't update order"], 422);
        }

        [$foundOrder, $ledger] = DB::transaction(function () use ($validated) {
            $foundOrder = Order::where('order_confirmation_token', $validated['token'])
                ->lockForUpdate()
                ->first();

            if (! $foundOrder) {
                throw ValidationException::withMessages([
                    'token' => 'Your order could not be found.',
                ]);
            }

            if (! $foundOrder->payments()->where('payment_status', 'PAID')->exists()) {
                throw ValidationException::withMessages([
                    'payment' => 'The order cannot be delivered until payment is confirmed.',
                ]);
            }

            if ($foundOrder->status === 'DELIVERED') {
                $ledger = Ledger::where('vendor_id', $foundOrder->vendor_id)->first();
                return [$foundOrder, $ledger];
            }

            if ($foundOrder->status !== 'CONFIRMED') {
                throw ValidationException::withMessages([
                    'status' => 'Only confirmed orders can be marked as delivered.',
                ]);
            }

            $ledger = Ledger::where('vendor_id', $foundOrder->vendor_id)
                ->lockForUpdate()
                ->first();

            if (! $ledger) {
                $ledger = Ledger::create([
                    'vendor_id' => $foundOrder->vendor_id,
                    'available_balance' => 0,
                    'payout_amount' => 0,
                    'status' => 'live',
                ]);
            }

            $ledger->increment('available_balance', $foundOrder->subtotal_amount);
            $foundOrder->update(['status' => 'DELIVERED']);

            return [$foundOrder, $ledger];
        });

        return response()->json([
            'message' => 'Order delivered successfully',
            'order' => $foundOrder,
            'ledger' => $ledger,
        ]);
    }

    private function releaseOrderReservation(int $orderId): void
    {
        DB::transaction(function () use ($orderId) {
            $order = Order::whereKey($orderId)->lockForUpdate()->firstOrFail();

            if ($order->status !== 'PENDING' || $order->payments()->where('payment_status', 'PAID')->exists()) {
                return;
            }

            $items = OrderItem::where('order_id', $order->id)
                ->lockForUpdate()
                ->get();

            foreach ($items as $item) {
                $product = Product::whereKey($item->product_id)
                    ->lockForUpdate()
                    ->first();

                if ($product) {
                    $remainingQuantity = $product->quantity + $item->quantity;
                    $product->update([
                        'quantity' => $remainingQuantity,
                        'status' => $remainingQuantity > 0 ? 'AVAILABLE' : 'OUT_OF_STOCK',
                    ]);
                }
            }

            $order->update(['status' => 'CANCELLED']);
        });
    }

    public function getByToken(Request $request)
    {
        $token = $request->token;

        $order = Order::where('order_confirmation_token', $token)->first();
        if ($order) {
            return response()->json([
                'order' => $order
            ]);
        }
        return response()->json([
            'order' => "Couldn't find order"
        ]);
    }
}
