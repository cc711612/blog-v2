<?php

namespace App\Support;

use Illuminate\Support\Str;

class ArticleSlug
{
    /**
     * Build canonical article slug.
     *
     * @param string|null $storedSlug
     * @param string $title
     * @param int $id
     * @return string
     */
    public static function from(?string $storedSlug, string $title, int $id): string
    {
        $candidate = trim((string) $storedSlug);

        if ($candidate !== '') {
            $slug = Str::slug($candidate);
            if ($slug !== '') {
                return $slug;
            }
        }

        $slug = Str::slug($title);

        return $slug !== '' ? $slug : 'article-'.$id;
    }
}
