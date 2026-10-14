<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Article extends Model
{
    use HasFactory;

    protected $table = 'articles';

    protected $fillable = [
        'source_id',
        'category_id',
        'title',
        'slug',
        'summary',
        'ai_summary',
        'content',
        'content_tts',
        'thumbnail',
        'original_url',
        'url_hash',
        'author',
        'word_count',
        'view_count',
        'listen_count',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'word_count' => 'integer',
            'view_count' => 'integer',
            'listen_count' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    /**
     * Danh mục của bài viết
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /**
     * Nguồn tin tức của bài viết
     */
    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class, 'source_id');
    }
}

