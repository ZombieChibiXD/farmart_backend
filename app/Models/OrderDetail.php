<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderDetail extends Model
{
    use HasFactory;
    // 'product_id'
    // 'order_id'
    // 'amount'
    // 'subtotal'
    // Fillable
    protected $fillable = [
        'product_id',
        'order_id',
        'amount',
        'subtotal',
    ];

    protected $with = [
        'product',
    ];


    function order()
    {
        return $this->belongsTo(Order::class);
    }
    function product(){
        return $this->belongsTo(Product::class);
    }
}
