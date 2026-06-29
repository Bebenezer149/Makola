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
        $orders=Order::where( 'vendor_id', $vendor_id)->count();
        $confirmedOrder=Order::where( 'vendor_id',$vendor_id)->where('status', 'Confirmed')->count();
        $deliveredOrder=Order::where( 'vendor_id', $vendor_id)->where('status', 'Delivered')->count();
        $cancelledOrder=Order::where( 'vendor_id', $vendor_id)->where('status', 'Cancelled')->count();
        $pendingOrders=Order::where( 'vendor_id',$vendor_id)->where('status', 'Pending')->count();
        $totalRevenue=Order::where('vendor_id', $vendor_id)->where('status','Delivered')->sum('total_amount');

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
