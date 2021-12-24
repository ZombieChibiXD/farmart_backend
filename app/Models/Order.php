<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    const STATUS_CANCELLED = 0;
    const STATUS_PENDING_PAYMENT = 1;
    const STATUS_PAID = 2;
    const STATUS_SHIPPED = 3;
    const STATUS_DELIVERED = 4;

    const NO_COURIER = 'N/A';


    const FIELDS = [
        'user_id' => 'required|exists:users,id',
        'store_id' => 'required|exists:stores,id',
        'total' => 'required|numeric',
        'status' => 'required|integer',
        'dropoff_location' => 'required|string',
        'transaction_code' => 'required|string',
        'courier_code' => 'required|string',

    ];

    protected $fillable = [
        'user_id',
        'store_id',
        'total',
        'status',
        'dropoff_location',
        'transaction_code',
        'courier_code',
        'promo_value',
    ];
    protected $appends = [
        'status_text',
    ];

    protected $attributes = [
        'status' => self::STATUS_PENDING_PAYMENT,
        'courier_code' => self::NO_COURIER,
    ];

    protected $with = [
        'orderDetails',
        'store',
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
            self::STATUS_CANCELLED => 'Cancelled',
        ];
        return $status[$this->status];
    }

    public static function statusText(){
        return [
            self::STATUS_PENDING_PAYMENT => 'Pending Payment',
            self::STATUS_PAID => 'Paid',
            self::STATUS_SHIPPED => 'Shipped',
            self::STATUS_DELIVERED => 'Delivered',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }


}
