<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    //
    public function Dashboard(Request $request){
        $vendor_id=auth()->id();

        $product=Product::where('vendor_id', $vendor_id)->count();
        $orders=Order::paid()->where('vendor_id', $vendor_id)->count();
        $confirmedOrder=Order::paid()->where('vendor_id',$vendor_id)->where('status', 'CONFIRMED')->count();
        $deliveredOrder=Order::paid()->where('vendor_id', $vendor_id)->where('status', 'DELIVERED')->count();
        $cancelledOrder=Order::paid()->where('vendor_id', $vendor_id)->where('status', 'CANCELLED')->count();
        $pendingOrders=Order::paid()->where('vendor_id',$vendor_id)->where('status', 'PENDING')->count();
        $totalRevenue=Order::paid()->where('vendor_id', $vendor_id)->where('status','DELIVERED')->sum('subtotal_amount');

        // if('vendor_id' !== $vendor_id){
        //     return response([
        //         'message'=>'Cannot access statistics',
        //     ], 403);
        // }

        return response()->json([
            'message'=>'Statistics loaded successfully',
            'total_products'=>$product,
            'total_orders'=>$orders,
            'confirmed_orders'=>$confirmedOrder,
            'delivered_orders'=>$deliveredOrder,
            'cancelled_orders'=>$cancelledOrder,
            'pending_orders'=>$pendingOrders,
            'total_revenue'=> $totalRevenue
        ]);

    }
}
