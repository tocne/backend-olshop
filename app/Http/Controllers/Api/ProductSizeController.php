<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductSize;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Helpers\ApiResponse;

class ProductSizeController extends Controller
{
    /**
     * Add new size for a product
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'size' => 'required|string|max:50',
            'stock' => 'required|integer|min:0'
        ]);

        // Create size
        $size = ProductSize::create($validated);

        // Update total stock on product
        $this->updateProductTotalStock($validated['product_id']);

        return ApiResponse::success($size, "Size added successfully");
    }

    /**
     * Update a size
     */
    public function update(Request $request, $id)
    {
        $size = ProductSize::find($id);

        if (!$size) {
            return ApiResponse::error("Size not found", 404);
        }

        $validated = $request->validate([
            'size' => 'required|string|max:50',
            'stock' => 'required|integer|min:0'
        ]);

        // Update size
        $size->update($validated);

        // Update total stock on product
        $this->updateProductTotalStock($size->product_id);

        return ApiResponse::success($size, "Size updated successfully");
    }

    /**
     * Delete a size
     */
    public function destroy($id)
    {
        $size = ProductSize::find($id);

        if (!$size) {
            return ApiResponse::error("Size not found", 404);
        }

        $productId = $size->product_id;

        $size->delete();

        // Update total stock on product
        $this->updateProductTotalStock($productId);

        return ApiResponse::success(null, "Size deleted successfully");
    }

    /**
     * Update total stock on product table
     */
    private function updateProductTotalStock($productId)
    {
        $product = Product::find($productId);

        if ($product) {
            $newTotalStock = $product->sizes()->sum('stock');
            $product->update(['stock' => $newTotalStock]);
        }
    }
}
