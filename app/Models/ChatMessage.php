<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'content',
        'chat_participant_id',
    ];
    protected $attributes = [
        'content' => '',
        'chat_participant_id' => null,
    ];


    // Chat messages belongs to owner
    public function owner()
    {
        return $this->hasOne(ChatParticipant::class);
    }
}
