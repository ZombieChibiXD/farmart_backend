<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class Image extends Model
{
    use HasFactory;

    protected $fillable = [
        'location',
        'local'
    ];

    protected $appends  = ['url'];


    public function getUrlAttribute()
    {
        // if($this->local){
        // }
        return URL::to(Storage::url($this->location));
        return $this->location;
    }

}
