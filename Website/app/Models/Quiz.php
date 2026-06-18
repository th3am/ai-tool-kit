<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Quiz extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'title', 'source_type', 'source_text',
        'status', 'error_message', 'is_public', 'share_uuid', 'max_questions',
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function ($quiz) {
            $quiz->share_uuid = (string) Str::uuid();
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function questions()
    {
        return $this->hasMany(QuizQuestion::class)->orderBy('order');
    }

    public function attempts()
    {
        return $this->hasMany(QuizAttempt::class)->latest();
    }

    public function getShareUrlAttribute(): string
    {
        return url('/quiz/public/' . $this->share_uuid);
    }
}
