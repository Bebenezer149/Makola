<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;

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

            'status' => 'in:PENDING,CONFIRMED,DELIVERED,CANCELLED',
            'payment_method' => 'required|in:MOMO,CASH',
            'items' => 'required|array',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        // $firstProduct = Product::findOrFail($validated['items'][0]['product_id']);

        $order = Order::create([
            'vendor_id' => $request->vendor_id,
            ...$validated,
        ]);



        $total = 0;


        foreach ($request->items as $item) {
            $product = Product::findOrFail($item['product_id']);
            $subtotal = $product->price * $item['quantity'];
            $reduce = $product->quantity - $item['quantity'];
            $orderItem = OrderItem::create([
                'product_id' => $product->id,
                'order_id' => $order->id,
                'unit_price' => $product->price,
                'quantity' => $item['quantity'],
                'subtotal' => $subtotal,

            ]);
            $product->update([
                'quantity' => $reduce,
                'status' => $reduce === 0 ? 'OUT_OF_STOCK' : 'AVAILABLE'
            ]);
            $total += $subtotal;
        }

        $order->update([
            'total_amount' => $total
        ]);


        return response()->json([
            'message' => 'Order placed successfully',

        ]);
    }

    public function fetchOrders(Request $request)
    {
        $foundOrder = Order::where('vendor_id', auth()->id())->get();


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
        ], 201);
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
                'status' => 'nullable|in:Delivered,Pending,Confirmed,Cancelled ',
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

        $foundOrder = Order::findOrFail($request->id);
        if ($foundOrder->vendor_id !== auth()->id()) {
            return response([
                'message' => 'Cannot access order',
            ], 403);
        }
        // if ($foundOrder->status !== 'PENDING'  || $foundOrder->status !== 'CONFIRMED') {
        //     return response()->json([
        //         'message' => 'Only Pending Orders can be Confirmed'
        //     ], 400);
        // }
        $foundOrder->update([
            'status' => $request->status
        ]);

        return response()->json([
            'message' => 'Updated successfully',
            'order' => $foundOrder
        ], 201);
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
            $deleteOrder->delete();

            return response()->json([
                'message' => 'Order deleted successfully'
            ]);
        }
    }
}
