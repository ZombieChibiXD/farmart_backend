<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductsType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description'
    ];

    public function products(){
        // Make Belongs to Many relationship to Product model through product_products_types table
        return $this->belongsToMany(Product::class, 'product_products_types', 'products_type_id', 'product_id');

    }
}
