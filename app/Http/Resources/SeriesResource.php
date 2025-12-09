<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SeriesResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'series_code' => $this->series_code,

            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,

            'product' => [
                'id' => $this->product->id,
                'name' => $this->product->name,
                'slug' => $this->product->slug,
                'product_code' => $this->product->product_code,
                'price' => $this->product->price,
                'image_url' => $this->product->image_url,
                'stock_type' => $this->product->stock_type,
            ],

            // Model A: ukuran & qty
            'sizes' => SeriesItemResource::collection($this->items),

            // Model B: produk bundling
            'bundle_products' => SeriesBundleProductResource::collection($this->products),

            'created_at' => $this->created_at,
        ];
    }
}
