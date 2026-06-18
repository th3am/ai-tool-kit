<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Presentation extends Model
{
    protected $fillable = ['user_id', 'topic', 'content', 'pdf_path', 'ppt_path'];

    protected $casts = [
        'content' => 'array',
    ];
}
