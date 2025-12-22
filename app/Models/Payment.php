<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'amount',
        'method',
        'status',
        'proof_image',
        'uploaded_at',
        'reference',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
        'uploaded_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function isPaid()
    {
        return $this->status === 'paid';
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
