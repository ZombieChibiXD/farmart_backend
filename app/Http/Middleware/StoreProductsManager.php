<?php

namespace App\Http\Middleware;

use App\Models\Store;
use Closure;
use Illuminate\Http\Request;

class StoreProductsManager
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $store_id = $request->route('store_id');
        $product_id = $request->route('product_id');
        if(in_array($product_id, Store::find($store_id)->products->pluck('id')->all()))
            return $next($request);
        return response([
            'message'=>'Product does not exist, or store does not own the product!'
        ], 403);
    }
}
