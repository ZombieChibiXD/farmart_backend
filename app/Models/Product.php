<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'store_id',
        'price'
    ];
    public function store(){
        return $this->belongsTo(Store::class);
    }
    public function orders(){
        return $this->belongsToMany(Order::class,'order_details')->withTimestamps();
    }
}
