<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class BackupUpload extends Model
{
    use HasUuids;

    public const STATUS_WAITING = 'waiting';

    public const STATUS_UPLOADING = 'uploading';

    public const STATUS_QUEUED = 'queued';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'upload_key', 'site', 'customer_id', 'user_id', 'category', 'original_name',
        'extension', 'mime_type', 'size', 'chunk_size', 'total_chunks',
        'uploaded_chunks', 'received_bytes', 'status', 'storage_path', 'manifest', 'checksum',
        'error_message', 'last_activity_at', 'queued_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'uploaded_chunks' => 'array',
            'manifest' => 'array',
            'size' => 'integer',
            'chunk_size' => 'integer',
            'total_chunks' => 'integer',
            'received_bytes' => 'integer',
            'last_activity_at' => 'datetime',
            'queued_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isFinished(): bool
    {
        return in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_FAILED], true);
    }
}
