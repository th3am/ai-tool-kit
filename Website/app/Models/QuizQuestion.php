<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuizQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'quiz_id', 'question_text', 'correct_answer', 'options', 'order',
    ];

    protected $casts = [
        'options' => 'array',
    ];

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    /**
     * Returns all options (correct + distractors) shuffled for display.
     */
    public function getAllOptionsShuffled(): array
    {
        $all = array_merge($this->options ?? [], [$this->correct_answer]);
        shuffle($all);
        return $all;
    }
}
