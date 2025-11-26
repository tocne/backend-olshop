<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
   protected $fillable = [
    'name',
    'description',
    'price',
    'category_id',
    'color',
    'stock',
    'stock_type',        
    'po_estimate_days',
    'po_notes', 
    'product_code',
    'image_url'
];

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
}
