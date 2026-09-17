<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;

class MarketplaceController extends Controller
{
    //

    public function fetchProductsForShelf(Request $request){
       $products=Product::where('status','AVAILABLE')->get();
       return response()->json($products);
    }
    public function fetchSellersForMarket(Request $request){
        $sellers=User::paginate(10);

        return response()->json($sellers);
    }
}
