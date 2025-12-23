<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\Payment;

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
                'errors' => $validator->errors(),
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
        if (! $address) {
            return response()->json([
                'status' => false,
                'message' => 'Alamat diperlukan.',
            ], 422);
        }

        // 3. SIMPAN ORDER
        $order = Order::create([
            'order_code' => Order::generateOrderCode(),
            'user_id' => $user ? $user->id : null,
            'customer_name' => $customerName,
            'customer_phone' => $customerPhone,
            'address' => $address,
            'notes' => $request->notes,
            'subtotal' => $request->subtotal,
            'status' => 'pending',
        ]);

        $payment = Payment::create([
            'order_id' => $order->id,
            'amount' => $order->subtotal, // atau total + ongkir nanti
            'method' => $request->payment_method ?? 'transfer',
            'status' => 'pending',
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

        foreach ($validated['items'] as $item) {

            $product = Product::findOrFail($item['product_id']);
            $sizeData = $product->sizes()->where('size', $item['size'])->first();

            if ($sizeData->stock < $item['quantity']) {
                return ApiResponse::error(
                    "Stok size {$item['size']} untuk {$product->name} sudah habis.",
                    400
                );
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Order berhasil dibuat',
            'data' => $order->load('items', 'payments'),
        ]);

    }
}
