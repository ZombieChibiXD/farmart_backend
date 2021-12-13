<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;

class Chatroom extends Model
{
    use HasFactory;

    protected $attributes = [
        'is_admin' => false,
        'store_id' => 0,
        'user_id' => 0,
    ];


    public function getDetailsAsMember()
    {
        $name = 'Lost Chat';
        $image = 'https://via.placeholder.com/150';

        if ($this->is_admin) {
            $name = 'Administrator';
            $image = URL::to('/') . '/app_icon.png';
        } else if ($this->store) {
            $name = $this->store->name;
            $image = $this->store->profile_image;
        }
        // Get the last message
        $last_message = $this->messages()->orderBy('created_at', 'desc')->first();

        return [
            'id' => $this->id,
            'last_message' => $last_message,

            'name' => $name,
            'profile_image' => $image,
        ];
    }
    public function getDetailsAsStore()
    {
        $name = 'Lost Chat';
        $image = 'https://via.placeholder.com/150';

        if ($this->is_admin) {
            $name = 'Administrator';
            $image = URL::to('/') . '/app_icon.png';
        } else if ($this->user) {
            $name = $this->user->firstname . ' ' . $this->user->lastname;
            $image = $this->user->profile_image;
        }
        // Get the last message
        $last_message = $this->messages()->orderBy('created_at', 'desc')->first();

        return [
            'id' => $this->id,
            'last_message' => $last_message,

            'name' => $name,
            'profile_image' => $image,
        ];
    }

    // Get details of the chatroom as admin
    public function getDetailsAsAdmin()
    {
        $name = 'Lost Chat';
        $image = 'https://via.placeholder.com/150';

        if ($this->user) {
            $name = $this->user->firstname . ' ' . $this->user->lastname;
            $image = $this->user->profile_image;
        } else if ($this->store) {
            $name = $this->store->name;
            $image = $this->store->profile_image;
        }

        // Get the last message
        $last_message = $this->messages()->orderBy('created_at', 'desc')->first();

        return [
            'id' => $this->id,
            'last_message' => $last_message,

            'name' => $name,
            'profile_image' => $image,
        ];
    }

    public function addMemberParticipant(User $member)
    {
        return $this->participants()->create([
            'user_id' => $member->id,
            'role_id' => Role::MEMBER,
        ]);
    }

    public function addSellerParticipants(Store $store)
    {
        return $this->participants()->createMany(
            $store->handlers->map(function ($seller) use ($store) {
                return [
                    'user_id' => $seller->id,
                    'role_flag' => Role::SELLER,
                    'store_id' => $store->id,
                ];
            })->toArray()
        );
    }

    /**
     * Add admin participant to chatroom
     */
    public function addAdminParticipants()
    {
        $admins = User::whereRaw('role & ? == ?', [Role::ADMINISTRATOR, Role::ADMINISTRATOR])->get();

        return $this->participants()->createMany(
            $admins->map(function ($admin) {
                return [
                    'user_id' => $admin->id,
                    'role_flag' => Role::ADMINISTRATOR,
                    'is_admin' => true,
                ];
            })->toArray()
        );
    }

    // Relationships
    // ChatMessage many through ChatParticipant
    public function messages()
    {
        return $this->hasManyThrough(ChatMessage::class, ChatParticipant::class, 'chatroom_id', 'chat_participant_id', 'id', 'id');
    }
    //ChatParticipant
    public function participants()
    {
        return $this->hasMany(ChatParticipant::class);
    }
    public function store()
    {
        return $this->belongsTo(Store::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
