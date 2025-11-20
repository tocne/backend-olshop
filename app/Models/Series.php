<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Series extends Model
{
    protected $fillable = ['name', 'description', 'price'];

    public function items()
    {
        return $this->hasMany(SeriesItem::class);
    }
}
