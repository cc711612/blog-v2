<?php

namespace App\Modules\Content\Application\Services;

use App\Modules\Content\Domain\Entities\CommentEntity;
use App\Modules\Content\Domain\Repositories\CommentRepositoryInterface;

/**
 * Use case service for adding article comments.
 */
class AddCommentService
{
    public function __construct(private readonly CommentRepositoryInterface $comments)
    {
    }

    /**
     * Add a new published comment.
     *
     * @param int $articleId
     * @param string $authorName
     * @param string $content
     * @param int|null $userId
     * @return CommentEntity
     */
    public function handle(int $articleId, string $authorName, string $content, ?int $userId = null): CommentEntity
    {
        $plainContent = trim(strip_tags($content));

        return $this->comments->add(
            $articleId,
            trim($authorName),
            $plainContent,
            $userId,
        );
    }
}
