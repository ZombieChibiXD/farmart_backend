<?php

namespace App\Http\Controllers;

use App\Models\Order;
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
      $labels = [];
      $datasets = [];
      $sales = [];
      $users = [];
      $income = [];

      $usercount = User::count();

      // Get labels and datasets for the chart within the last 6 months
      for ($i = 6; $i >= 0; $i--) {
        // Get the current month and year for the label
        $label = date('M Y', strtotime('-' . $i . ' months'));
        $labels[] = $label;

        // Get the number of orders for the current month where order status is STATUS_DELIVERED
        $sales []= Order::where('status', Order::STATUS_DELIVERED)
          ->whereMonth('created_at', date('m', strtotime("-$i months")))
          ->whereYear('created_at', date('Y', strtotime("-$i months")))
          ->count();
        // Get the number of users for the current month
        $users []= User::whereMonth('created_at', date('m', strtotime("-$i months")))
          ->whereYear('created_at', date('Y', strtotime("-$i months")))
          ->count();
        // Get the total income for the current month
        $income []= Order::where('status', Order::STATUS_DELIVERED)
          ->whereMonth('created_at', date('m', strtotime("-$i months")))
          ->whereYear('created_at', date('Y', strtotime("-$i months")))
          ->sum('total');
      }

      $datasets[] = [
        'label' => 'Penjualan',
        'data' => $sales,
        'backgroundColor' => 'rgba(255, 99, 132, 0.2)',
        'borderColor' => 'rgba(255,99,132,1)',
        'borderWidth' => 1,
      ];
      $datasets[] = [
        'label' => 'Pengguna',
        'data' => $users,
        'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
        'borderColor' => 'rgba(54, 162, 235, 1)',
        'borderWidth' => 1,
      ];
      $datasets[] = [
        'label' => 'Pendapatan',
        'data' => $income,
        'backgroundColor' => 'rgba(255, 206, 86, 0.2)',
        'borderColor' => 'rgba(255, 206, 86, 1)',
        'borderWidth' => 1,
        'hidden' => true,
      ];


      return response()->json([
        'total_users' => $usercount,
        'chart'=>[
        'labels' => $labels,
        'datasets' => $datasets,
      ]]);
    }


    public function store(Request $request)
    {
      $usercount = User::count();
      $productcount = Product::count();

    }

}
