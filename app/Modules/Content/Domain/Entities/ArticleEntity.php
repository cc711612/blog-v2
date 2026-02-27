<?php

namespace App\Modules\Content\Domain\Entities;

use Carbon\CarbonImmutable;

class ArticleEntity
{
    public function __construct(
        public readonly int $id,
        public readonly int $userId,
        public readonly string $title,
        public readonly string $contentHtml,
        public readonly int $status,
        public readonly CarbonImmutable $updatedAt,
        public readonly ?CarbonImmutable $createdAt = null,
        public readonly string $authorName = 'Unknown',
        public readonly array $seo = [],
    ) {
    }
}
