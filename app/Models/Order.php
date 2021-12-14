<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    const STATUS_PENDING_PAYMENT = 1;
    const STATUS_PAID = 2;
    const STATUS_SHIPPED = 3;
    const STATUS_DELIVERED = 4;
    const STATUS_CANCELED = 5;

    const NO_COURIER = 'No courier';


    protected $fillable = [
        'user_id',
        'total',
        'status',
        'dropoff_location',
        'transaction_code',
        'courrier_code',
    ];
    protected $appends = [
        'status_text',
    ];

    protected $attributes = [
        'status' => self::STATUS_PENDING_PAYMENT,
        'courrier_code' => self::NO_COURIER,
    ];

    protected $with = [
        'orderDetails',
    ];


    public function orderDetails()
    {
        return $this->hasMany(OrderDetail::class);
    }


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function products(){
        return $this->hasMany(Product::class,'order_details')->withPivot('amount','subtotal')->withTimestamps();
    }

    public function getStatusTextAttribute()
    {
        $status = [
            self::STATUS_PENDING_PAYMENT => 'Pending Payment',
            self::STATUS_PAID => 'Paid',
            self::STATUS_SHIPPED => 'Shipped',
            self::STATUS_DELIVERED => 'Delivered',
            self::STATUS_CANCELED => 'Canceled',
        ];
        return $status[$this->status];
    }


}
