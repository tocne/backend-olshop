<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    // 🔹 GET /api/orders
    public function index()
    {
        $orders = Order::with('user', 'products')->get();

        return response()->json($orders);
    }

    // 🔹 POST /api/orders
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'total_price' => 'required|numeric|min:0',
            'status' => 'required|string',
        ]);

        $order = Order::create($request->all());

        return response()->json($order, 201);
    }

    // 🔹 GET /api/orders/{id}
    public function show($id)
    {
        $order = Order::with('user', 'products')->findOrFail($id);

        return response()->json($order);
    }

    // 🔹 PUT /api/orders/{id}
    public function update(Request $request, $id)
    {
        $order = Order::findOrFail($id);
        $order->update($request->all());

        return response()->json($order);
    }

    // 🔹 DELETE /api/orders/{id}
    public function destroy($id)
    {
        $order = Order::findOrFail($id);
        $order->delete();

        return response()->json(['message' => 'Order deleted successfully']);
    }
}
