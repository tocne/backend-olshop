<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductSize;
use App\Services\SupabaseUploader;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/products",
     *     summary="Get list of products",
     *     tags={"Products"},
     *
     *     @OA\Response(response=200, description="OK")
     * )
     */
    public function index()
    {
        try {
            $products = Product::with(['category', 'sizes', 'colors'])
    ->where(function ($q) {

        // READY biasa (harus punya size)
        $q->where(function ($r) {
            $r->where('stock_type', 'ready')
              ->whereHas('sizes');
        })

        // READY seri (tidak butuh sizes)
        ->orWhere(function ($r) {
            $r->where('stock_type', 'ready')
              ->where('is_seri', true);
        })

        // PO
        ->orWhere('stock_type', 'po');
    })
    ->get();


            return ApiResponse::success($products, 'All products retrieved');

        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *    path="/api/products",
     *    summary="Create product with multiple sizes",
     *    tags={"Products"},
     *
     *    @OA\RequestBody(
     *       required=true,
     *
     *       @OA\JsonContent(
     *          example={
     *             "name": "Dress Anak",
     *             "description": "Dress lucu",
     *             "price": 95000,
     *             "category_id": 1,
     *             "color": "Pink",
     *             "sizes": {
     *                 {"size": "S", "stock": 10},
     *                 {"size": "M", "stock": 15},
     *                 {"size": "L", "stock": 7}
     *             }
     *          }
     *       )
     *    ),
     *
     *    @OA\Response(response=201, description="Product created")
     * )
     */
    public function store(Request $request)
    {
        try {

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'price' => 'required|numeric',
                'category_id' => 'required|exists:categories,id',
                'category_prefix' => 'required|string|max:3',

                // MULTI COLORS
                'colors' => 'nullable|array',
                'colors.*' => 'string|max:50',

                // STOCK TYPE
                'stock_type' => 'required|in:ready,po',
                'po_estimate_days' => 'required_if:stock_type,po|nullable|integer|min:1',
                'po_notes' => 'nullable|string',

                // SIZE ONLY IF READY
                'sizes' => 'required_if:stock_type,ready|array',
                'sizes.*.size' => 'required_if:stock_type,ready|string|max:20',
                'sizes.*.stock' => 'required_if:stock_type,ready|integer|min:0',

                'image' => 'nullable|image|max:2048',
            ]);

            // Upload image
            $image_url = null;
            if ($request->hasFile('image')) {
                $image_url = SupabaseUploader::upload($request->file('image'), 'products');
            }

            // Generate SKU
            $prefix = strtoupper($validated['category_prefix']);
            $last = Product::where('product_code', 'like', $prefix.'%')
                ->orderBy('product_code', 'desc')
                ->first();

            $newNumber = $last
                ? str_pad(intval(substr($last->product_code, strlen($prefix))) + 1, 3, '0', STR_PAD_LEFT)
                : '001';

            $skuBase = $prefix.$newNumber;

            // Total stock (READY only)
            $totalStock = ($validated['stock_type'] === 'ready')
                ? array_sum(array_column($validated['sizes'], 'stock'))
                : 0;
            $validated['slug'] = Str::slug($validated['name']);
            // Create product
            $product = Product::create([
                'name' => $validated['name'],
                'slug' => $validated['slug'],
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

            // MULTIPLE IMAGES WITH ORDER
            if ($request->hasFile('images')) {

                foreach ($request->file('images') as $index => $file) {

                    $url = SupabaseUploader::upload($file, 'products');

                    $product->images()->create([
                        'image_url' => $url,
                        'order' => $index,
                    ]);
                }
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

            // Insert Colors (optional)
            if (! empty($validated['colors'])) {
                foreach ($validated['colors'] as $color) {
                    $product->colors()->create([
                        'color_name' => ucfirst(strtolower($color)),
                    ]);
                }
            }

            return ApiResponse::success(
                $product->load(['sizes', 'colors']),
                'Product created successfully'
            );

        } catch (\Throwable $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/products/{product}",
     *     summary="Get product by ID",
     *     tags={"Products"},
     *
     *     @OA\Parameter(
     *         name="product",
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
    public function show($id)
    {
        try {
            $product = Product::with(['category', 'sizes', 'colors', 'images'])->find($id);

            if (! $product) {
                return ApiResponse::error('Product not found', 404);
            }

            return ApiResponse::success($product, 'Product found');
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage(), 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/products/{product}",
     *     summary="Update product",
     *     tags={"Products"},
     *
     *     @OA\Parameter(
     *         name="product",
     *         in="path",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="price", type="number")
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="Product updated")
     * )
     */
    public function update(Request $request, $id)
    {
        try {
            $product = Product::with(['sizes', 'colors'])->findOrFail($id);

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'price' => 'required|numeric',
                'category_id' => 'required|exists:categories,id',

                // MULTI COLORS
                'colors' => 'nullable|array',
                'colors.*' => 'string|max:50',

                // STOCK TYPE
                'stock_type' => 'required|in:ready,po',
                'po_estimate_days' => 'required_if:stock_type,po|nullable|integer|min:1',
                'po_notes' => 'nullable|string',

                // SIZE ONLY IF READY
                'sizes' => 'required_if:stock_type,ready|array',
                'sizes.*.size' => 'required_if:stock_type,ready|string|max:20',
                'sizes.*.stock' => 'required_if:stock_type,ready|integer|min:0',

                'image' => 'nullable|image|max:2048',
            ]);

            // Upload image (optional)
            $image_url = $product->image_url;
            if ($request->hasFile('image')) {
                $image_url = SupabaseUploader::upload($request->file('image'), 'products');
            }

            // Total stock READY only
            $totalStock = ($validated['stock_type'] === 'ready')
                ? array_sum(array_column($validated['sizes'], 'stock'))
                : 0;

            // Update product
            $product->update([
                'name' => $validated['name'],
                'description' => $validated['description'],
                'price' => $validated['price'],
                'category_id' => $validated['category_id'],

                'stock' => $totalStock,

                'stock_type' => $validated['stock_type'],
                'po_estimate_days' => $validated['stock_type'] === 'po'
                    ? ($validated['po_estimate_days'] ?? null)
                    : null,
                'po_notes' => $validated['stock_type'] === 'po'
                    ? ($validated['po_notes'] ?? null)
                    : null,

                'image_url' => $image_url,
            ]);

            // Delete old images if provided
            if ($request->has('delete_images')) {
                foreach ($request->delete_images as $imgId) {
                    ProductImage::where('id', $imgId)->delete();
                }
            }

            // Add new images
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $file) {
                    $url = SupabaseUploader::upload($file, 'products');

                    $product->images()->create([
                        'image_url' => $url,
                        'order' => 999, // temporary, will reorder later
                    ]);
                }
            }

            // Reorder
            if ($request->has('order')) {
                foreach ($request->order as $imgId => $position) {
                    ProductImage::where('id', $imgId)->update(['order' => $position]);
                }
            }

            /*
            |--------------------------------------------------------------------------
            | UPDATE SIZES (Replace all)
            |--------------------------------------------------------------------------
            */
            $product->sizes()->delete();

            if ($validated['stock_type'] === 'ready') {
                foreach ($validated['sizes'] as $s) {
                    $product->sizes()->create([
                        'size' => strtoupper($s['size']),
                        'stock' => $s['stock'],
                        'barcode' => $product->product_code.'-'.strtoupper($s['size']),
                    ]);
                }
            }

            /*
            |--------------------------------------------------------------------------
            | UPDATE COLORS (Replace all)
            |--------------------------------------------------------------------------
            */
            $product->colors()->delete();

            if (! empty($validated['colors'])) {
                foreach ($validated['colors'] as $color) {
                    $product->colors()->create([
                        'color_name' => ucfirst(strtolower($color)),
                    ]);
                }
            }

            return ApiResponse::success(
                $product->load(['sizes', 'colors']),
                'Product updated successfully'
            );

        } catch (\Throwable $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/products/add-size-stock",
     *     summary="Add stock to a product size by barcode",
     *     tags={"Products"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="barcode", type="string"),
     *             @OA\Property(property="quantity", type="integer")
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="Size stock updated")
     * )
     */
    public function addSizeStock(Request $request)
    {
        $request->validate([
            'barcode' => 'required',
            'quantity' => 'required|integer|min:1',
        ]);

        // Cari size berdasarkan barcode
        $size = ProductSize::where('barcode', $request->barcode)->first();

        if (! $size) {
            return ApiResponse::error('Size not found', 404);
        }

        // Tambah stok size
        $size->stock += $request->quantity;
        $size->save();

        // Update stok total produk (sum semua size)
        $product = $size->product;
        $product->stock = $product->sizes()->sum('stock');
        $product->save();

        return ApiResponse::success(
            [
                'product' => $product->load('sizes'),
                'updated_size' => $size,
            ],
            'Size stock updated successfully'
        );
    }

    /**
     * @OA\Delete(
     *     path="/api/products/{product}",
     *     summary="Delete product",
     *     tags={"Products"},
     *
     *     @OA\Parameter(
     *         name="product",
     *         in="path",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(response=200, description="Product deleted")
     * )
     */
    public function destroy($id)
    {
        try {
            $product = Product::findOrFail($id);
            $product->delete();

            return ApiResponse::success(null, 'Product deleted successfully');
        } catch (\Throwable $th) {
            return ApiResponse::error('Product not found', 404);
        }
    }

    public function getProductsByCategory($categoryPrefix)
    {
        try {
            // Cari kategori berdasarkan prefix
            $category = Category::where('prefix', $categoryPrefix)->firstOrFail();

            // Ambil produk berdasarkan kategori
            $products = $category->products; // Asumsi Anda sudah punya hubungan "products" pada model Category

            return ApiResponse::success($products, 'Products retrieved successfully');
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage(), 500);
        }
    }
}
