<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/payments",
     *     summary="Get payment list of payments",
     *     tags={"Payments"},
     *
     *     @OA\Response(response=200, description="OK")
     * )
     */
    public function index()
    {
        try {
            $payments = Payment::with('order')->get();

            return ApiResponse::success($payments, 'All payments retrieved');
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/payments",
     *     summary="Create payment",
     *     tags={"Payments"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"order_id","amount"},
     *
     *             @OA\Property(property="order_id", type="integer", example=1),
     *             @OA\Property(property="amount", type="number", example=30000)
     *         )
     *     ),
     *
     *     @OA\Response(response=201, description="Payment created")
     * )
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'order_id' => 'required|exists:orders,id',
                'amount' => 'required|numeric|min:0',
                'method' => 'required|string',
                'status' => 'required|string|in:paid,failed',
            ]);

            $order = Order::findOrFail($validated['order_id']);

            $order->update([
                'status' => $validated['status'] === 'paid' ? 'paid' : 'pending',
            ]);

            return ApiResponse::success($order, 'Payment status updated', 200);

        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/payments/{id}",
     *     summary="Get payment by ID",
     *     tags={"Payments"},
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="OK"),
     *     @OA\Response(response=404, description="Not Found")
     * )
     */
    public function show($id)
    {
        try {
            $order = Order::with('items')->findOrFail($id);

            return ApiResponse::success($order, 'Order detail retrieved');
        } catch (\Throwable $th) {
            return ApiResponse::error('Order not found', 404);
        }
    }
}
