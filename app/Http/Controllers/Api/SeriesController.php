<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Series;
use App\Models\SeriesItem;
use Illuminate\Http\Request;
use App\Helpers\ApiResponse;

class SeriesController extends Controller
{
    // GET /api/series

    /**
 * @OA\Get(
 *     path="/api/series",
 *     summary="Get list of all series",
 *     tags={"Series"},
 *     @OA\Response(
 *         response=200,
 *         description="List of series",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="All series retrieved"),
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(
 *                     @OA\Property(property="id", type="integer", example=1),
 *                     @OA\Property(property="name", type="string", example="Bundle Hoodie Anak"),
 *                     @OA\Property(property="price", type="integer", example=120000),
 *                     @OA\Property(
 *                         property="items",
 *                         type="array",
 *                         @OA\Items(
 *                             @OA\Property(property="product_id", type="integer", example=12),
 *                             @OA\Property(property="quantity", type="integer", example=1)
 *                         )
 *                     )
 *                 )
 *             )
 *         )
 *     )
 * )
 */

    public function index()
    {
        try {
            $series = Series::with('items.product')->get();
            return ApiResponse::success($series, 'All series retrieved');
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage(), 500);
        }
    }

    // GET /api/series/{id}

    /**
 * @OA\Get(
 *     path="/api/series/{id}",
 *     summary="Get detail of a series",
 *     tags={"Series"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Series details",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Series detail retrieved"),
 *             @OA\Property(
 *                 property="data",
 *                 type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="name", type="string", example="Paket Dress Anak Hemat"),
 *                 @OA\Property(property="price", type="integer", example=150000),
 *                 @OA\Property(
 *                     property="items",
 *                     type="array",
 *                     @OA\Items(
 *                         @OA\Property(property="product_id", type="integer", example=12),
 *                         @OA\Property(property="quantity", type="integer", example=1)
 *                     )
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(response=404, description="Series not found")
 * )
 */

    public function show($id)
    {
        try {
            $series = Series::with('items.product')->find($id);

            if (!$series) {
                return ApiResponse::error("Series not found", 404);
            }

            return ApiResponse::success(
                $series,
                "Series detail retrieved",
                200
            );

        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage(), 500);
        }
    }

    // POST /api/series
    /**
 * @OA\Post(
 *     path="/api/series",
 *     summary="Create new series bundle",
 *     tags={"Series"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             example={
 *                 "name": "Paket Dress Anak Hemat",
 *                 "description": "Bundle dress hemat",
 *                 "price": 150000,
 *                 "items": {
 *                     {"product_id": 12, "quantity": 1},
 *                     {"product_id": 15, "quantity": 1}
 *                 }
 *             },
 *             @OA\Property(property="name", type="string"),
 *             @OA\Property(property="description", type="string"),
 *             @OA\Property(property="price", type="number"),
 *             @OA\Property(
 *                 property="items",
 *                 type="array",
 *                 @OA\Items(
 *                     @OA\Property(property="product_id", type="integer"),
 *                     @OA\Property(property="quantity", type="integer")
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Series created successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Series created successfully")
 *         )
 *     )
 * )
 */

public function store(Request $request)
{
    try {

        $validated = $request->validate([
            'name' => 'required',
            'price' => 'required|numeric',
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $series = Series::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'price' => $validated['price']
        ]);

        foreach ($validated['items'] as $item) {
            SeriesItem::create([
                'series_id' => $series->id,
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity']
            ]);
        }

        return ApiResponse::success(
            $series->load('items.product'),
            'Series created successfully',
            201
        );

    } catch (\Throwable $th) {
        return ApiResponse::error($th->getMessage(), 500);
    }
}


    // PUT /api/series/{id}
    /**
 * @OA\Put(
 *     path="/api/series/{id}",
 *     summary="Update a series bundle",
 *     tags={"Series"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\RequestBody(
 *         required=false,
 *         @OA\JsonContent(
 *             example={
 *                 "name": "Bundle Sweater Anak Baru",
 *                 "price": 99000,
 *                 "items": {
 *                     {"product_id": 12, "quantity": 2}
 *                 }
 *             }
 *         )
 *     ),
 *     @OA\Response(response=200, description="Series updated successfully"),
 *     @OA\Response(response=404, description="Series not found")
 * )
 */

 public function update(Request $request, $id)
{
    try {

        // VALIDATION (fleksibel untuk partial update)
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|numeric',

            'items' => 'nullable|array',
            'items.*.product_id' => 'required_with:items|exists:products,id',
            'items.*.quantity' => 'required_with:items|integer|min:1',
        ]);

        // FIND SERIES
        $series = Series::find($id);
        if (!$series) {
            return ApiResponse::error("Series not found", 404);
        }

        // UPDATE SERIES MAIN DATA
        $series->update($validated);

        // UPDATE ITEMS (jika dikirim)
        if (isset($validated['items'])) {

            // Hapus item lama
            SeriesItem::where('series_id', $id)->delete();

            // Masukkan item baru
            foreach ($validated['items'] as $item) {
                SeriesItem::create([
                    'series_id' => $id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity']
                ]);
            }
        }

        // RETURN
        return ApiResponse::success(
            $series->load('items.product'),
            "Series updated successfully"
        );

    } catch (\Throwable $th) {
        return ApiResponse::error($th->getMessage(), 500);
    }
}

    // DELETE /api/series/{id}

    /**
 * @OA\Delete(
 *     path="/api/series/{id}",
 *     summary="Delete a series",
 *     tags={"Series"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(response=200, description="Series deleted successfully"),
 *     @OA\Response(response=404, description="Series not found")
 * )
 */

    public function destroy($id)
    {
        try {

            $series = Series::find($id);

            if (!$series) {
                return ApiResponse::error("Series not found", 404);
            }

            $series->delete();

            return ApiResponse::success(
                null,
                "Series deleted successfully",
                200
            );

        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage(), 500);
        }
    }
}
