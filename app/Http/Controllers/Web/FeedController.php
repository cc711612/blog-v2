<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Modules\Content\Domain\Repositories\ArticleRepositoryInterface;
use App\Support\ArticleSlug;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class FeedController extends Controller
{
    public function __construct(private readonly ArticleRepositoryInterface $articles)
    {
    }

    public function index(): Response
    {
        $base = rtrim(config('app.url', 'http://localhost'), '/');
        $items = $this->articles->allPublishedByUser(32)->take((int) config('seo.feed.limit', 20));

        return response($this->buildFeedXml($base, $items), 200)
            ->header('Content-Type', 'application/rss+xml; charset=UTF-8');
    }

    /**
     * Build RSS 2.0 XML without Blade template parsing.
     *
     * @param string $base
     * @param Collection<int, mixed> $items
     * @return string
     */
    private function buildFeedXml(string $base, Collection $items): string
    {
        $lastBuild = optional($items->first())->updatedAt ?? now();

        $lines = [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">',
            '  <channel>',
            '    <title>'.$this->xmlEscape((string) config('seo.site_name')).'</title>',
            '    <link>'.$this->xmlEscape($base.'/').'</link>',
            '    <description>'.$this->xmlEscape((string) config('seo.home_description')).'</description>',
            '    <language>'.$this->xmlEscape((string) config('seo.feed.language', 'zh-TW')).'</language>',
            '    <lastBuildDate>'.$lastBuild->toRfc2822String().'</lastBuildDate>',
            '    <atom:link href="'.$this->xmlEscape($base.'/feed.xml').'" rel="self" type="application/rss+xml"/>',
        ];

        foreach ($items as $article) {
            $slug = ArticleSlug::from($article->slug, $article->title, $article->id);
            $link = $base.'/article/'.$article->id.'/'.$slug;
            $description = (string) (data_get($article->seo, 'description')
                ?: Str::limit(trim(html_entity_decode(strip_tags((string) $article->contentHtml), ENT_QUOTES | ENT_HTML5, 'UTF-8')), 150));
            $published = $article->createdAt ?? $article->updatedAt;

            $lines[] = '    <item>';
            $lines[] = '      <title>'.$this->xmlEscape($article->title).'</title>';
            $lines[] = '      <link>'.$this->xmlEscape($link).'</link>';
            $lines[] = '      <guid isPermaLink="true">'.$this->xmlEscape($link).'</guid>';
            $lines[] = '      <pubDate>'.$published->toRfc2822String().'</pubDate>';
            $lines[] = '      <description>'.$this->xmlEscape($description).'</description>';
            $lines[] = '    </item>';
        }

        $lines[] = '  </channel>';
        $lines[] = '</rss>';

        return implode("\n", $lines);
    }

    private function xmlEscape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
