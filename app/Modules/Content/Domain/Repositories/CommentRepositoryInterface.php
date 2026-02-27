<?php

namespace App\Modules\Content\Domain\Repositories;

use App\Modules\Content\Domain\Entities\CommentEntity;
use Illuminate\Support\Collection;

/**
 * Contract for comment persistence operations.
 */
interface CommentRepositoryInterface
{
    /**
     * List published comments by article.
     *
     * @param int $articleId
     * @param string $search
     * @param string $sort
     * @return Collection<int, CommentEntity>
     */
    public function listByArticle(int $articleId, string $search = '', string $sort = 'latest'): Collection;

    /**
     * Create and return a published comment.
     *
     * @param int $articleId
     * @param string $authorName
     * @param string $content
     * @param int|null $userId
     * @return CommentEntity
     */
    public function add(int $articleId, string $authorName, string $content, ?int $userId = null): CommentEntity;

    /**
     * Count published comments for one article.
     *
     * @param int $articleId
     * @return int
     */
    public function countPublishedByArticle(int $articleId): int;
}
