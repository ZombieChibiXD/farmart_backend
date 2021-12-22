<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
// A restful controller is a controller that responds to HTTP requests using a set of methods.
class OverviewController extends Controller
{
    /**
     * Display an overview for administrator.
     *
     * @return Response
     */
    public function admin(Request $request)
    {
      $usercount = User::count();
      $productcount = Product::count();

    }

}
