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
        $baseOrderQuery = Order::where('vendor_id', $vendor_id);

        return response()->json([
            'message'=>'Statistics loaded successfully',
            'total_products' => Product::where('vendor_id', $vendor_id)->count(),
            'total_orders' => (clone $baseOrderQuery)->count(),
            'confirmed_orders' => (clone $baseOrderQuery)->where('status', 'CONFIRMED')->count(),
            'delivered_orders' => (clone $baseOrderQuery)->where('status', 'DELIVERED')->count(),
            'cancelled_orders' => (clone $baseOrderQuery)->where('status', 'CANCELLED')->count(),
            'pending_orders' => (clone $baseOrderQuery)->where('status', 'PENDING')->count(),
            'total_revenue' => (clone $baseOrderQuery)->where('status','DELIVERED')->sum('total_amount')
        ]);

    }
}
