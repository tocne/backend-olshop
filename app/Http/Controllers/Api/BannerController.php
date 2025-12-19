<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Banner;

class BannerController extends Controller
{
    public function index()
    {
        return response()->json(
            Banner::where('is_active', true)
                ->orderBy('order')
                ->get(['id', 'image_url', 'title', 'subtitle'])
        );
    }
}
