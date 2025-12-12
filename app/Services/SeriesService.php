<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Series;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SeriesService
{
    public function createSeries(array $data)
{
    return DB::transaction(function () use ($data) {

        // 1. Buat product induk seri
        $product = Product::create([
            'name'         => $data['name'],
            'slug'         => Str::slug($data['name']),
            'price'        => $data['price'],
            'category_id'  => $data['category_id'] ?? 4,
            'stock_type'   => 'ready',
            'is_seri'      => true,
            'product_code' => $this->generateSeriesCodeWithPrefix('SP'),
        ]);

        // 2. Buat record series
        $series = Series::create([
            'product_id'  => $product->id,
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'price'       => $data['price'],
            'series_code' => 'SER-' . strtoupper(Str::random(6)),
        ]);


        // 3. Insert size-based items
        if (!empty($data['items'])) {
            foreach ($data['items'] as $item) {
                $series->items()->create([
                    'size'     => $item['size'],
                    'quantity' => $item['quantity'],
                ]);
            }
        }

        // 4. Insert bundled products
        if (!empty($data['bundle_products'])) {
            foreach ($data['bundle_products'] as $bp) {
                $series->products()->attach(
                    $bp['product_id'],
                    ['quantity' => $bp['quantity']]
                );
            }
        }

        // 5. AMBIL THUMBNAIL OTOMATIS
        $thumbnail = null;

        // Prioritas 1: Produk bundle pertama
        if (!empty($data['bundle_products'])) {
            $firstProductId = $data['bundle_products'][0]['product_id'];

            // Ambil langsung dari product.image_url
            $thumbnail = Product::where('id', $firstProductId)->value('image_url');

            // Kalau null → ambil ProductImage pertama
            if (!$thumbnail) {
                $thumbnail = ProductImage::where('product_id', $firstProductId)
                    ->orderBy('id', 'asc')
                    ->value('image_url');
            }
        }

        // Prioritas 2: fallback ke gambar product asal jika ada
        if (!$thumbnail) {
            $thumbnail = $product->image_url;
        }

        // Update jika dapat thumbnail
        if ($thumbnail) {
            $product->update(['image_url' => $thumbnail]);
            $series->update(['thumbnail' => $thumbnail]);
        }

        return $series->fresh()->load(['product', 'items', 'products']);
    });
}
    private function generateSeriesCodeWithPrefix($prefix)
    {
        do {
            $code = $prefix . '-' . strtoupper(Str::random(6));
        } while (Series::where('series_code', $code)->exists());

        return $code;
    }
}
