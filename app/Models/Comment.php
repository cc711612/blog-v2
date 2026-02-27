<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Comment Eloquent model.
 */
class Comment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'article_id',
        'user_id',
        'guest_name',
        'content',
        'logs',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
        'deleted_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => 'int',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Article, Comment>
     */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    /**
     * @return BelongsTo<User, Comment>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Legacy alias for user relation.
     *
     * @return BelongsTo<User, Comment>
     */
    public function users(): BelongsTo
    {
        return $this->user();
    }

    /**
     * Legacy alias for article relation.
     *
     * @return BelongsTo<Article, Comment>
     */
    public function articles(): BelongsTo
    {
        return $this->article();
    }

    /**
     * @param mixed $value
     * @return array<int|string, mixed>
     */
    public function getLogsAttribute(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (is_array($value)) {
            return $value;
        }

        if (!is_string($value)) {
            return [];
        }

        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        $unserialized = @unserialize($value);

        return is_array($unserialized) ? $unserialized : [];
    }

    /**
     * @param array<int|string, mixed> $value
     * @return void
     */
    public function setLogsAttribute(array $value): void
    {
        $this->attributes['logs'] = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
