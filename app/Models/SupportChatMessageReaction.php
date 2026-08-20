<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportChatMessageReaction extends Model
{
    protected $fillable = [
        'support_chat_message_id',
        'user_id',
        'reaction',
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(SupportChatMessage::class, 'support_chat_message_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
