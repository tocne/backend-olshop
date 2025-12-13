<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\Series;
use Illuminate\Support\Facades\DB;

class OrderService
{
    /* ============================================
     | Generate Order Code
     |============================================ */
    public function generateOrderCode()
    {
        $last = Order::orderBy('id', 'desc')->first();
        $next = str_pad(($last->id ?? 0) + 1, 4, '0', STR_PAD_LEFT);

        return 'INV-' . date('Ymd') . '-' . $next;
    }

    /* ============================================
     | Create Base Order
     | Dipakai semua order mode
     |============================================ */
    public function createBaseOrder($user, array $data, string $orderType)
    {
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

            'subtotal' => 0,
            'shipping_cost' => $data['shipping_cost'] ?? 0,
            'total' => 0,
        ]);
    }

    /* ============================================
     | Insert Order Items Snapshot
     |============================================ */
    public function insertOrderItems(Order $order, array $items, bool $reduceStock = true)
    {
        $subtotal = 0;

        foreach ($items as $item) {

            $product = Product::findOrFail($item['product_id']);
            $price = $product->price;
            $line = $price * $item['quantity'];

            $order->items()->create([
                'product_id' => $product->id,
                'product_name' => $product->name,
                'quantity' => $item['quantity'],
                'price' => $price,
                'total_price' => $line,
            ]);

            if ($reduceStock && $product->stock_type === 'ready') {
                $product->reduceStock($item['quantity']);
            }

            $subtotal += $line;
        }

        return $subtotal;
    }

    /* ============================================
     | Insert Series Snapshot (Model A + Model B)
     |============================================ */
    public function insertSeriesSnapshot(Order $order, Series $series, int $qty)
    {
        $subtotal = 0;

        // MODEL A: Variasi size & quantity
        foreach ($series->items as $item) {
            $line = $series->price * $qty;

            $order->items()->create([
                'series_id' => $series->id,
                'product_id' => $series->product_id,
                'product_name' => $series->name,
                'size' => $item->size,
                'quantity' => $item->quantity * $qty,
                'price' => $series->price,
                'total_price' => $line,
            ]);

            $subtotal += $line;
        }

        // MODEL B: Bundle products
        foreach ($series->products as $p) {
            $line = $p->price * $p->pivot->quantity * $qty;

            $order->items()->create([
                'series_id' => $series->id,
                'product_id' => $p->id,
                'product_name' => $p->name,
                'quantity' => $p->pivot->quantity * $qty,
                'price' => $p->price,
                'total_price' => $line,
            ]);

            $subtotal += $line;
        }

        return $subtotal;
    }

    /* ============================================
     | READY ORDER (NORMAL)
     |============================================ */
    public function createReadyOrder($user, array $data)
    {
        return DB::transaction(function () use ($user, $data) {

            // 1. CREATE ORDER
            $order = Order::create([
                'user_id' => $user?->id,
                'order_code' => $this->generateOrderCode(),
                'order_type' => 'normal',
                'status' => 'pending',

                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
                'address' => $data['address'],
                'notes' => $data['notes'] ?? null,

                'subtotal' => 0,
                'total' => 0,
            ]);

            $subtotal = 0;

            // 2. LOOP ITEMS
            foreach ($data['items'] as $item) {

                $product = Product::findOrFail($item['product_id']);

                // === VARIANT SIZE (KUNCI SISTEM) ===
                $variant = $product->sizes()
                    ->where('id', $item['product_size_id'])
                    ->firstOrFail();

                // === CEK & KURANGI STOK ===
                if ($product->stock_type === 'ready') {
                    if ($variant->stock < $item['quantity']) {
                        throw new \Exception(
                            "Stok ukuran {$variant->size} untuk {$product->name} tidak mencukupi"
                        );
                    }

                    $variant->decrement('stock', $item['quantity']);
                }

                // === HITUNG ===
                $lineTotal = $item['price'] * $item['quantity'];

                // === SIMPAN SNAPSHOT ===
                $order->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'size' => $variant->size,
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'total_price' => $lineTotal,
                    'stock_type' => $product->stock_type,
                ]);

                $subtotal += $lineTotal;
            }

            // 3. UPDATE TOTAL
            $order->update([
                'subtotal' => $subtotal,
                'total' => $subtotal,
            ]);

            return $order;
        });
    }
    /* ============================================
     | PILSUK ORDER
     |============================================ */
    public function createPilsukOrder($user, array $data)
    {
        return DB::transaction(function () use ($user, $data) {

            $order = $this->createBaseOrder($user, $data, 'pilsuk');

            $subtotal = $this->insertOrderItems($order, $data['items']);

            $order->update([
                'subtotal' => $subtotal,
                'total' => $subtotal + ($data['shipping_cost'] ?? 0),
            ]);

            return $order;
        });
    }

    /* ============================================
     | SERI ORDER
     |============================================ */
    public function createSeriOrder($user, array $data)
    {
        return DB::transaction(function () use ($user, $data) {

            $series = Series::with(['items', 'products', 'product'])->findOrFail($data['series_id']);

            $product = $series->product;
            if (! $series) {
                throw new \Exception('Produk ini tidak memiliki seri.');
            }

            $order = $this->createBaseOrder($user, $data, 'seri');

            $subtotal = $this->insertSeriesSnapshot($order, $series, $data['quantity']);
            $subtotal = $series->price * $data['quantity'];
            $order->update([
                'subtotal' => $subtotal,
                'total' => $subtotal + ($data['shipping_cost'] ?? 0),
            ]);

            return $order;
        });
    }
}
