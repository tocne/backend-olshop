<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeriesItem extends Model
{
    protected $fillable = ['series_id', 'product_id', 'size', 'quantity'];

    public function series()
    {
        return $this->belongsTo(Series::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
