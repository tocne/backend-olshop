<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Series;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\ProductImage;


class SeriesService
{
    public function createSeries(array $data)
    {
        return DB::transaction(function () use ($data) {

            // Auto create product for this series
            $product = Product::create([
                'name' => $data['name'],
                'slug' => Str::slug($data['name']),
                'price' => $data['price'],
                'category_id' => $data['category_id'] ?? 1,
                'stock_type' => $data['stock_type'] ?? 'ready',
                'is_seri' => true,
                'product_code' => $this->generateSeriesCodeWithPrefix('SP'),
            ]);

            // Create series detail
            $series = Series::create([
                'product_id' => $product->id,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'price' => $data['price'],
                'series_code' => 'SER-'.strtoupper(Str::random(6)),
            ]);

            if (! empty($data['product_ids'])) {
                $firstProductId = $data['product_ids'][0];

                $thumbnail = ProductImage::where('product_id', $firstProductId)
                    ->where('is_primary', true)
                    ->value('image_url');

                if (! $thumbnail) {
                    $thumbnail = ProductImage::where('product_id', $firstProductId)
                        ->value('image_url');
                }

                $series->update(['thumbnail' => $thumbnail]);
            }

            // Add Model A (size-based items)
            if (! empty($data['items'])) {
                foreach ($data['items'] as $item) {
                    $series->items()->create([
                        'size' => $item['size'],
                        'quantity' => $item['quantity'],
                    ]);
                }
            }

            // Add Model B (bundle products)
            if (! empty($data['bundle_products'])) {
                foreach ($data['bundle_products'] as $bp) {
                    $series->products()->attach(
                        $bp['product_id'],
                        ['quantity' => $bp['quantity']]
                    );
                }
            }

            return $series->load(['product', 'items', 'products']);
        });
    }

    private function generateSeriesCode()
    {
        return 'SER-'.rand(1000, 9999);
    }
}
