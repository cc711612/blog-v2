<?php

namespace App\Modules\Content\Domain\Entities;

use Carbon\CarbonImmutable;

class CommentEntity
{
    public function __construct(
        public readonly int $id,
        public readonly int $articleId,
        public readonly string $authorName,
        public readonly string $content,
        public readonly int $status,
        public readonly CarbonImmutable $createdAt,
        public readonly ?int $userId = null,
    ) {
    }
}
