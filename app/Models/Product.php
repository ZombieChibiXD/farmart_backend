<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;

class Product extends Model
{
    use HasFactory;
    const FIELDS = [
        'fullname' => 'required|string',
        'shortname' => 'required|string',
        'unit' => 'required|string',
        'slug' => 'required|string|unique:products,slug',
        'price' => 'required|numeric',
        'price_discounted' => 'numeric|nullable',
        'labeled' => 'string|nullable',
        'description' => 'required|string',
        'stock' => 'required|numeric',
        'sold' => 'numeric',
        'likes' => 'numeric',
    ];

    protected $fillable = [
        'store_id',
        'fullname',
        'shortname',
        'unit',
        'slug',
        'price',
        'labeled',
        'description',
        'stock',
    ];

    /**
     * Appended attributes.
     *
     * @var array
     */
    protected $appends  = ['product_images', 'location','type'];

    public function getLocationAttribute()
    {
        return $this->store->location;
    }
    public function getTypeAttribute()
    {
        return $this->types->pluck('name')->toArray();
    }
    public function getProductImagesAttribute()
    {
        if (count($this->images) > 0) {
            $image_urls = [];
            foreach ($this->images as $image) {
                $image_urls[] = $image->url;
            }
            return $image_urls;
            // return array_column($this->images, 'url');
        }
        $placeholder = URL::to('/') . '/no_image_general.png';
        return [$placeholder, $placeholder, $placeholder];
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
    public function types()
    {
        // Make many to many relationship with ProductType through pivot product_products_type table
        return $this->belongsToMany(ProductsType::class, 'product_products_types', 'product_id', 'products_type_id');
    }
    public function orders()
    {
        return $this->belongsToMany(Order::class, 'order_details')->withTimestamps();
    }
    public function images()
    {
        return $this->belongsToMany(Image::class, 'product_images');
    }
}
