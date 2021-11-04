<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'storename',
        'description',
        'location',
        'address',
        'coordinate',
    ];


    public function handlers(){
        return $this->belongsToMany(User::class, 'store_sellers');
    }
}
