<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'product_id',
        'amount',
        'price_discounted',
        'is_checked_out'
    ];
    protected $casts = [
        'is_checked_out' => 'boolean'
    ];

    protected $attributes = [
        'is_checked_out' => false
    ];
    protected $with = ['product'];
    public function product(){
        return $this->belongsTo(Product::class);
    }

    public function user(){
        return $this->belongsTo(User::class);
    }

}
