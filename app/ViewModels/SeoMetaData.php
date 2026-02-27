<?php

namespace App\ViewModels;

/**
 * Data carrier for page-level SEO metadata.
 */
class SeoMetaData
{
    /**
     * @param string $title
     * @param string $description
     * @param string $keywords
     * @param string $canonical
     * @param string $siteName
     * @param string $ogImage
     * @param string|null $robots
     * @param string|null $articleAuthor
     * @param string|null $articleModifiedTime
     */
    public function __construct(
        public readonly string $title,
        public readonly string $description,
        public readonly string $keywords,
        public readonly string $canonical,
        public readonly string $siteName,
        public readonly string $ogImage,
        public readonly ?string $robots = null,
        public readonly ?string $articleAuthor = null,
        public readonly ?string $articleModifiedTime = null,
    ) {
    }
}
