<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Series;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\SeriesService;

class SeriesController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/series",
     *     tags={"Series"},
     *     summary="Get all series",
     *     description="Retrieve all series along with their products",
     *
     *     @OA\Response(
     *         response=200,
     *         description="All series retrieved",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="All series retrieved"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *
     *                 @OA\Items(
     *
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Bundle Hemat"),
     *                     @OA\Property(property="description", type="string", example="Paket hemat hoodie anak"),
     *                     @OA\Property(property="price", type="number", example=120000),
     *                     @OA\Property(
     *                         property="products",
     *                         type="array",
     *
     *                         @OA\Items(
     *
     *                             @OA\Property(property="id", type="integer"),
     *                             @OA\Property(property="name", type="string"),
     *                             @OA\Property(property="price", type="number")
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error"
     *     )
     * )
     */
    public function index()
    {
        try {
    $series = Series::with(['product', 'items', 'products'])->latest()->get();

    return ApiResponse::success($series, 'Series list retrieved');
        } catch (\Throwable $th) {
            Log::error('Series index error: '.$th->getMessage());

            return ApiResponse::error($th->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/series",
     *     tags={"Series"},
     *     summary="Create a new series",
     *     description="Create a new product bundle (series) with selected product IDs",
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"name","price","product_ids"},
     *
     *             @OA\Property(property="name", type="string", example="Paket Hoodie Anak"),
     *             @OA\Property(property="description", type="string", example="Paket bundling hoodie 2 pcs"),
     *             @OA\Property(property="price", type="number", example=120000),
     *             @OA\Property(
     *                 property="product_ids",
     *                 type="array",
     *
     *                 @OA\Items(type="integer", example=1)
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Series created successfully",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Series created successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=500, description="Internal Server Error")
     * )
     */
    protected $service;

    public function __construct(SeriesService $service)
    {
        $this->service = $service;
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string',
                'description' => 'nullable|string',
                'price' => 'required|integer|min:1',

                // optional
                'category_id' => 'nullable|exists:categories,id',
                'stock_type' => 'nullable|in:ready,po',

                // Model A (size-based items)
                'items' => 'nullable|array',
                'items.*.size' => 'required|string',
                'items.*.quantity' => 'required|integer|min:1',

                // Model B (multi-product bundle)
                'bundle_products' => 'nullable|array',
                'bundle_products.*.product_id' => 'required|exists:products,id',
                'bundle_products.*.quantity' => 'required|integer|min:1',
            ]);

            $series = $this->service->createSeries($validated);

            return ApiResponse::success(
                $series,
                'Series created successfully'
            );

        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/series/{id}",
     *     tags={"Series"},
     *     summary="Get series detail",
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Series ID",
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Series retrieved",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Series retrieved"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *
     *     @OA\Response(response=404, description="Series not found"),
     *     @OA\Response(response=500, description="Internal Server Error")
     * )
     */
    public function show($id)
    {
        try {
            $series = Series::with('products', 'items', 'products')->find($id);

            if (! $series) {
                return ApiResponse::error('Series not found', 404);
            }

            return ApiResponse::success($series, 'Series retrieved');

        } catch (\Throwable $th) {
            Log::error('Series show error: '.$th->getMessage());

            return ApiResponse::error($th->getMessage(), 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/series/{id}",
     *     tags={"Series"},
     *     summary="Update an existing series",
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Series ID",
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"name","price","product_ids"},
     *
     *             @OA\Property(property="name", type="string", example="Paket Hoodie Anak Diskon"),
     *             @OA\Property(property="description", type="string", example="Bundle hoodie updated"),
     *             @OA\Property(property="price", type="number", example=100000),
     *             @OA\Property(
     *                 property="product_ids",
     *                 type="array",
     *
     *                 @OA\Items(type="integer", example=1)
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="Series updated successfully"),
     *     @OA\Response(response=404, description="Series not found"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=500, description="Internal Server Error")
     * )
     */
    public function update(Request $request, $id)
{
    try {
        $validated = $request->validate([
            'name' => 'nullable|string',
            'description' => 'nullable|string',
            'price' => 'nullable|integer|min:1',

            // Model A
            'items' => 'nullable|array',
            'items.*.size' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',

            // Model B
            'bundle_products' => 'nullable|array',
            'bundle_products.*.product_id' => 'required|exists:products,id',
            'bundle_products.*.quantity' => 'required|integer|min:1',
        ]);

        $series = Series::with(['product'])->findOrFail($id);

        DB::transaction(function () use ($validated, $series) {

            // Update product seri
            $series->product->update([
                'name' => $validated['name'] ?? $series->name,
                'slug' => isset($validated['name'])
                    ? Str::slug($validated['name'])
                    : $series->product->slug,
                'price' => $validated['price'] ?? $series->price,
            ]);

            // Update main series table
            $series->update([
                'name' => $validated['name'] ?? $series->name,
                'description' => $validated['description'] ?? $series->description,
                'price' => $validated['price'] ?? $series->price,
            ]);

            // Reset Model A (size-based)
            if (isset($validated['items'])) {
                $series->items()->delete();
                foreach ($validated['items'] as $item) {
                    $series->items()->create($item);
                }
            }

            // Reset Model B (bundle products)
            if (isset($validated['bundle_products'])) {
                $series->products()->detach(); // remove old
                foreach ($validated['bundle_products'] as $bp) {
                    $series->products()->attach(
                        $bp['product_id'],
                        ['quantity' => $bp['quantity']]
                    );
                }
            }
        });

        return ApiResponse::success(
            $series->fresh()->load(['product', 'items', 'products']),
            'Series updated successfully'
        );

    } catch (\Throwable $th) {
        return ApiResponse::error($th->getMessage(), 500);
    }
}


    /**
     * @OA\Delete(
     *     path="/api/series/{id}",
     *     tags={"Series"},
     *     summary="Delete a series",
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Series ID",
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(response=200, description="Series deleted successfully"),
     *     @OA\Response(response=404, description="Series not found"),
     *     @OA\Response(response=500, description="Internal Server Error")
     * )
     */
    public function destroy($id)
{
    try {
        $series = Series::findOrFail($id);
        $product = $series->product;

        DB::transaction(function () use ($series, $product) {
            $series->items()->delete();
            $series->products()->detach();
            $series->delete();

            if ($product) {
                $product->delete();
            }
        });

        return ApiResponse::success(null, 'Series deleted successfully');

    } catch (\Throwable $th) {
        return ApiResponse::error($th->getMessage(), 500);
    }
}

}
