<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SeriesItemResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'size' => $this->size,
            'quantity' => $this->quantity,
        ];
    }
}
