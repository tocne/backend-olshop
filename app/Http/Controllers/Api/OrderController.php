<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Product;
use App\Services\OrderService;
use App\Http\Resources\ProductResource;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    protected $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    /**
     * @OA\Get(
     *     path="/api/orders",
     *     summary="Get list of orders",
     *     tags={"Orders"},
     *
     *     @OA\Response(response=200, description="OK")
     * )
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            if (! $user) {
                return ApiResponse::error('Unauthorized', 401);
            }

            $orders = Order::with('items.product')
                ->where('user_id', $user->id)
                ->orderBy('id', 'desc')
                ->get();

            return ApiResponse::success($orders, 'User orders retrieved');
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage(), 500);
        }
    }

    public function adminIndex()
    {
        try {
            $orders = Order::with(['items.product'])
                ->orderBy('id', 'desc')
                ->get();

            return ApiResponse::success($orders, 'All orders retrieved');
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage(), 500);
        }
    }

    public function showByCode($code)
    {
        try {
            $order = Order::with(['items.product', 'user'])
                ->where('order_code', $code)
                ->firstOrFail();

            return ApiResponse::success($order, 'Order detail retrieved');
        } catch (\Throwable $th) {
            return ApiResponse::error('Order not found', 404);
        }
    }

    public function checkout(Request $request)
    {
        try {
            // VALIDASI
            $validated = $request->validate([
                'customer_name' => 'required|string|max:255',
                'customer_phone' => 'required|string|max:30',
                'address' => 'required|string',
                'notes' => 'nullable|string',
                'subtotal' => 'required|integer|min:0',

                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.product_name' => 'nullable|string',
                'items.*.size' => 'nullable|string|max:10',
                'items.*.color' => 'nullable|string|max:50',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.price' => 'required|integer|min:0',
                'items.*.size_id' => 'nullable|exists:product_sizes,id',
            ]);

            // ===== GENERATE ORDER CODE =====
            $lastOrder = Order::orderBy('id', 'desc')->first();
            $nextNumber = str_pad(($lastOrder->id ?? 0) + 1, 4, '0', STR_PAD_LEFT);

            $orderCode = 'INV-'.date('Ymd').'-'.$nextNumber;

            // ===== CREATE ORDER =====
            $order = Order::create([
                'order_code' => $orderCode,
                'status' => 'pending',
                'order_type' => 'normal',

                'customer_name' => $validated['customer_name'],
                'customer_phone' => $validated['customer_phone'],
                'address' => $validated['address'],
                'notes' => $validated['notes'] ?? null,

                'subtotal' => $validated['subtotal'],
                'total' => $validated['subtotal'],
            ]);

            // ===== INSERT ORDER ITEMS =====
            foreach ($validated['items'] as $item) {

                $product = Product::find($item['product_id']);

                // GET SIZE INFORMATION (IF READY STOCK)
                if ($item['size_id']) {
                    $sizeData = $product->sizes()->find($item['size_id']);

                    if ($product->stock_type === 'ready') {
                        if ($sizeData->stock < $item['quantity']) {
                            return ApiResponse::error(
                                "Stok ukuran {$sizeData->size} tidak mencukupi",
                                400
                            );
                        }

                        // Reduce stock
                        $sizeData->decrement('stock', $item['quantity']);
                    }
                }

                // Save item
                $order->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $item['product_name'] ?? $product->name,
                    'size' => $item['size'],
                    'color' => $item['color'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'stock_type' => $product->stock_type,
                ]);
            }

            return ApiResponse::success(
                ['order_code' => $orderCode],
                'Order created successfully'
            );

        } catch (\Throwable $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    protected function handleStore(Request $request)
    {
        try {
            $user = $request->user();  // null jika guest
            $mode = strtolower($request->order_mode);

            // Daftar mode valid
            $validModes = ['ready', 'pilsuk', 'seri', 'po'];
            if (! in_array($mode, $validModes)) {
                return ApiResponse::error("Mode order tidak dikenal: {$mode}", 400);
            }

            // RULE: Guest tidak boleh PO
            if ($mode === 'po' && ! $user) {
                return ApiResponse::error('Hanya member yang dapat melakukan PO.', 403);
            }

            // Eksekusi sesuai mode
            $order = match ($mode) {
                'ready' => $this->orderService->createReadyOrder($user, $request->all()),
                'pilsuk' => $this->orderService->createPilsukOrder($user, $request->all()),
                'seri' => $this->orderService->createSeriOrder($user, $request->all()),
                'po_seri' => $this->orderService->createPoOrder($user, $request->all()),
            };

            $order->load(['items.product', 'user']);

            return ApiResponse::success(
                new OrderResource($order),
                'Order berhasil dibuat'
            );

        } catch (\Throwable $e) {
            return ApiResponse::error(
                config('app.debug') ? $e->getMessage() : 'Gagal membuat order',
                500
            );
        }
    }

    /**
     * @OA\Post(
     *     path="/api/orders",
     *     summary="Create a new order",
     *     tags={"Orders"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="order_mode", type="string", example="ready"),
     *             // Tambahkan properti lain sesuai kebutuhan
     *         )
     *     ),
     *
     *     @OA\Response(response=201, description="Order created"),
     *     @OA\Response(response=400, description="Bad Request"),
     *     @OA\Response(response=500, description="Internal Server Error")
     * )
     */
    public function create(Request $request)
    {
        return $this->handleStore($request);
    }

    public function markAsPaid($id)
    {
        try {
            $order = Order::findOrFail($id);

            if ($order->status !== 'pending') {
                return ApiResponse::error('Only pending orders can be marked as paid.', 400);
            }

            $order->update(['status' => 'paid']);

            return ApiResponse::success($order, 'Order marked as paid.');
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage(), 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/orders/{id}/ship",
     *     summary="Mark order as shipped",
     *     tags={"Orders"},
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Order shipped")
     * )
     */
    public function ship($id)
    {
        try {
            $order = Order::findOrFail($id);

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
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *
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
     *
     *     @OA\Parameter(
     *         name="order",
     *         in="path",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(response=200, description="OK"),
     *     @OA\Response(response=404, description="Not Found")
     * )
     */
    public function show($identifier)
    {
        try {
            $order = Order::with(['items.product', 'user'])
                ->where('order_code', $identifier) // jika pakai order_code
                ->orWhere('id', $identifier)       // jika pakai ID
                ->firstOrFail();

            return ApiResponse::success($order, 'Order detail retrieved');
        } catch (\Throwable $th) {
            return ApiResponse::error('Order not found', 404);
        }
    }

    public function cancel($id)
    {
        try {
            $order = Order::findOrFail($id);

            if ($order->status !== 'pending') {
                return ApiResponse::error('Only pending orders can be canceled.', 400);
            }

            $order->update(['status' => 'canceled']);

            return ApiResponse::success($order, 'Order has been canceled.');
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage(), 500);
        }
    }
}
