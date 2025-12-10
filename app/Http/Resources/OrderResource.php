<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'order_code' => $this->order_code,
            'order_type' => $this->order_type,
            'status' => $this->status,

            'customer_name' => $this->customer_name,
            'customer_phone' => $this->customer_phone,
            'address' => $this->address,
            'notes' => $this->notes,

            'subtotal' => $this->subtotal,
            'shipping_cost' => $this->shipping_cost,
            'total' => $this->total,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'items' => OrderItemResource::collection($this->items),
        ];
    }
}
