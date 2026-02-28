<?php

namespace App\Modules\Content\Infrastructure\Persistence\Eloquent;

use App\Models\Comment;
use App\Modules\Content\Domain\Entities\CommentEntity;
use App\Modules\Content\Domain\Repositories\CommentRepositoryInterface;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

/**
 * Eloquent-based comment repository.
 */
class EloquentCommentRepository implements CommentRepositoryInterface
{
    private static ?bool $hasGuestName = null;

    /**
     * @param int $articleId
     * @param string $search
     * @param string $sort
     * @return Collection<int, CommentEntity>
     */
    public function listByArticle(int $articleId, string $search = '', string $sort = 'latest'): Collection
    {
        $query = Comment::query()
            ->with('user')
            ->where('article_id', $articleId)
            ->where('status', 1);

        if ($search !== '') {
            $query->where('content', 'like', "%{$search}%");
        }

        $query->orderBy('created_at', $sort === 'oldest' ? 'asc' : 'desc');

        return $query->get()->map(fn (Comment $comment) => $this->toEntity($comment));
    }

    /**
     * @param int $articleId
     * @param string $authorName
     * @param string $content
     * @param int|null $userId
     * @return CommentEntity
     */
    public function add(int $articleId, string $authorName, string $content, ?int $userId = null): CommentEntity
    {
        $payload = [
            'article_id' => $articleId,
            'user_id' => $userId,
            'content' => $content,
            'status' => 1,
        ];

        if ($this->hasGuestName()) {
            $payload['guest_name'] = $authorName !== '' ? $authorName : ($userId === null ? 'Guest' : null);
        }

        $comment = Comment::query()->create($payload);

        $comment->load('user');

        $entity = $this->toEntity($comment);

        if ($authorName !== '') {
            return new CommentEntity(
                id: $entity->id,
                articleId: $entity->articleId,
                authorName: $authorName,
                content: $entity->content,
                status: $entity->status,
                createdAt: $entity->createdAt,
                userId: $entity->userId,
            );
        }

        return $entity;
    }

    /**
     * @param int $articleId
     * @return int
     */
    public function countPublishedByArticle(int $articleId): int
    {
        return Comment::query()
            ->where('article_id', $articleId)
            ->where('status', 1)
            ->count();
    }

    /**
     * Map Eloquent model into domain entity.
     *
     * @param Comment $comment
     * @return CommentEntity
     */
    private function toEntity(Comment $comment): CommentEntity
    {
        $guestName = $this->hasGuestName() ? $comment->getAttribute('guest_name') : null;

        return new CommentEntity(
            id: (int) $comment->id,
            articleId: (int) $comment->article_id,
            authorName: (string) ($guestName ?? $comment->user?->name ?? 'Guest'),
            content: (string) $comment->content,
            status: (int) $comment->status,
            createdAt: CarbonImmutable::instance($comment->created_at),
            userId: $comment->user_id === null ? null : (int) $comment->user_id,
        );
    }

    /**
     * Check schema compatibility for legacy guest_name column.
     *
     * @return bool
     */
    private function hasGuestName(): bool
    {
        if (self::$hasGuestName === null) {
            self::$hasGuestName = Schema::hasColumn('comments', 'guest_name');
        }

        return self::$hasGuestName;
    }
}
