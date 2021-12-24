<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Promo extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'type', 'start_date', 'end_date', 'seasons', 'value', 'visible', 'usable', 'code'
    ];
}
