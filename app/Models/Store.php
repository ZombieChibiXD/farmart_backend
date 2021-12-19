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

    protected $hidden = [
        'user_id',
        'slug',
        'created_at',
        'updated_at',
        'image',
    ];


    /**
     * Appended attributes.
     *
     * @var array
     */
    protected $appends  = ['profile_image', 'total_products', 'total_orders', 'total_sales', 'total_reviews', 'stars'];

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
    public function orders(){
        return $this->hasMany(Order::class);
    }
    public function orderItems(){
        return $this->hasManyThrough(OrderDetail::class, Product::class);
    }
    public function reviews(){
        return $this->hasManyThrough(ProductReviews::class, Product::class);
    }

    public function getTotalProductsAttribute()
    {
        return $this->products()->count();
    }

    public function getTotalOrdersAttribute()
    {
        return $this->orderItems()->count();
    }

    public function getTotalSalesAttribute()
    {
        return $this->orderItems()->sum('subtotal');
    }

    public function getTotalReviewsAttribute()
    {
        return $this->reviews()->count();
    }

    public function getStarsAttribute()
    {
        $reviews = $this->reviews()->get();
        $total = $reviews->count();
        $stars = 0;
        if ($total > 0) {
            foreach ($reviews as $review) {
                $stars += $review->stars;
            }
            return $stars / $total;
        }
        return 0;
    }


}
