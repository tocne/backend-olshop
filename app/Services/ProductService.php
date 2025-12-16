<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Str;
use App\Services\SupabaseUploader;

class ProductService
{
    public function create(array $validated, $image = null, array $images = [])
    {
        // Upload main image
        $image_url = null;
        if ($image) {
            $image_url = SupabaseUploader::upload($image, 'products');
        }

        // Generate SKU
        $prefix = strtoupper($validated['category_prefix']);
        $last = Product::where('product_code', 'like', $prefix.'%')
            ->orderBy('product_code', 'desc')
            ->first();

        $newNumber = $last
            ? str_pad(
                intval(substr($last->product_code, strlen($prefix))) + 1,
                3,
                '0',
                STR_PAD_LEFT
            )
            : '001';

        $skuBase = $prefix.$newNumber;

        // Total stock (READY only)
        $totalStock = ($validated['stock_type'] === 'ready')
            ? array_sum(array_column($validated['sizes'], 'stock'))
            : 0;

        // Create product
        $product = Product::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'],
            'price' => $validated['price'],
            'category_id' => $validated['category_id'],
            'stock' => $totalStock,
            'product_code' => $skuBase,
            'stock_type' => $validated['stock_type'],
            'po_estimate_days' => $validated['stock_type'] === 'po'
                ? ($validated['po_estimate_days'] ?? null)
                : null,
            'po_notes' => $validated['stock_type'] === 'po'
                ? ($validated['po_notes'] ?? null)
                : null,
            'image_url' => $image_url,
        ]);

        // Multiple images
        foreach ($images as $index => $file) {
            $url = SupabaseUploader::upload($file, 'products');

            $product->images()->create([
                'image_url' => $url,
                'order' => $index,
            ]);
        }

        // Insert sizes (READY only)
        if ($validated['stock_type'] === 'ready') {
            foreach ($validated['sizes'] as $s) {
                $product->sizes()->create([
                    'size' => strtoupper($s['size']),
                    'stock' => $s['stock'],
                    'barcode' => $skuBase.'-'.strtoupper($s['size']),
                ]);
            }
        }

        // Insert colors
        if (! empty($validated['colors'])) {
            foreach ($validated['colors'] as $color) {
                $product->colors()->create([
                    'color_name' => ucfirst(strtolower($color)),
                ]);
            }
        }

        return $product;
    }
}
