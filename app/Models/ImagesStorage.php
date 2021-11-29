<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;

class ImagesStorage extends Model
{
    use HasFactory;

    protected $fillable = [
        'uri',
        'location',
        'active'
    ];

    protected $appends  = ['image_url'];


    public function getImageUrlAttribute()
    {
        if($this->local){
            return URL::to('/storage') . '/' . $this->uri;
        }
        return $this->uri;
    }

}
