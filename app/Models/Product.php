<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'store_id',
        'description',
        'price',
    ];

    /**
     * Appended attributes.
     *
     * @var array
     */
    protected $appends  = ['product_images'];

    public function getProductImagesAttribute()
    {
        if (count($this->images)>0) {
            $image_urls = [];
            foreach ($this->images as $image) {
                $image_urls [] = $image->url;
            }
            return $image_urls;
            // return array_column($this->images, 'url');
        }
        $placeholder = URL::to('/') . '/no_image_general.png';
        return [$placeholder,$placeholder,$placeholder];
    }

    public function store(){
        return $this->belongsTo(Store::class);
    }
    public function orders(){
        return $this->belongsToMany(Order::class,'order_details')->withTimestamps();
    }
    public function images()
    {
        return $this->belongsToMany(Image::class, 'product_images');
    }
}
