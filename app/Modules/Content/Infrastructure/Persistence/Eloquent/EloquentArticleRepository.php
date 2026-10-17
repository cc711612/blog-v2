<?php

namespace App\Modules\Content\Infrastructure\Persistence\Eloquent;

use App\Models\Article;
use App\Modules\Content\Domain\Entities\ArticleEntity;
use App\Modules\Content\Domain\Repositories\ArticleRepositoryInterface;
use Carbon\CarbonImmutable;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Eloquent-based article repository.
 */
class EloquentArticleRepository implements ArticleRepositoryInterface
{
    public function paginatePublishedByUser(int $userId, int $page, int $perPage, string $search = ''): LengthAwarePaginator
    {
        $query = Article::query()
            ->with('author')
            ->where('status', 1)
            ->where('user_id', $userId)
            ->orderByDesc('id');

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('title', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            });
        }

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);
        $paginator->setCollection($paginator->getCollection()->map(
            fn (Article $article) => $this->toEntity($article)
        ));

        return $paginator;
    }

    public function findPublishedById(int $id): ?ArticleEntity
    {
        $article = Article::query()
            ->with('author')
            ->where('id', $id)
            ->where('status', 1)
            ->first();

        return $article ? $this->toEntity($article) : null;
    }

    /**
     * @return Collection<int, ArticleEntity>
     */
    public function allPublishedByUser(int $userId): Collection
    {
        return Article::query()
            ->with('author')
            ->where('status', 1)
            ->where('user_id', $userId)
            ->orderByDesc('id')
            ->get()
            ->map(fn (Article $article) => $this->toEntity($article));
    }

    /**
     * @return Collection<int, ArticleEntity>
     */
    public function latestPublishedByUser(int $userId, int $limit): Collection
    {
        return Article::query()
            ->with('author')
            ->where('status', 1)
            ->where('user_id', $userId)
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn (Article $article) => $this->toEntity($article));
    }

    /**
     * Map Eloquent model into domain entity.
     */
    private function toEntity(Article $article): ArticleEntity
    {
        return new ArticleEntity(
            id: (int) $article->id,
            userId: (int) $article->user_id,
            title: (string) $article->title,
            contentHtml: (string) $article->content,
            status: (int) $article->status,
            // 舊資料的 timestamp 欄位可為 NULL：updatedAt 依序退回 created_at、現在時間，不能因此讓頁面 500
            updatedAt: CarbonImmutable::instance($article->updated_at ?? $article->created_at ?? now()),
            createdAt: $article->created_at ? CarbonImmutable::instance($article->created_at) : null,
            authorName: (string) ($article->author?->name ?? 'Unknown'),
            seo: is_array($article->seo) ? $article->seo : [],
            slug: (string) ($article->getAttribute('slug') ?? ''),
        );
    }
}
