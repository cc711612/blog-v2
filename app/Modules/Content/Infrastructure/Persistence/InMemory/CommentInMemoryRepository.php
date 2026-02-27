<?php

namespace App\Modules\Content\Infrastructure\Persistence\InMemory;

use App\Modules\Content\Domain\Entities\CommentEntity;
use App\Modules\Content\Domain\Repositories\CommentRepositoryInterface;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * In-memory fallback repository for comments.
 */
class CommentInMemoryRepository implements CommentRepositoryInterface
{
    private const CACHE_KEY = 'blog-v2:comments';

    /**
     * @param int $articleId
     * @param string $search
     * @param string $sort
     * @return Collection<int, CommentEntity>
     */
    public function listByArticle(int $articleId, string $search = '', string $sort = 'latest'): Collection
    {
        $comments = $this->all()
            ->filter(fn (CommentEntity $comment) => $comment->articleId === $articleId && $comment->status === 1)
            ->when($search !== '', fn (Collection $items) => $items->filter(
                fn (CommentEntity $comment) => str_contains(mb_strtolower($comment->content), mb_strtolower($search))
            ));

        return ($sort === 'oldest'
            ? $comments->sortBy(fn (CommentEntity $comment) => $comment->createdAt->timestamp)
            : $comments->sortByDesc(fn (CommentEntity $comment) => $comment->createdAt->timestamp)
        )->values();
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
        $all = $this->all();
        $nextId = (int) ($all->max(fn (CommentEntity $comment) => $comment->id) ?? 0) + 1;

        $entity = new CommentEntity(
            id: $nextId,
            articleId: $articleId,
            authorName: $authorName === '' ? 'Guest' : $authorName,
            content: $content,
            status: 1,
            createdAt: CarbonImmutable::now(),
            userId: $userId,
        );

        $all->push($entity);
        $this->persist($all);

        return $entity;
    }

    /**
     * @param int $articleId
     * @return int
     */
    public function countPublishedByArticle(int $articleId): int
    {
        return $this->listByArticle($articleId)->count();
    }

    /**
     * @return Collection<int, CommentEntity>
     */
    private function all(): Collection
    {
        $seed = collect([
            new CommentEntity(1, 44, 'Roy', 'Great deployment checklist, thanks!', 1, CarbonImmutable::parse('2026-02-01 10:00:00')),
            new CommentEntity(2, 62, 'Alice', 'Disk cleanup commands saved my VM.', 1, CarbonImmutable::parse('2026-02-05 09:30:00')),
            new CommentEntity(3, 44, 'Bob', 'Can you share nginx conf example?', 1, CarbonImmutable::parse('2026-02-07 08:10:00')),
        ]);

        $cached = Cache::get(self::CACHE_KEY);
        if (!is_array($cached)) {
            return $seed;
        }

        return collect($cached)->map(function (array $row) {
            return new CommentEntity(
                id: (int) $row['id'],
                articleId: (int) $row['article_id'],
                authorName: (string) $row['author_name'],
                content: (string) $row['content'],
                status: (int) $row['status'],
                createdAt: CarbonImmutable::parse($row['created_at'])
            );
        });
    }

    /**
     * @param Collection<int, CommentEntity> $all
     * @return void
     */
    private function persist(Collection $all): void
    {
        $payload = $all->map(fn (CommentEntity $comment) => [
            'id' => $comment->id,
            'article_id' => $comment->articleId,
            'author_name' => $comment->authorName,
            'content' => $comment->content,
            'status' => $comment->status,
            'created_at' => $comment->createdAt->toDateTimeString(),
        ])->all();

        Cache::forever(self::CACHE_KEY, $payload);
    }
}
