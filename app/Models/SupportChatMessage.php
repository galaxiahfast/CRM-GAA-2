<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class SupportChatMessage extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'message',
        'image_path',
        'image_original_name',
        'attachment_path',
        'attachment_original_name',
        'attachment_mime',
        'attachment_size',
        'sticker_key',
        'automatic_key',
    ];

    protected static function booted(): void
    {
        static::forceDeleted(function (SupportChatMessage $message): void {
            if ($message->image_path) {
                Storage::disk('public')->delete($message->image_path);
            }
            if ($message->attachment_path) {
                Storage::disk('public')->delete($message->attachment_path);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
