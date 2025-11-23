<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Helpers\ApiResponse;

class ProductController extends Controller
{
    /**
 * @OA\Get(
 *     path="/api/products",
 *     summary="Get list of products",
 *     tags={"Products"},
 *     @OA\Response(response=200, description="OK")
 * )
 */
    public function index()
    {
        try {
            $products = Product::with('category','sizes')->get();
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
 *    @OA\RequestBody(
 *       required=true,
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
 *    @OA\Response(response=201, description="Product created")
 * )
 */

public function store(Request $request)
{
    try {
        // 1️⃣ VALIDATION
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'description'   => 'nullable|string',
            'price'         => 'required|numeric',
            'category_id'   => 'required|exists:categories,id',
            'category_prefix' => 'required|string|max:3',   // PREFIX UNTUK SKU
            'color'         => 'nullable|string|max:50',

            // Size + stock
            'sizes'                 => 'nullable|array',
            'sizes.*.size'          => 'required_with:sizes|string|max:50',
            'sizes.*.stock'         => 'required_with:sizes|integer|min:0',
            'category_id'           => 'required|exists:categories,id',


            // Image
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        // 2️⃣ UPLOAD GAMBAR (JIKA ADA)
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
            $validated['image_url'] = asset('storage/' . $path);
        }

        // 3️⃣ EXTRACT SIZES
        $sizes = $validated['sizes'] ?? [];
        unset($validated['sizes']);

        // 4️⃣ HITUNG TOTAL STOCK (dari size)
        $totalStock = array_sum(array_column($sizes, 'stock'));
        $validated['stock'] = $totalStock;

        // 5️⃣ AUTO GENERATE SKU (product_code)
        $prefix = strtoupper($validated['category_prefix']);

        $lastProduct = Product::where('product_code', 'like', $prefix . '%')
            ->orderBy('product_code', 'desc')
            ->first();

        if ($lastProduct) {
            $lastNumber = intval(substr($lastProduct->product_code, strlen($prefix)));
            $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '001';
        }

        $validated['product_code'] = $prefix . $newNumber;

        unset($validated['category_prefix']); // tidak disimpan di DB

        // 6️⃣ CREATE PRODUCT
        $product = Product::create($validated);

        // 7️⃣ SIMPAN SIZE-SIZE NYA
        if (!empty($sizes)) {
            foreach ($sizes as $sizeData) {
                $barcode = $validated['product_code'] . '-' . strtoupper($sizeData['size']);

                $product->sizes()->create([
                    'size' => $sizeData['size'],
                    'stock' => $sizeData['stock'],
                    'barcode' => $barcode
                ]);
            }

        }

        // 8️⃣ RETURN SUCCESS RESPONSE
        return ApiResponse::success(
            $product->load('sizes'),
            'Product created successfully',
            201
        );

    } catch (\Throwable $th) {
        return ApiResponse::error($th->getMessage(), 500);
    }
}


    /**
 * @OA\Get(
 *     path="/api/products/{product}",
 *     summary="Get product by ID",
 *     tags={"Products"},
 *     @OA\Parameter(
 *         name="product",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(response=200, description="OK"),
 *     @OA\Response(response=404, description="Not Found")
 * )
 */
    public function show($id)
    {
        try {
            $product = Product::with('category','sizes')->findOrFail($id);
            if (!$product) {
                return ApiResponse::error('Product not found', 404);
            }
            return ApiResponse::success($product, 'Product found');
        } catch (\Throwable $th) {
            return ApiResponse::error('Product not found', 404);
        }
    }

    /**
 * @OA\Put(
 *     path="/api/products/{product}",
 *     summary="Update product",
 *     tags={"Products"},
 *     @OA\Parameter(
 *         name="product",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             @OA\Property(property="name", type="string"),
 *             @OA\Property(property="price", type="number")
 *         )
 *     ),
 *     @OA\Response(response=200, description="Product updated")
 * )
 */
    public function update(Request $request, $id)
{
    $product = Product::findOrFail($id);

    $validated = $request->validate([
        'name' => 'string|max:255',
        'description' => 'nullable|string',
        'price' => 'numeric',
        'category_id' => 'exists:categories,id',
        'color' => 'string|max:50',
        'image' => 'nullable|image|max:2048'
    ]);

    if ($request->hasFile('image')) {
        $path = $request->file('image')->store('products', 'public');
        $validated['image_url'] = asset('storage/' . $path);
    }

    $product->update($validated);

    return ApiResponse::success($product->load('sizes'), 'Product updated');
}

public function addSizeStock(Request $request)
{
    $request->validate([
        'barcode' => 'required',
        'quantity' => 'required|integer|min:1'
    ]);

    // Cari size berdasarkan barcode
    $size = ProductSize::where('barcode', $request->barcode)->first();

    if (!$size) {
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
            'updated_size' => $size
        ],
        'Size stock updated successfully'
    );
}


    
/**
 * @OA\Delete(
 *     path="/api/products/{product}",
 *     summary="Delete product",
 *     tags={"Products"},
 *     @OA\Parameter(
 *         name="product",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
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
}
