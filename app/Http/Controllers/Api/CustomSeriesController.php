<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Series;
use App\Models\SeriesImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Helpers\ApiResponse;

class CustomSeriesController extends Controller
{
    // ==========================================================
    // GET ALL CUSTOM SERIES
    // ==========================================================
    public function index()
    {
        try {
            $series = Series::with('images')
                ->where('active', true)
                ->latest()
                ->get();

            return ApiResponse::success($series, 'Custom series list retrieved');
        } catch (\Throwable $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    // ==========================================================
    // GET ONE CUSTOM SERIES
    // ==========================================================
    public function show($id)
    {
        try {
            $series = Series::with('images')
                ->where('active', true)
                ->find($id);

            if (! $series) {
                return ApiResponse::error('Custom series not found', 404);
            }

            return ApiResponse::success($series, 'Custom series retrieved');
        } catch (\Throwable $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    // ==========================================================
    // CREATE CUSTOM SERIES
    // ==========================================================
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name'        => 'required|string|max:255',
                'description' => 'nullable|string',
                'price'       => 'required|numeric|min:0',

                'thumbnail'   => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
                'images'      => 'nullable|array',
                'images.*'    => 'image|mimes:jpg,jpeg,png,webp|max:2048',

                'active'      => 'nullable|boolean',
            ]);

            $series = DB::transaction(function () use ($validated, $request) {

                // =========================
                // 1. Upload thumbnail
                // =========================
                $thumbnailPath = $request->file('thumbnail')
                    ->store('series/thumbnails', 'public');

                // =========================
                // 2. Create series
                // =========================
                $series = Series::create([
                    'name'        => $validated['name'],
                    'slug'        => Str::slug($validated['name']),
                    'description' => $validated['description'] ?? null,
                    'price'       => $validated['price'],
                    'thumbnail'   => $thumbnailPath,
                    'series_code' => 'SER-' . strtoupper(Str::random(6)),
                    'active'      => $validated['active'] ?? true,
                ]);

                // =========================
                // 3. Upload gallery images
                // =========================
                if ($request->hasFile('images')) {
                    foreach ($request->file('images') as $index => $image) {
                        $path = $image->store('series/images', 'public');

                        SeriesImage::create([
                            'series_id' => $series->id,
                            'image_url' => $path,
                            'order'     => $index,
                        ]);
                    }
                }

                return $series->load('images');
            });

            return ApiResponse::success($series, 'Custom series created successfully');

        } catch (\Throwable $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }
}
