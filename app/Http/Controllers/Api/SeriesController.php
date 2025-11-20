<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Series;
use Illuminate\Http\Request;
use App\Helpers\ApiResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="All series retrieved"),
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(
 *                     @OA\Property(property="id", type="integer", example=1),
 *                     @OA\Property(property="name", type="string", example="Bundle Hemat"),
 *                     @OA\Property(property="description", type="string", example="Paket hemat hoodie anak"),
 *                     @OA\Property(property="price", type="number", example=120000),
 *                     @OA\Property(
 *                         property="products",
 *                         type="array",
 *                         @OA\Items(
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
            $series = Series::with('products')->get();
            return ApiResponse::success($series, 'All series retrieved');
        } catch (\Throwable $th) {
            Log::error('Series index error: ' . $th->getMessage());
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
 *         @OA\JsonContent(
 *             required={"name","price","product_ids"},
 *             @OA\Property(property="name", type="string", example="Paket Hoodie Anak"),
 *             @OA\Property(property="description", type="string", example="Paket bundling hoodie 2 pcs"),
 *             @OA\Property(property="price", type="number", example=120000),
 *             @OA\Property(
 *                 property="product_ids",
 *                 type="array",
 *                 @OA\Items(type="integer", example=1)
 *             )
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Series created successfully",
 *         @OA\JsonContent(
 *             type="object",
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
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required',
                'description' => 'nullable|string',
                'price' => 'required|numeric',
                'product_ids' => 'required|array',
                'product_ids.*' => 'required|exists:products,id',
            ]);

            $series = Series::create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'price' => $validated['price'],
            ]);
            
            $series->series_code = 'SER' . strtoupper(Str::random(6));
            $series->save();

            $series->products()->sync($validated['product_ids']);

            return ApiResponse::success($series->load('products'), 'Series created successfully');

        } catch (\Throwable $th) {
            Log::error('Series store error: ' . $th->getMessage());
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
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Series retrieved",
 *         @OA\JsonContent(
 *             type="object",
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
            $series = Series::with('products')->find($id);

            if (!$series) {
                return ApiResponse::error('Series not found', 404);
            }

            return ApiResponse::success($series, 'Series retrieved');

        } catch (\Throwable $th) {
            Log::error('Series show error: ' . $th->getMessage());
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
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"name","price","product_ids"},
 *             @OA\Property(property="name", type="string", example="Paket Hoodie Anak Diskon"),
 *             @OA\Property(property="description", type="string", example="Bundle hoodie updated"),
 *             @OA\Property(property="price", type="number", example=100000),
 *             @OA\Property(
 *                 property="product_ids",
 *                 type="array",
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
            $series = Series::find($id);

            if (!$series) {
                return ApiResponse::error('Series not found', 404);
            }

            $validated = $request->validate([
                'name' => 'required',
                'description' => 'nullable|string',
                'price' => 'required|numeric',
                'product_ids' => 'required|array',
                'product_ids.*' => 'required|exists:products,id',
            ]);

            $series->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'price' => $validated['price'],
            ]);

            $series->products()->sync($validated['product_ids']);

            return ApiResponse::success($series->load('products'), 'Series updated successfully');

        } catch (\Throwable $th) {
            Log::error('Series update error: ' . $th->getMessage());
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
            $series = Series::find($id);

            if (!$series) {
                return ApiResponse::error('Series not found', 404);
            }

            $series->products()->detach();
            $series->delete();

            return ApiResponse::success(null, 'Series deleted successfully');

        } catch (\Throwable $th) {
            Log::error('Series destroy error: ' . $th->getMessage());
            return ApiResponse::error($th->getMessage(), 500);
        }
    }
}
