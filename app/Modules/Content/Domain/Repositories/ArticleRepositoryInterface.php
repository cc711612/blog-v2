<?php

namespace App\Modules\Content\Domain\Repositories;

use App\Modules\Content\Domain\Entities\ArticleEntity;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Contract for article persistence operations.
 */
interface ArticleRepositoryInterface
{
    /**
     * Paginate published articles for a specific user.
     *
     * @param int $userId
     * @param int $page
     * @param int $perPage
     * @param string $search
     * @return LengthAwarePaginator
     */
    public function paginatePublishedByUser(int $userId, int $page, int $perPage, string $search = ''): LengthAwarePaginator;

    /**
     * Find one published article by id.
     *
     * @param int $id
     * @return ArticleEntity|null
     */
    public function findPublishedById(int $id): ?ArticleEntity;

    /**
     * Return all published articles for a specific user.
     *
     * @param int $userId
     * @return Collection<int, ArticleEntity>
     */
    public function allPublishedByUser(int $userId): Collection;
}
