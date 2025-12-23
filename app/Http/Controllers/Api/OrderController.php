<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
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

    public function adminIndex(Request $request)
    {
        try {
            $query = Order::with('items.product');

            // 🔥 FILTER UNTUK BULK PRINT
            if ($request->filled('ids')) {
                $ids = explode(',', $request->ids);
                $query->whereIn('id', $ids);
            }

            $orders = $query->orderBy('id', 'desc')->get();

            return ApiResponse::success($orders, 'Orders retrieved');
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage(), 500);
        }
    }

    public function active(Request $request)
{
    try {
        $user = $request->user();

        if (!$user) {
            return ApiResponse::error('Unauthorized', 401);
        }

        $order = Order::where('user_id', $user->id)
            ->whereIn('status', [
                'awaiting_payment',
                'paid',
                'shipped',
            ])
            ->orderBy('created_at', 'desc')
            ->first();

        return ApiResponse::success($order, 'Active order retrieved');
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

    public function checkout(Request $request, OrderService $orderService)
    {
        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:30',
            'address' => 'required|string',
            'notes' => 'nullable|string',
            'payment_method' => 'nullable|string|in:transfer,qris',

            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.product_size_id' => 'required|exists:product_sizes,id',
            'items.*.size' => 'required|string|max:10',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|integer|min:0',
        ]);

        try {
            $order = $orderService->createReadyOrder(
                auth()->user(),
                $validated
            );

            return ApiResponse::success(
                ['order_code' => $order->order_code],
                'Order berhasil dibuat'
            );

        } catch (\Throwable $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    protected function handleStore(Request $request)
    {
        try{
            $user = $request->user();
            $mode = strtolower($request->input('order_mode'));

            if (! in_array($mode, ['ready', 'pilsuk', 'seri'])) {
                return ApiResponse::error('Mode order tidak dikenal', 400);
            }

            if ($mode !== 'seri' && ! $request->has('items')) {
                return ApiResponse::error('Items wajib diisi', 422);
            }

            if ($mode === 'seri' && ! $request->has('series_id')) {
                return ApiResponse::error('Series wajib dipilih', 422);
            }

            if ($mode === 'seri') {
                $request->validate([
                    'series_id' => 'required|integer|exists:series,id',
                    'quantity' => 'nullable|integer|min:1',
                    'customer_name' => 'required|string',
                    'customer_phone' => 'required|string',
                    'address' => 'required|string',
                    'notes' => 'nullable|string',
                    'shipping_cost' => 'nullable|numeric|min:0',
                ]);
            }

            // data dasar (shared)
            $baseData = $request->only([
                'customer_name',
                'customer_phone',
                'address',
                'notes',
                'shipping_cost',
            ]);

            $baseData['shipping_cost'] = $baseData['shipping_cost'] ?? 0;

            return match ($mode) {

                // ================= READY =================
                'ready' => new OrderResource(
                    $this->orderService
                        ->createReadyOrder($user, array_merge(
                            $baseData,
                            ['items' => $request->input('items')]
                        ))
                        ->load(['items', 'payments'])
                ),

                // ================= PILSUK =================
                'pilsuk' => new OrderResource(
                    $this->orderService
                        ->createPilsukOrder($user, array_merge(
                            $baseData,
                            ['items' => $request->input('items')]
                        ))
                        ->load(['items', 'payments'])
                ),

                // ================= SERI =================
                'seri' => new OrderResource(
                    $this->orderService
                        ->createSeriOrder($user, array_merge(
                            $baseData,
                            [
                                'series_id' => $request->input('series_id'),
                                'quantity' => $request->input('quantity'),
                            ]
                        ))
                        ->load(['items', 'payments'])
                ),
            };
            
        } catch (\Throwable $e) {

        // 🔥 INI KUNCI AGAR ERROR MUNCUL DI FRONTEND
        return ApiResponse::error(
            $e->getMessage(),
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
    public function store(Request $request)
    {
        return $this->handleStore($request);
    }

    public function markAsPaid($id)
    {
        try {
            $order = Order::with('payments')->findOrFail($id);

            // ✅ hanya order menunggu pembayaran
            if ($order->status !== 'awaiting_payment') {
                return ApiResponse::error(
                    'Only orders awaiting payment can be marked as paid.',
                    400
                );
            }

            // ✅ ambil payment pending terakhir
            $payment = $order->payments()
                ->where('status', 'pending')
                ->latest()
                ->first();

            if (! $payment) {
                return ApiResponse::error(
                    'No pending payment found for this order.',
                    400
                );
            }

            // ✅ update payment & order
            $payment->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            $order->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            return ApiResponse::success([
                'order' => $order,
                'payment' => $payment,
            ], 'Order successfully marked as paid.');

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
    public function ship(Request $request, $id)
    {
        try {
            $order = Order::findOrFail($id);

            if ($order->status !== 'paid') {
                return ApiResponse::error(
                    'Hanya order dengan status paid yang dapat dikirim.',
                    400
                );
            }

            // SET SEMUA SEKALIGUS
            $order->status = 'shipped';
            $order->shipped_at = now(); // ⬅️ OTOMATIS
            $order->shipping_note = $request->shipping_note; // OPSIONAL
            $order->save();

            // Load ulang relasi
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
            $order = Order::with(['items.product', 'payments', 'user'])
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
