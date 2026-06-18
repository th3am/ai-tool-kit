<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ToolJob extends Model
{
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'user_id',
        'chat_session_id',
        'tool_type',
        'status',
        'params',
        'results',
        'error_message',
    ];

    protected $casts = [
        'params' => 'array',
        'results' => 'array',
    ];

    public function chatSession()
    {
        return $this->belongsTo(ChatSession::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }
}
