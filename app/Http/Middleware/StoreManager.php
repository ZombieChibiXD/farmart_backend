<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StoreManager
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
        $user = Auth::user();
        $store_id = $request->route('store_id');
        if($user->stores->contains($store_id))
            return $next($request);
        return response([
            'message'=>'Store does not exist, or you have insufficient permission to modify the store!'
        ], 403);
    }
}
