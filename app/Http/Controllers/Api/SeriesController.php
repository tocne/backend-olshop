<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Series;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Helpers\ApiResponse;

class SeriesController extends Controller
{
    // ==========================================================
    // GET ALL SERIES
    // ==========================================================
    public function index()
    {
        try {
            $series = Series::with(['product', 'items', 'products'])->get();

            return ApiResponse::success($series, 'Series list retrieved');
        } catch (\Throwable $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    // ==========================================================
    // GET ONE SERIES
    // ==========================================================
    public function show($id)
    {
        try {
            $series = Series::with(['product', 'items', 'products'])->find($id);

            if (! $series) {
                return ApiResponse::error('Series not found', 404);
            }

            return ApiResponse::success($series, 'Series found');
        } catch (\Throwable $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }
public function byProduct($productId)
{
    $series = Series::with('items.product')
        ->where('product_id', $productId)
        ->first();

    if (! $series) {
        return response()->json([
            'success' => false,
            'message' => 'Series not found'
        ], 404);
    }

    return response()->json([
        'success' => true,
        'data' => $series
    ]);
}

    // ==========================================================
    // CREATE SERIES
    // ==========================================================
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name'        => 'required|string|max:255',
                'description' => 'nullable|string',
                'price'       => 'required|numeric|min:0',
                'category_id' => 'required|exists:categories,id',

                'items'                 => 'nullable|array',
                'items.*.size'          => 'required|string',
                'items.*.quantity'      => 'required|integer|min:1',

                'bundle_products'       => 'nullable|array',
                'bundle_products.*.product_id' => 'required|exists:products,id',
                'bundle_products.*.quantity'   => 'required|integer|min:1',
            ]);

            $series = DB::transaction(function () use ($validated) {

                // 1. Create product for series
                $product = Product::create([
                    'name'        => $validated['name'],
                    'slug'        => Str::slug($validated['name']),
                    'price'       => $validated['price'],
                    'category_id' => $validated['category_id'],
                    'stock_type'  => 'ready',
                    'is_seri'     => true,
                    'product_code'=> $this->generateSeriesCode('SP'),
                ]);

                // 2. Create series record
                $series = Series::create([
                    'product_id'  => $product->id,
                    'name'        => $validated['name'],
                    'description' => $validated['description'] ?? null,
                    'price'       => $validated['price'],
                    'series_code' => 'SER-' . strtoupper(Str::random(6)),
                ]);

                // 3. INSERT ITEMS (size-based)
                if (!empty($validated['items'])) {
                    foreach ($validated['items'] as $item) {
                        $series->items()->create([
                            'size'     => $item['size'],
                            'quantity' => $item['quantity'],
                        ]);
                    }
                }

                // 4. INSERT BUNDLE PRODUCTS
                if (!empty($validated['bundle_products'])) {
                    foreach ($validated['bundle_products'] as $bp) {
                        $series->products()->attach(
                            $bp['product_id'],
                            ['quantity' => $bp['quantity']]
                        );
                    }
                }

                // 5. THUMBNAIL LOGIC
                $thumbnail = $this->resolveThumbnail($validated);

                if ($thumbnail) {
                    $series->update(['thumbnail' => $thumbnail]);
                    $product->update(['image_url' => $thumbnail]);
                }

                return $series->fresh()->load(['product', 'items', 'products']);
            });

            return ApiResponse::success($series, 'Series created successfully');

        } catch (\Throwable $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    // ==========================================================
    // UPDATE SERIES (optional fields)
    // ==========================================================
    public function update(Request $request, $id)
    {
        try {
            $series = Series::with('product')->find($id);

            if (! $series) {
                return ApiResponse::error('Series not found', 404);
            }

            $validated = $request->validate([
                'name'        => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'price'       => 'nullable|numeric|min:0'
            ]);

            $series->update($validated);

            if (isset($validated['name'])) {
                $series->product->update([
                    'name' => $validated['name'],
                    'slug' => Str::slug($validated['name'])
                ]);
            }

            if (isset($validated['price'])) {
                $series->product->update(['price' => $validated['price']]);
            }

            return ApiResponse::success(
                $series->fresh()->load(['product', 'items', 'products']),
                'Series updated successfully'
            );

        } catch (\Throwable $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    // ==========================================================
    // DELETE ONE SERIES
    // ==========================================================
    public function destroy($id)
    {
        try {
            $series = Series::with(['product', 'products', 'items'])->find($id);

            if (! $series) {
                return ApiResponse::error('Series not found', 404);
            }

            DB::transaction(function () use ($series) {

                $series->products()->detach();
                $series->items()->delete();

                if ($series->product) {
                    $series->product->delete();
                }

                $series->delete();
            });

            return ApiResponse::success(null, 'Series deleted successfully');

        } catch (\Throwable $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    // ==========================================================
    // DELETE ALL SERIES
    // ==========================================================
    public function destroyAll()
    {
        try {
            DB::transaction(function () {

                DB::table('series_products')->delete();
                DB::table('series_items')->delete();
                Product::where('is_seri', true)->delete();
                Series::truncate();
            });

            return ApiResponse::success(null, 'All series deleted successfully');

        } catch (\Throwable $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    // ==========================================================
    // HELPER: GENERATE SP CODE
    // ==========================================================
    private function generateSeriesCode($prefix)
    {
        return $prefix . strtoupper(Str::random(6));
    }

    // ==========================================================
    // HELPER: RESOLVE THUMBNAIL
    // ==========================================================
    private function resolveThumbnail($data)
    {
        $thumbnail = null;

        // Prioritas 1: Produk bundle pertama
        if (!empty($data['bundle_products'])) {
            $firstId = $data['bundle_products'][0]['product_id'];

            $thumbnail = Product::where('id', $firstId)->value('image_url');

            if (!$thumbnail) {
                $thumbnail = ProductImage::where('product_id', $firstId)
                    ->orderBy('id')
                    ->value('image_url');
            }
        }

        return $thumbnail;
    }
}
