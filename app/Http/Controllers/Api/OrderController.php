<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Helpers\ApiResponse;

class OrderController extends Controller
{
    
    /**
 * @OA\Get(
 *     path="/api/orders",
 *     summary="Get list of orders",
 *     tags={"Orders"},
 *     @OA\Response(response=200, description="OK")
 * )
 */
    public function index()
    {
        try {
            $orders = Order::with('items.product', 'user')->get();
            return ApiResponse::success($orders, 'All orders retrieved');

        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage(), 500);
        }

    }
    /**
 * @OA\Post(
 *     path="/api/orders",
 *     summary="Create new order",
 *     tags={"Orders"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"product_id","quantity"},
 *             @OA\Property(property="product_id", type="integer", example=1),
 *             @OA\Property(property="quantity", type="integer", example=2)
 *         )
 *     ),
 *     @OA\Response(response=201, description="Order created")
 * )
 */
    public function store(Request $request)
    {
        
        try {
            $validated = $request->validate([
                'user_id' => 'required|exists:users,id',
                'items' => 'required|array',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.quantity' => 'required|integer|min:1',
            ]);

            // Validasi stok
            foreach ($validated['items'] as $item) {
                $product = Product::find($item['product_id']);

                if ($product->stock < $item['quantity']) {
                    return ApiResponse::error(
                        "Stok produk {$product->name} tidak mencukupi. Sisa stok: {$product->stock}",
                        400
                    );
                }
            }

            // Hitung total
            $totalPrice = 0;
            foreach ($validated['items'] as $item) {
                $product = Product::find($item['product_id']);
                $totalPrice += $product->price * $item['quantity'];
            }

            // Buat order
            $order = Order::create([
                'user_id' => $validated['user_id'],
                'total_price' => $totalPrice,
                'status' => 'pending',
            ]);

            // Simpan item + kurangi stok
            foreach ($validated['items'] as $item) {
                $product = Product::find($item['product_id']);

                $order->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'price' => $product->price,
                ]);

                $product->decrement('stock', $item['quantity']);
            }

            return ApiResponse::success(
                $order->load('items.product'),
                'Order created successfully',
                201
            );

        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage(), 500);
        }
    }
/**
 * @OA\Put(
 *     path="/api/orders/{id}/ship",
 *     summary="Mark order as shipped",
 *     tags={"Orders"},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Order shipped")
 * )
 */
    public function ship($id)
    {
        try {
            $order = Order::findOrFail($id);

            // Validasi status
            if ($order->status !== 'paid') {
                return ApiResponse::error(
                    'Hanya order dengan status paid yang dapat dikirim.',
                    400
                );
            }

            $order->update(['status' => 'shipped']);

            return ApiResponse::success($order, 'Pesanan telah dikirim.');

        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage(), 500);
        }
    }

    /**
 * @OA\Put(
 *     path="/api/orders/{id}/complete",
 *     summary="Mark order as complete",
 *     tags={"Orders"},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Order completed")
 * )
 */
    public function complete($id)
    {
        try {
            $order = Order::findOrFail($id);

            // Validasi urutan status
            if ($order->status !== 'shipped') {
                return ApiResponse::error(
                    'Hanya order dengan status shipped yang dapat diselesaikan.',
                    400
            );
        }

        // Update status jadi completed
        $order->update(['status' => 'completed']);
        return ApiResponse::success($order, 'Pesanan telah selesai.');

        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage(), 500);
        }
    }

/**
 * @OA\Get(
 *     path="/api/orders/{order}",
 *     summary="Get order by ID",
 *     tags={"Orders"},
 *     @OA\Parameter(
 *         name="order",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(response=200, description="OK"),
 *     @OA\Response(response=404, description="Not Found")
 * )
 */
    public function show($id)
    {
        try {
             $order = Order::with('items.product', 'user')->findOrFail($id);
             return ApiResponse::success($order, 'Order detail retrieved');
        } catch (\Throwable $th) {
            return ApiResponse::error('Order not found', 404);
        }
    }    
}
