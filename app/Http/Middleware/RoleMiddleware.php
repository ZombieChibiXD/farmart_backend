<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next, int $flag)
    {
        $user = Auth::user();
        if ($flag) {
            if ($user->role & $flag == $flag)
                return $next($request);
        } else if ($user->role == 0) return response(['message'=>'You are restricted'],403);
        return response(['message'=>'Not available'],403);;
    }
}
