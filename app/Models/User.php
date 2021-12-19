<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'firstname',
        'lastname',
        'email',
        'username',
        'role',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Appended attributes.
     *
     * @var array
     */
    protected $appends  = ['profile_image','roles'];


    /**
     * Check if user manages a store by ID.
     *
     * @param int $store_id
     * @return bool
     */
    public function managesStore(int $store_id)
    {
        return $this->stores->contains($store_id);
    }

    /**
     * Check if user has a role.
     *
     * @param int $role
     * @return bool
     */
    public function hasRole(int $role)
    {
        return $this->role & $role;
    }

    public function getProfileImageAttribute()
    {
        if ($this->image) return $this->image->url;
        return URL::to('/') . '/no_image_user.png';
    }

    public function getRolesAttribute()
    {
        if ($this->role == 0)
            return ['RESTRICTED'];
        $user_roles = [];
        $roles = Role::where('flag', '<>', 0)->get();
        foreach ($roles as $key => $value) {
            if  (($this->role & $value->flag) == $value->flag)
                $user_roles[] = [$value->flag =>$value->name];
        }
        return $user_roles;
    }


    public function stores()
    {
        return $this->belongsToMany(Store::class, 'store_sellers');
    }

    public function owned_store()
    {
        return $this->hasMany(Store::class);
    }
    public function image()
    {
        return $this->belongsTo(Image::class);
    }

    // Relationship have many chatroom through chat participant
    public function chatrooms_all()
    {
        return $this->hasManyThrough(Chatroom::class, ChatParticipant::class, 'user_id', 'id', 'id', 'chatroom_id');
    }

    public function chatrooms()
    {
        return $this->hasMany(Chatroom::class);
    }

    public function likes_products()
    {
        return $this->belongsToMany(Product::class, 'likes_products');
    }

    /**
     * Makes product reviews
     *
     */
    public function reviews()
    {
        return $this->hasMany(ProductReviews::class);
    }

    public function cart()
    {
        return $this->hasMany(Cart::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

}
