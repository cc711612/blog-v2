<?php

namespace App\Modules\Content\Application\Services;

use App\Modules\Content\Domain\Repositories\ArticleRepositoryInterface;
use App\Modules\Content\Domain\Repositories\CommentRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Use case service for article listing.
 */
class ListArticlesService
{
    public function __construct(
        private readonly ArticleRepositoryInterface $articles,
        private readonly CommentRepositoryInterface $comments,
    ) {
    }

    /**
     * Build paginated article list view data.
     *
     * @param int $userId
     * @param int $page
     * @param int $perPage
     * @param string $search
     * @return LengthAwarePaginator
     */
    public function handle(int $userId, int $page, int $perPage, string $search = ''): LengthAwarePaginator
    {
        $paginator = $this->articles->paginatePublishedByUser($userId, $page, $perPage, $search);
        $items = collect($paginator->items())->map(function ($article) {
            return [
                'id' => $article->id,
                'title' => $article->title,
                'content_html' => $article->contentHtml,
                'content_preview' => \Illuminate\Support\Str::limit(strip_tags($article->contentHtml), 120),
                'author_name' => $article->authorName,
                'created_at' => $article->createdAt ?? $article->updatedAt,
                'seo' => $article->seo,
                'updated_at' => $article->updatedAt,
                'comments_count' => $this->comments->countPublishedByArticle($article->id),
            ];
        })->all();

        return new LengthAwarePaginator(
            $items,
            $paginator->total(),
            $paginator->perPage(),
            $paginator->currentPage(),
            [
                'path' => $paginator->path(),
                'query' => app()->bound('request') ? request()->query() : [],
            ]
        );
    }
}
