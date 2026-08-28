<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    protected $fillable = [
        'title',
        'url',
        'published_at',
        'author',
        'content',
        'category',
        'source',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];
}