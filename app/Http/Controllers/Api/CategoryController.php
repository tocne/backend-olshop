<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use App\Helpers\ApiResponse;

class CategoryController extends Controller
{
    public function index()
    {
        try {
            return ApiResponse::success(Category::all(), 'All categories retrieved');
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage(), 500);
        }
        
    }

    public function store(Request $request)
    {

        try {
            $validated = $request->validate([
            'name' => 'required|string|max:255',
            'prefix' => 'required|string|max:5'
            ]);

        $category = Category::create($validated);
        return ApiResponse::success($category, 'Category created successfully');
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage(), 500);
        }
        
    }

    public function show($id)
    {
        try {
            $category = Category::findOrFail($id);
            return ApiResponse::success($category, 'Category retrieved');
            
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage(), 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $category = Category::findOrFail($id);
            $category->update($request->all());

            return ApiResponse::success($category, 'Category updated successfully');
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage(), 500);
        }
    }

    public function destroy($id)
    {
        try {
            $category = Category::findOrFail($id);
            $category->delete();

            return ApiResponse::success(null, 'Category deleted successfully');
        } catch (\Throwable $th) {
            return ApiResponse::error($th->getMessage(), 500);
        }     
    }
}