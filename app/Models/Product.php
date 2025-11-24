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
        'product_code',
        'size',
        'parent_id',
        'image_url'
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
