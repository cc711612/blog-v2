<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Modules\Content\Domain\Repositories\ArticleRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __construct(private readonly ArticleRepositoryInterface $articles)
    {
    }

    public function index(): Response
    {
        $base = rtrim(config('app.url', 'http://localhost'), '/');
        $items = $this->articles->allPublishedByUser(32);

        $xml = $this->buildSitemapXml($base, $items);

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }

    /**
     * Build sitemap XML without Blade template parsing.
     *
     * @param string $base
     * @param Collection<int, mixed> $items
     * @return string
     */
    private function buildSitemapXml(string $base, Collection $items): string
    {
        $lastmod = optional($items->first())->updatedAt?->toIso8601String() ?? now()->toIso8601String();

        $lines = [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
            '  <url>',
            '    <loc>'.$this->xmlEscape($base.'/').'</loc>',
            '    <lastmod>'.$this->xmlEscape($lastmod).'</lastmod>',
            '    <priority>1.00</priority>',
            '  </url>',
            '  <url>',
            '    <loc>'.$this->xmlEscape($base.'/article').'</loc>',
            '    <lastmod>'.$this->xmlEscape($lastmod).'</lastmod>',
            '    <priority>0.64</priority>',
            '  </url>',
        ];

        foreach ($items as $article) {
            $lines[] = '  <url>';
            $lines[] = '    <loc>'.$this->xmlEscape($base.'/article/'.$article->id).'</loc>';
            $lines[] = '    <lastmod>'.$this->xmlEscape($article->updatedAt->toIso8601String()).'</lastmod>';
            $lines[] = '    <priority>0.80</priority>';
            $lines[] = '  </url>';
        }

        $lines[] = '</urlset>';

        return implode("\n", $lines);
    }

    /**
     * Escape XML special characters.
     *
     * @param string $value
     * @return string
     */
    private function xmlEscape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
