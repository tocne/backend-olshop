<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Order;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function summary()
    {
        return response()->json([
            'total_products' => Product::count(),
            'total_orders' => Order::count(),
            'pending_orders' => Order::where('status', 'pending')->count(),
            'waiting_confirmation' => Order::where('status', 'waiting_confirmation')->count(),
        ]);
    }

    public function salesWeekly()
{
    // ambil 7 hari terakhir
    $days = collect(range(0, 6))->map(function ($i) {
        return now()->subDays($i)->format('Y-m-d');
    })->reverse();

    $orders = [];
    $revenue = [];

    foreach ($days as $day) {
        $orders[] = Order::whereDate('created_at', $day)->count();
        $revenue[] = Order::whereDate('created_at', $day)->sum('total_amount');
    }

    return response()->json([
        'labels' => $days,
        'orders' => $orders,
        'revenue' => $revenue
    ]);
}

}
