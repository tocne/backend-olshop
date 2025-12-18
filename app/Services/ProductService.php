<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Str;
use App\Services\SupabaseUploader;

class ProductService
{
    public function create(array $validated, $image = null, array $images = [])
    {
        // === AMBIL PREFIX DARI CATEGORY ===
        $category = Category::findOrFail($validated['category_id']);
        $prefix = strtoupper($category->prefix);

        // === GENERATE SKU ===
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

        // === HITUNG STOCK ===
        $totalStock = ($validated['stock_type'] === 'ready')
            ? array_sum(array_column($validated['sizes'], 'stock'))
            : 0;

        // === UPLOAD IMAGE ===
        $image_url = $image
            ? SupabaseUploader::upload($image, 'products')
            : null;

        // === CREATE PRODUCT ===
        $product = Product::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
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

        // === MULTI IMAGES ===
        foreach ($images as $index => $file) {
            $url = SupabaseUploader::upload($file, 'products');
            $product->images()->create([
                'image_url' => $url,
                'order' => $index,
            ]);
        }

        // === SIZES ===
        if ($validated['stock_type'] === 'ready') {
            foreach ($validated['sizes'] as $s) {
                $product->sizes()->create([
                    'size' => strtoupper($s['size']),
                    'stock' => $s['stock'],
                    'barcode' => $skuBase.'-'.strtoupper($s['size']),
                ]);
            }
        }

        // === COLORS ===
        if (!empty($validated['colors'])) {
            foreach ($validated['colors'] as $color) {
                $product->colors()->create([
                    'color_name' => ucfirst(strtolower($color)),
                ]);
            }
        }

        return $product;
    }
}
