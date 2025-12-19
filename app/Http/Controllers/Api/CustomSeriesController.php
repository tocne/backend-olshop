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

    // ==========================================================
    // CREATE CUSTOM SERIES
    // ==========================================================
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'price' => 'required|numeric|min:0',

                'thumbnail' => 'required',
                'images' => 'nullable|array',
                'images.*' => 'nullable',

                'active' => 'nullable|boolean',
            ]);

            $series = DB::transaction(function () use ($request) {

                // =========================
                // UPLOAD THUMBNAIL (SAMA DENGAN PRODUCT)
                // =========================
                $thumbnail = $request->file('thumbnail');
                $thumbnailUrl = null;

                if ($thumbnail instanceof UploadedFile) {
                    $thumbnailUrl = SupabaseUploader::upload(
                        $thumbnail,
                        'series/thumbnails'
                    );
                } elseif (is_string($thumbnail)) {
                    $thumbnailUrl = $thumbnail;
                }

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
                // UPLOAD GALLERY (SAMA DENGAN PRODUCT)
                // =========================
                if ($request->has('images')) {
                    foreach ($request->images as $index => $image) {

                        $imageUrl = null;

                        if ($image instanceof UploadedFile) {
                            $imageUrl = SupabaseUploader::upload(
                                $image,
                                'series/gallery'
                            );
                        } elseif (is_string($image)) {
                            $imageUrl = $image;
                        }

                        if ($imageUrl) {
                            SeriesImage::create([
                                'series_id' => $series->id,
                                'image_url' => $imageUrl, // URL FULL
                                'order' => $index,
                            ]);
                        }
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
