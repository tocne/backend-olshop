<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Series extends Model
{
    protected $fillable = [
        'product_id',
        'name',
        'description',
        'price',
        'series_code',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function items() // Model A
    {
        return $this->hasMany(SeriesItem::class);
    }

    public function products() // Model B
    {
        return $this->belongsToMany(Product::class, 'series_products')
            ->withPivot('quantity')
            ->withTimestamps();
    }
}
