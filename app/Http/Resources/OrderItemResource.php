<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_name' => $this->product_name,
            'product_code' => $this->product_code,
            'size' => $this->size,
            'color' => $this->color,

            'quantity' => $this->quantity,
            'price' => $this->price,
            'total_price' => $this->total_price,

            'series_id' => $this->series_id,
            'created_at' => $this->created_at,
        ];
    }
}
