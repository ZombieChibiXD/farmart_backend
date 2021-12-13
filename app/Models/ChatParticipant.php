<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatParticipant extends Model
{
    use HasFactory;

    protected $attributes = [
        'user_id' => null,
        'chatroom_id' => null,
        'role_flag' => Role::MEMBER,
        'store_id' => null,
        'is_admin' => false,
    ];

    protected $fillable = [
        'user_id',
        'chatroom_id',
        'role_flag',
        'store_id',
        'is_admin',
    ];

    // Relationships
    // ------------
    // Chatroom
    public function chatroom()
    {
        return $this->belongsTo(Chatroom::class);
    }
    // User
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    // ChatMessage
    public function chatmessages()
    {
        return $this->hasMany(ChatMessage::class);
    }
    // Store (if applicable)
    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
