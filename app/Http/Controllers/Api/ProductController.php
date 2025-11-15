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
            $products = Product::with('category')->get();
            return ApiResponse::success($products, 'All products retrieved');
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage(), 500);
        }
    }

    /**
 * @OA\Post(
 *     path="/api/products",
 *     summary="Create new product",
 *     tags={"Products"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"name","price"},
 *             @OA\Property(property="name", type="string", example="Produk A"),
 *             @OA\Property(property="price", type="number", example=15000)
 *         )
 *     ),
 *     @OA\Response(response=201, description="Product created")
 * )
 */

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'price' => 'required|numeric',
                'stock' => 'required|integer',
                'category_id' => 'required|exists:categories,id',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
            ]);

            if ($request->hasFile('image')) {
                $path = $request->file('image')->store('products', 'public');
                $validated['image_url'] = asset('storage/' . $path);
            }

            $product = Product::create($validated);
            return ApiResponse::success($product, 'Product created successfully', 201);
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
            $product->update($request->all());
            return ApiResponse::success($product, 'Product updated successfully');
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
