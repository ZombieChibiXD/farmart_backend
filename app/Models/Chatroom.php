<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;

class Chatroom extends Model
{
    use HasFactory;

    // Constant Default Lost Chat Name
    const DEFAULT_LOST_CHAT_NAME = 'Lost Chat';
    // Constant Default Lost Chat Image
    const DEFAULT_LOST_CHAT_IMAGE = 'https://via.placeholder.com/150';

    protected $attributes = [
        'is_admin' => false,
        'store_id' => null,
        'user_id' => null,
    ];


    public static function cast(self $obj) : self
    {
        return $obj;
    }

    public static function findOrCreateMemberStore($user_id, $store_id) : self
    {
        $chatroom = Chatroom::where('user_id', $user_id)->where('store_id', $store_id)->first();
        if ($chatroom) {
            return $chatroom;
        }

        $chatroom = new Chatroom();
        $chatroom->user_id = $user_id;
        $chatroom->store_id = $store_id;
        $chatroom->save();

        // Create user as participant
        $chatroom->participants()->create([
            'user_id' => $user_id,
        ]);

        return $chatroom;
    }

    public static function findOrCreateMemberAdmin($user_id) : self
    {
        $chatroom = Chatroom::where('user_id', $user_id)->where('is_admin', true)->first();
        if ($chatroom) {
            return $chatroom;
        }

        $chatroom = new Chatroom();
        $chatroom->user_id = $user_id;
        $chatroom->is_admin = true;
        $chatroom->save();

        // Create user as participant
        $chatroom->participants()->create([
            'user_id' => $user_id,
        ]);

        return $chatroom;
    }

    public static function findOrCreateStoreAdmin($store_id) : self
    {
        $chatroom = Chatroom::where('store_id', $store_id)->where('is_admin', true)->first();
        if ($chatroom) {
            return $chatroom;
        }

        $chatroom = new Chatroom();
        $chatroom->store_id = $store_id;
        $chatroom->is_admin = true;
        $chatroom->save();

        return $chatroom;
    }

    public function findOrCreateMemberParticipant($user_id) : ChatParticipant
    {
        $participant = $this->participants()->where('user_id', $user_id)->where('role_flag', Role::MEMBER)->first();
        if ($participant) {
            return $participant;
        }
        return $this->participants()->create([
            'user_id' => $user_id,
            'role_flag' => Role::MEMBER,
        ]);
    }

    public function findOrCreateSellerParticipant($user_id, $store_id) : ChatParticipant
    {
        $participant = $this->participants()->where('user_id', $user_id)->where('role_flag', Role::SELLER)->first();
        if ($participant) {
            return $participant;
        }
        return $this->participants()->create([
            'user_id' => $user_id,
            'role_flag' => Role::SELLER,
            'store_id' => $store_id,
        ]);
    }

    public function findOrCreateAdminParticipant($user_id) : ChatParticipant
    {
        $participant = $this->participants()->where('user_id', $user_id)->where('role_flag', Role::ADMINISTRATOR)->first();
        if ($participant) {
            return $participant;
        }
        return $this->participants()->create([
            'user_id' => $user_id,
            'role_flag' => Role::ADMINISTRATOR,
            'is_admin' => true,
        ]);
    }

    public function getDetailsAsMember()
    {
        $name = self::DEFAULT_LOST_CHAT_NAME;
        $image = self::DEFAULT_LOST_CHAT_IMAGE;

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
        $name = self::DEFAULT_LOST_CHAT_NAME;
        $image = self::DEFAULT_LOST_CHAT_IMAGE;

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
        $name = self::DEFAULT_LOST_CHAT_NAME;
        $image = self::DEFAULT_LOST_CHAT_IMAGE;

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

    // Relationships
    // ChatMessage many through ChatParticipant
    public function messages()
    {
        return $this->hasManyThrough(ChatMessage::class, ChatParticipant::class, 'chatroom_id', 'chat_participant_id', 'id', 'id')->orderBy('created_at', 'desc');
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
