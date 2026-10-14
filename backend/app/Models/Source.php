<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Source extends Model
{
    use HasFactory;

    protected $table = 'sources';

    protected $fillable = [
        'name',
        'slug',
        'website_url',
        'rss_url',
        'category_id',
        'last_crawled_at',
        'is_active',
    ];

    protected $casts = [
        'is_active'       => 'boolean',
        'last_crawled_at' => 'datetime',
    ];

    // Relations
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function articles()
    {
        return $this->hasMany(Article::class);
    }
}

