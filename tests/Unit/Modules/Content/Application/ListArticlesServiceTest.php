<?php

namespace Tests\Unit\Modules\Content\Application;

use App\Modules\Content\Application\Services\ListArticlesService;
use App\Modules\Content\Domain\Entities\ArticleEntity;
use App\Modules\Content\Domain\Repositories\ArticleRepositoryInterface;
use App\Modules\Content\Domain\Repositories\CommentRepositoryInterface;
use Carbon\CarbonImmutable;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class ListArticlesServiceTest extends TestCase
{
    public function test_it_returns_paginated_articles_with_comment_count(): void
    {
        $service = new ListArticlesService(new FakeArticleRepository(), new FakeCommentRepository());
        $result = $service->handle(32, 1, 10, 'laravel');

        $this->assertSame(1, $result->total());
        $this->assertSame(1, $result->items()[0]['comments_count']);
        $this->assertSame('Laravel Test Article', $result->items()[0]['title']);
    }
}

class FakeArticleRepository implements ArticleRepositoryInterface
{
    public function paginatePublishedByUser(int $userId, int $page, int $perPage, string $search = ''): LengthAwarePaginator
    {
        $items = collect([
            new ArticleEntity(1, 32, 'Laravel Test Article', '<p>demo</p>', 1, CarbonImmutable::now()),
        ])->filter(fn (ArticleEntity $article) => str_contains(strtolower($article->title), strtolower($search)))->values();

        return new LengthAwarePaginator($items->all(), $items->count(), $perPage, $page);
    }

    public function findPublishedById(int $id): ?ArticleEntity
    {
        return null;
    }

    public function allPublishedByUser(int $userId): Collection
    {
        return collect();
    }
}

class FakeCommentRepository implements CommentRepositoryInterface
{
    public function listByArticle(int $articleId, string $search = '', string $sort = 'latest'): Collection
    {
        return collect();
    }

    public function add(int $articleId, string $authorName, string $content, ?int $userId = null): \App\Modules\Content\Domain\Entities\CommentEntity
    {
        throw new \RuntimeException('Not used in this test');
    }

    public function countPublishedByArticle(int $articleId): int
    {
        return $articleId === 1 ? 1 : 0;
    }
}
