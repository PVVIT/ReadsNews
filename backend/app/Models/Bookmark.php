<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bookmark extends Model
{
    use HasFactory;

    protected $table = 'bookmarks';

    public $incrementing = false;

    // Bảng không có cột updated_at
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'article_id',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'user_id' => 'integer',
        'article_id' => 'integer',
    ];

    /**
     * Người dùng sở hữu bookmark
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Bài viết được bookmark
     */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class, 'article_id');
    }
}
