<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CheckoutController extends Controller
{
    public function checkout(Request $request)
    {
        $user = Auth::user();

        // 1. VALIDASI
        $validator = Validator::make($request->all(), [
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer',
            'items.*.quantity' => 'required|integer',
            'items.*.price' => 'required|integer',
            'subtotal' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // 2. LOGIKA CHECKOUT
        if ($user) {
            // MEMBER CHECKOUT
            $customerName = $user->name;
            $customerPhone = $user->phone;
            $address = $user->address;
        } else {
            // GUEST CHECKOUT
            $customerName = $request->customer_name;
            $customerPhone = $request->customer_phone;
            $address = $request->address;
        }

        // JIKA TIDAK ADA ALAMAT
        if (!$address) {
            return response()->json([
                'status' => false,
                'message' => 'Alamat diperlukan.'
            ], 422);
        }

        // 3. SIMPAN ORDER
        $order = Order::create([
            'user_id' => $user ? $user->id : null,
            'customer_name' => $customerName,
            'customer_phone' => $customerPhone,
            'address' => $address,
            'notes' => $request->notes,
            'subtotal' => $request->subtotal,
            'status' => 'pending'
        ]);

        // 4. SIMPAN ITEM
        foreach ($request->items as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item['product_id'],
                'product_name' => $item['product_name'],
                'size' => $item['size'] ?? null,
                'quantity' => $item['quantity'],
                'price' => $item['price'],
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Order berhasil dibuat',
            'order_id' => $order->id
        ]);
    }
}
