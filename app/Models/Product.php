<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'category_id',
        'stock',
        'stock_type',
        'po_estimate_days',
        'po_notes',
        'product_code',
        'image_url',
        'images',
    ];

    protected $casts = [
        'price' => 'integer',
    ];

    public function series()
    {
        return $this->hasOne(Series::class);
    }

    public function scopePo($query)
    {
        return $query->where('stock_type', 'po');
    }

    public function scopeReady($query)
    {
        return $query->where('stock_type', 'ready');
    }

    public function sizes()
    {
        return $this->hasMany(ProductSize::class, 'product_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function colors()
    {
        return $this->hasMany(ProductColor::class, 'product_id');
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class)->orderBy('order', 'asc');
    }
}
