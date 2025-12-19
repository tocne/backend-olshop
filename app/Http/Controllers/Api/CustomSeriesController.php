<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Series;
use App\Models\SeriesImage;
use App\Services\SupabaseUploader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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

    public function store(Request $request)
    {
        try {
            // =========================
            // VALIDATION (WAJIB IMAGE)
            // =========================
            $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'price' => 'required|numeric|min:0',

                'thumbnail' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
                'images' => 'nullable|array',
                'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:2048',

                'active' => 'nullable|boolean',
            ]);

            $series = DB::transaction(function () use ($request) {

                // =========================
                // UPLOAD THUMBNAIL (PASTI ADA)
                // =========================
                $thumbnailUrl = SupabaseUploader::upload(
                    $request->file('thumbnail'),
                    'series/thumbnails'
                );

                // =========================
                // CREATE SERIES
                // =========================
                $series = Series::create([
                    'name' => $request->name,
                    'description' => $request->description,
                    'price' => $request->price,
                    'thumbnail' => $thumbnailUrl, // URL FULL
                    'series_code' => 'SER-'.strtoupper(Str::random(6)),
                    'active' => $request->active ?? true,
                ]);

                // =========================
                // UPLOAD GALLERY (OPTIONAL)
                // =========================
                $galleryFiles = $request->file('images');

                if (is_array($galleryFiles)) {
                    foreach ($galleryFiles as $index => $image) {
                        $imageUrl = SupabaseUploader::upload(
                            $image,
                            'series/gallery'
                        );

                        SeriesImage::create([
                            'series_id' => $series->id,
                            'image_url' => $imageUrl,
                            'order' => $index,
                        ]);
                    }
                }

                return $series->load('images');
            });

            return ApiResponse::success(
                $series,
                'Custom series created successfully'
            );

        } catch (\Throwable $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }
}
