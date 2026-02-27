<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Article Eloquent model.
 */
class Article extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'id',
        'user_id',
        'title',
        'content',
        'images',
        'seo',
        'status',
        'published_at',
        'created_by',
        'updated_by',
        'deleted_by',
        'deleted_at',
    ];

    public $incrementing = false;

    protected $keyType = 'int';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => 'int',
            'published_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, Article>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return HasMany<Comment, Article>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * Legacy alias for author relation.
     *
     * @return BelongsTo<User, Article>
     */
    public function users(): BelongsTo
    {
        return $this->author();
    }

    /**
     * @param mixed $value
     * @return array<int, mixed>
     */
    public function getImagesAttribute(mixed $value): array
    {
        return $this->decodeArrayLikeValue($value);
    }

    /**
     * @param array<int, mixed> $value
     * @return void
     */
    public function setImagesAttribute(array $value): void
    {
        $this->attributes['images'] = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * @param mixed $value
     * @return array<string, mixed>
     */
    public function getSeoAttribute(mixed $value): array
    {
        return $this->decodeArrayLikeValue($value);
    }

    /**
     * @param array<string, mixed> $value
     * @return void
     */
    public function setSeoAttribute(array $value): void
    {
        $this->attributes['seo'] = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Scope to configured blog owner.
     *
     * @param Builder<Article> $query
     * @return Builder<Article>
     */
    public function scopeWebArticle(Builder $query): Builder
    {
        return $query->where('user_id', (int) config('app.user_id', 32));
    }

    /**
     * Decode JSON/serialized array-like fields from legacy storage.
     *
     * @param mixed $value
     * @return array<int|string, mixed>
     */
    private function decodeArrayLikeValue(mixed $value): array
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
}
