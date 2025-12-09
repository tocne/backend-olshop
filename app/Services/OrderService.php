<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class OrderService
{
    /* ============================================
     |  UTILITY: Generate Order Code
     |============================================ */
    public function generateOrderCode()
    {
        $last = Order::orderBy('id', 'desc')->first();
        $next = str_pad(($last->id ?? 0) + 1, 4, '0', STR_PAD_LEFT);

        return 'ORD-'.date('Ymd').'-'.$next;
    }

    /* ============================================
     |  UTILITY: Hitung subtotal dari items request
     |============================================ */
    public function calculateSubtotal(array $items)
    {
        $total = 0;

        foreach ($items as $item) {
            $product = Product::findOrFail($item['product_id']);
            $total += $product->price * $item['quantity'];
        }

        return $total;
    }

    /* ============================================
     |  UTILITY: Create base order
     |  (dipakai semua mode: ready, pilsuk, seri, po)
     |============================================ */
    public function createBaseOrder($user, array $data, string $orderType)
    {
        $subtotal = $this->calculateSubtotal($data['items']);
        $shipping = $data['shipping_cost'] ?? 0;

        return Order::create([
            'user_id' => $user?->id,
            'order_code' => $this->generateOrderCode(),
            'order_type' => $orderType,
            'status' => 'pending',

            'customer_name' => $data['customer_name'] ?? null,
            'customer_phone' => $data['customer_phone'] ?? null,
            'address' => $data['address'] ?? null,
            'notes' => $data['notes'] ?? null,
            'payment_method' => $data['payment_method'] ?? null,

            'subtotal' => $subtotal,
            'shipping_cost' => $shipping,
            'total' => $subtotal + $shipping,
        ]);
    }

    /* ============================================
     |  UTILITY: Insert items dengan snapshot
     |============================================ */
    public function insertOrderItems($order, array $items, $reduceStock = true)
{
    foreach ($items as $item) {

        $product = Product::findOrFail($item['product_id']);

        $price = $product->price; // ambil harga asli

        $total = $price * $item['quantity'];

        // Insert item
        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => $item['quantity'],
            'price' => $price,
            'total_price' => $total,
        ]);

        // Reduce stock only if ready
        if ($reduceStock && $product->stock_type === 'ready') {
            $product->decrement('stock', $item['quantity']);
        }
    }
}


    public function insertSeriesSnapshot(Order $order, Series $series, int $qty)
    {
        // snapshot items (Model A)
        foreach ($series->items as $item) {
            $order->items()->create([
                'series_id' => $series->id,
                'product_id' => $series->product_id,
                'product_name' => $series->name,
                'size' => $item->size,
                'quantity' => $item->quantity * $qty,
                'price' => $series->price,
                'total_price' => ($series->price * $qty),
            ]);
        }

        // snapshot bundle products (Model B)
        foreach ($series->products as $p) {
            $order->items()->create([
                'series_id' => $series->id,
                'product_id' => $p->id,
                'product_name' => $p->name,
                'quantity' => $p->pivot->quantity * $qty,
                'price' => $p->price,
                'total_price' => ($p->price * $p->pivot->quantity * $qty),
            ]);
        }
    }

    /* ============================================
     |  READY ORDER MODE
     |============================================ */
    public function createReadyOrder($user, array $data)
    {
        return DB::transaction(function () use ($user, $data) {

            // Create main order
            $order = $this->createBaseOrder($user, $data, 'normal');

            // Insert items snapshot
            $this->insertOrderItems($order, $data['items']);

            return $order;
        });
    }

    /* ============================================
     |  PILSUK ORDER MODE
     |============================================ */
    public function createPilsukOrder($user, array $data)
    {
        return DB::transaction(function () use ($user, $data) {

            $order = $this->createBaseOrder($user, $data, 'pilsuk');

            $this->insertOrderItems($order, $data['items']);

            return $order;
        });
    }

    /* ============================================
     |  SERI ORDER MODE
     |============================================ */
    public function createSeriOrder($user, array $data)
    {
        return DB::transaction(function () use ($user, $data) {

            $product = Product::findOrFail($data['product_id']);

            $series = $product->series()->with(['items', 'products'])->first();

            if (! $series) {
                throw new \Exception('Produk ini tidak memiliki seri.');
            }

            // create base order
            $order = $this->createBaseOrder($user, $data, 'seri');

            // insert snapshot seri
            $this->insertSeriesSnapshot($order, $series, $data['quantity']);

            return $order;
        });
    }

}
