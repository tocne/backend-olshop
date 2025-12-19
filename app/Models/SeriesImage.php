<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeriesImage extends Model
{
    protected $fillable = [
        'series_id',
        'image_url',
        'order'
    ];

    public function series()
    {
        return $this->belongsTo(Series::class);
    }
}
