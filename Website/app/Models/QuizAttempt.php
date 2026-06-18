<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuizAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'quiz_id', 'user_id', 'participant_name',
        'score', 'total', 'answers', 'completed_at',
    ];

    protected $casts = [
        'answers'      => 'array',
        'completed_at' => 'datetime',
    ];

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getPercentageAttribute(): int
    {
        if ($this->total === 0) return 0;
        return (int) round(($this->score / $this->total) * 100);
    }
}
