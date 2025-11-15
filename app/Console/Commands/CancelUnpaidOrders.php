<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use Carbon\Carbon;

class CancelUnpaidOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:cancel-unpaid';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Batalkan pesanan yang belum dibayar lebih dari 1 jam dan kembalikan stok produk';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $expiredOrders = Order::where('status', 'pending')
            ->where('created_at', '<=', now()->subHour())
            ->with('items.product')
            ->get();

        foreach ($expiredOrders as $order) {
            foreach ($order->items as $item) {
                $product = $item->product;
                $product->increment('stock', $item->quantity);
            }

            $order->update(['status' => 'cancelled']);
        }

        $this->info(count($expiredOrders) . ' order dibatalkan otomatis.');
    }
}
