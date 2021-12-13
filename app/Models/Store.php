<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;

class Store extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'user_id',
        'name',
        'storename',
        'description',
        'location',
        'address',
        'coordinate',
        'email',
        'url',
        'telephone',
    ];

    /**
     * Appended attributes.
     *
     * @var array
     */
    protected $appends  = ['profile_image'];

    public function getProfileImageAttribute()
    {
        if ($this->image) return $this->image->url;
        return URL::to('/') . '/no_image_shop.png';
    }

    public function handlers(){
        return $this->belongsToMany(User::class, 'store_sellers');
    }
    public function owner(){
        return $this->hasOne(User::class);
    }
    public function products(){
        return $this->hasMany(Product::class);
    }
    public function image()
    {
        return $this->belongsTo(Image::class);
    }
    public function chatrooms(){
        return $this->hasMany(Chatroom::class);
    }
}
