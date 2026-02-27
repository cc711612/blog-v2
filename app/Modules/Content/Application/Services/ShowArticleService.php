<?php

namespace App\Modules\Content\Application\Services;

use App\Modules\Content\Domain\Entities\ArticleEntity;
use App\Modules\Content\Domain\Repositories\ArticleRepositoryInterface;

/**
 * Use case service for article detail retrieval.
 */
class ShowArticleService
{
    public function __construct(private readonly ArticleRepositoryInterface $articles)
    {
    }

    /**
     * Get a published article by id.
     *
     * @param int $id
     * @return ArticleEntity|null
     */
    public function handle(int $id): ?ArticleEntity
    {
        return $this->articles->findPublishedById($id);
    }
}
