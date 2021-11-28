<?php

namespace App\Models;

use App\Http\Middleware\Roles;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    public const RESTRICTED    = 0;
    public const MEMBER        = 1;
    public const SELLER        = 2;
    public const SUPERVISOR    = 4;
    public const ADMINISTRATOR = 8;
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'flag',
        'name',
    ];
}
