<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\MoolreService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

            'payment_method' => 'required|in:MOMO,CASH',
            'items' => 'required|array|min:1|max:100',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $order = DB::transaction(function () use ($validated) {
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
                'payment_method' => $validated['payment_method'],
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
            $order->update(['total_amount' => round($total, 2), 'order_confirmation_token' => $token]);

            return $order;
        }, 3);

        try {
            $vendor = User::find($order->vendor_id);
            if ($vendor && $vendor->phone_number) {
                $sms = app(MoolreService::class);
                $sms->sendMessage($vendor->phone_number, "You have a new order from " . $order->customer_name . " to attend to. Kindly visit your dashboard at https://blue-space-gh.vercel.app/login to review order ");
            }
        } catch (\Exception $e) {
            // Log the error but don't fail the request. The order was created successfully.
            Log::error('Failed to send new order SMS notification: ' . $e->getMessage());
        }

        return response()->json([
            'message' => 'Order placed successfully',
            'order' => $order,
        ], 201);
    }

    public function fetchOrders(Request $request)
    {
        $foundOrder = Order::where('vendor_id', auth()->id())->orderBy('created_at', 'desc')->get();


        return response()->json([
            'message' => 'Orders retrieved successfully',
            'order' => $foundOrder
        ]);
    }

    public function fetchOneOrder(Request $request)
    {
        $id = $request->input('id');

        $foundOrder = Order::where('vendor_id', auth()->id())
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

        $foundOrder = Order::where('vendor_id', $request->user()->id)->findOrFail($validated['id']);
        $allowedTransitions = [ // Defines the state machine for order statuses
            'PENDING' => ['CONFIRMED', 'CANCELLED'],
            'CONFIRMED' => ['DELIVERED', 'CANCELLED'],
            'DELIVERED' => [],
            'CANCELLED' => [],
        ];

        if (! in_array($validated['status'], $allowedTransitions[$foundOrder->status] ?? [], true)) {
            return response()->json(['message' => 'This order status transition is not allowed.'], 422);
        }

        $foundOrder->update(['status' => $validated['status']]);

        // Send SMS notification after status update
        try {
            if ($validated['status'] === "CONFIRMED") {
                $customerPhoneNumber = $foundOrder->phone_number;
                $vendorName = $request->user()->business_name;
                $sms = app(MoolreService::class);
                $sms->sendMessage($customerPhoneNumber, "Hey there, your order has been confirmed and will be dispatched soon. Let us know when it arrives here " . env("FRONTEND_URL") . "/confirm-order" . "/".$foundOrder->order_confirmation_token . " Thanks from buying from " . $vendorName);
            }
        } catch (Exception $e) {
            // Log the error but don't fail the main request, as the status was updated successfully.
            Log::error('Failed to send order confirmation SMS: ' . $e->getMessage());
        }

        return response()->json([
            'message' => 'Updated successfully',
            'order' => $foundOrder
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

    public function getByToken(Request $request)
    {
        $token = $request->token;
        $foundOrder = Order::where("order_confirmation_token", $token)->first();
        if (!$foundOrder) {
            return response()->json(["message" => "Your Order Could not be found"], 404);
        }

        return response()->json(["order"=>$foundOrder]);
    }
}
