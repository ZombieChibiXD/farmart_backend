<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'total',
        'status',
        'dropoff_location',
        'transaction_code',
        'courrier_code',
    ];
    public function products(){
        return $this->hasMany(Product::class,'order_details')->withPivot('amount','subtotal')->withTimestamps();
    }
}
