<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Series extends Model
{
    protected $fillable = [
        'name',
        'description',
        'price',
        'series_code',
        'thumbnail',
        'active'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function items() // Model A
    {
        return $this->hasMany(SeriesItem::class);
    }
        public function images()
    {
        return $this->hasMany(SeriesImage::class)->orderBy('order');
    }

    public function products() // Model B
    {
        return $this->belongsToMany(Product::class, 'series_products')
            ->withPivot('quantity')
            ->withTimestamps();
    }
}
