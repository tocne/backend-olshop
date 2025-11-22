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
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric',
            'category_id' => 'required|exists:categories,id',
            'color' => 'nullable|string|max:50',
            'sizes' => 'nullable|array',
            'sizes.*.size' => 'required_with:sizes|string|max:50',
            'sizes.*.stock' => 'required_with:sizes|integer|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        // Upload gambar
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
            $validated['image_url'] = asset('storage/' . $path);
        }

        // Extract sizes
        $sizes = $validated['sizes'] ?? [];
        unset($validated['sizes']);

        // Hitung total stock
        $totalStock = array_sum(array_column($sizes, 'stock'));
        $validated['stock'] = $totalStock;
        // Create product
        $product = Product::create($validated);

        // Insert multiple sizes
        foreach ($sizes as $sizeData) {
            $product->sizes()->create([
                'size' => $sizeData['size'],
                'stock' => $sizeData['stock']
            ]);
        }

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
            $product = Product::with('category')->findOrFail($id);
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
        try {
            $product = Product::findOrFail($id);
            $validated = $request->validate([
            'name' => 'string|max:255',
            'description' => 'nullable|string',
            'price' => 'numeric',
            'category_id' => 'exists:categories,id',
            'color' => 'string|max:50',

            'sizes' => 'nullable|array',
            'sizes.*.size' => 'required_with:sizes|string|max:50',
            'sizes.*.stock' => 'required_with:sizes|integer|min:0',

            'image' => 'nullable|image|max:2048'
        ]);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
            $validated['image_url'] = asset('storage/' . $path);
        }

        $product->update($validated);

        if ($request->has('sizes')) {
            $product->sizes()->delete();
            foreach ($validated['sizes'] as $sizeData) {
                $product->sizes()->create([
                    'size' => $sizeData['size'],
                    'stock' => $sizeData['stock']
                ]);
            }
        }

        return ApiResponse::success(
            $product->load('sizes'),'Product updated successfully');

        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage(), 500);
        }
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
