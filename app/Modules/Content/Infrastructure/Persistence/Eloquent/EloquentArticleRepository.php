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
    /**
     * @param int $userId
     * @param int $page
     * @param int $perPage
     * @param string $search
     * @return LengthAwarePaginator
     */
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

    /**
     * @param int $id
     * @return ArticleEntity|null
     */
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
     * @param int $userId
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
     * Map Eloquent model into domain entity.
     *
     * @param Article $article
     * @return ArticleEntity
     */
    private function toEntity(Article $article): ArticleEntity
    {
        return new ArticleEntity(
            id: (int) $article->id,
            userId: (int) $article->user_id,
            title: (string) $article->title,
            contentHtml: (string) $article->content,
            status: (int) $article->status,
            updatedAt: CarbonImmutable::instance($article->updated_at),
            createdAt: CarbonImmutable::instance($article->created_at),
            authorName: (string) ($article->author?->name ?? 'Unknown'),
            seo: is_array($article->seo) ? $article->seo : [],
            slug: (string) ($article->getAttribute('slug') ?? ''),
        );
    }
}
