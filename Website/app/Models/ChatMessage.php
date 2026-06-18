<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    protected $fillable = [
        'session_id',
        'role',
        'content',
        'meta_data',
        'tool_job_id',
    ];

    protected $casts = [
        'meta_data' => 'array',
    ];

    public function session()
    {
        return $this->belongsTo(ChatSession::class, 'session_id');
    }

    public function toolJob()
    {
        return $this->belongsTo(ToolJob::class);
    }
}
