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
        public function index(Request $request)
    {
        try {
            $orders = Order::with('items.product', 'items.series')
                ->where('user_id', $request->user()->id)
                ->get();

            return ApiResponse::success($orders, 'All orders retrieved');

        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage(), 500);
        }
    }
    
    //store method
    /**
     * @OA\Post(
     *    path="/api/orders",
     *    summary="Create order with product sizes",
     *    tags={"Orders"},
     *    @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(
     *          example={
     *             "user_id": 1,
     *             "items": {
     *                {"product_id": 12, "size": "M", "quantity": 2}
     *             }
     *          }
     *       )
     *    ),
     *    @OA\Response(response=201, description="Order created")
     * )
     */

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|exists:users,id',
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.size' => 'required|string',
                'items.*.quantity' => 'required|integer|min:1',
            ]);

            $totalPrice = 0;

            foreach ($validated['items'] as $item) {

                $product = Product::findOrFail($item['product_id']);

                $sizeData = $product->sizes()->where('size', $item['size'])->first();

                if (!$sizeData) {
                    return ApiResponse::error(
                        "Size {$item['size']} tidak tersedia untuk produk {$product->name}",
                        400
                    );
                }

                // Cek stok HANYA untuk ready stock
                if ($product->stock_type === 'ready' && $sizeData->stock < $item['quantity']) {
                    return ApiResponse::error(
                        "Stok size {$item['size']} tidak mencukupi. Sisa: {$sizeData->stock}",
                        400
                    );
                }

                $totalPrice += $product->price * $item['quantity'];
            }


            // Buat order
            $order = Order::create([
                'user_id' => $validated['user_id'],
                'total_price' => $totalPrice,
                'status' => 'pending',
                'order_type' => 'product_size'
            ]);


            // Simpan order items
            foreach ($validated['items'] as $item) {

                $product = Product::findOrFail($item['product_id']);
                $sizeData = $product->sizes()->where('size', $item['size'])->first();

                $order->items()->create([
                    'product_id' => $product->id,
                    'size'       => $item['size'],
                    'quantity'   => $item['quantity'],
                    'price'      => $product->price,
                    'stock_type' => $product->stock_type   // ⭐ NEW
                ]);

                // Hanya kurangi stok jika READY STOCK
                if ($product->stock_type === 'ready') {
                    $sizeData->decrement('stock', $item['quantity']);
                }
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
            $order = Order::with('items.product')->findOrFail($id);

            if ($order->status !== 'paid') {
                return ApiResponse::error(
                    'Hanya order dengan status paid yang dapat dikirim.',
                    400
                );
            }

            $order->update(['status' => 'shipped']);

            // Load ulang agar response lengkap
            $order->load('items.product');

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

            if ($order->status !== 'shipped') {
                return ApiResponse::error(
                    'Hanya order dengan status shipped yang dapat diselesaikan.',
                    400
                );
            }

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
