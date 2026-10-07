<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    public const ROLE_USER = 'user';
    public const ROLE_EDITOR = 'editor';
    public const ROLE_ADMIN = 'admin';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'role',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Kiểm tra user có quyền admin hay không.
     */
    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /**
     * Kiểm tra user có quyền editor hay không.
     */
    public function isEditor(): bool
    {
        return $this->role === self::ROLE_EDITOR;
    }

    /**
     * Kiểm tra user là user thông thường hay không.
     */
    public function isUser(): bool
    {
        return $this->role === self::ROLE_USER;
    }

    /**
     * Kiểm tra role có thuộc danh sách chỉ định không.
     * Ví dụ: $user->hasRole('admin') hoặc $user->hasRole(['admin', 'editor'])
     */
    public function hasRole(string|array $roles): bool
    {
        if (is_string($roles)) {
            $roles = array_map('trim', explode(',', $roles));
        }

        return in_array($this->role, $roles, true);
    }

    /**
     * Cài đặt của người dùng.
     */
    public function settings(): HasOne
    {
        return $this->hasOne(UserSetting::class, 'user_id');
    }

    /**
     * Bài viết đã bookmark.
     */
    public function bookmarks(): HasMany
    {
        return $this->hasMany(Bookmark::class, 'user_id');
    }

    /**
     * Lịch sử nghe bài viết.
     */
    public function listeningHistories(): HasMany
    {
        return $this->hasMany(ListeningHistory::class, 'user_id');
    }
}
