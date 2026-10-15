<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Modules\Content\Application\Services\ListArticlesService;
use App\Modules\Content\Application\Services\ShowArticleService;
use App\Modules\Content\Domain\Entities\ArticleEntity;
use App\Support\ArticleSlug;
use App\ViewModels\SeoMetaData;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ArticleController extends Controller
{
    public function __construct(
        private readonly ListArticlesService $listArticles,
        private readonly ShowArticleService $showArticle,
    ) {
    }

    /**
     * Render article listing page.
     */
    public function index(Request $request)
    {
        $page = max(1, (int) $request->integer('page', 1));
        $search = Str::limit(trim((string) $request->query('search', '')), 100, '');
        $articles = $this->listArticles->handle(32, $page, 10, $search);
        $recentArticles = collect($this->listArticles->handle(32, 1, 5)->items())
            ->map(fn (array $article) => [
                'id' => (int) $article['id'],
                'slug' => $article['slug'],
                'title' => (string) $article['title'],
                'created_at' => $article['created_at'],
            ]);

        $popularKeywords = $this->buildPopularKeywords();
        $seoMeta = $this->buildIndexSeoMeta($search, $page);

        return view('articles.index', [
            'articles' => $articles,
            'search' => $search,
            'recentArticles' => $recentArticles,
            'popularKeywords' => $popularKeywords,
            'seoMeta' => $seoMeta,
        ]);
    }

    /**
     * Render article detail page.
     */
    public function show(int $id, ?string $slug = null)
    {
        $article = $this->showArticle->handle($id);
        abort_if($article === null, 404);

        $canonicalSlug = ArticleSlug::from($article->slug, $article->title, $article->id);
        if ($slug !== $canonicalSlug) {
            return redirect()->route('articles.show', ['id' => $article->id, 'slug' => $canonicalSlug], 301);
        }

        $seoMeta = $this->buildShowSeoMeta($article);

        return view('articles.show', [
            'article' => $article,
            'seoMeta' => $seoMeta,
        ]);
    }

    /**
     * Build SEO metadata for listing page.
     *
     * @return SeoMetaData
     */
    private function buildIndexSeoMeta(string $search, int $page): SeoMetaData
    {
        $siteName = (string) config('seo.site_name');
        $keywords = 'blog,部落格,個人文章,個人部落格,工程師,Laravel,laravel,php,後端工程師,backend';
        $isPaged = $page > 1;
        $isHome = $search === '' && ! $isPaged;
        $base = $this->siteBase();

        $title = (string) config('seo.home_title');
        $description = (string) config('seo.home_description');
        if ($search !== '') {
            $title = '搜尋：'.$search.'｜'.$siteName;
            $description = '搜尋結果: '.$search;
        } elseif ($isPaged) {
            $title = '文章列表 第 '.$page.' 頁｜'.$siteName;
            $description = '文章列表 第 '.$page.' 頁';
        }

        $jsonLd = [];
        if ($isHome) {
            $jsonLd[] = [
                '@context' => 'https://schema.org',
                '@type' => 'WebSite',
                'name' => $siteName,
                'url' => $base.'/',
                'inLanguage' => (string) config('seo.locale'),
                'publisher' => $this->authorPerson($base),
            ];
        }

        return new SeoMetaData(
            title: $title,
            description: $description,
            keywords: $keywords,
            // 首頁與 /article 第 1 頁內容相同，canonical 統一指向首頁 /
            canonical: $isHome ? rtrim(route('home'), '/').'/' : route('articles.index', array_filter([
                'page' => $isPaged ? $page : null,
                'search' => $search !== '' ? $search : null,
            ])),
            siteName: $siteName,
            ogImage: $this->defaultOgImage($base),
            robots: $search !== '' ? 'noindex,follow' : null,
            ogImageAlt: $title,
            ogImageWidth: (int) config('seo.og_image.width'),
            ogImageHeight: (int) config('seo.og_image.height'),
            jsonLd: $jsonLd,
        );
    }

    /**
     * Build SEO metadata for article detail page.
     *
     * @return SeoMetaData
     */
    private function buildShowSeoMeta(ArticleEntity $article): SeoMetaData
    {
        $defaultKeywords = 'blog,部落格,個人文章,個人部落格,工程師,Laravel,laravel,php,後端工程師,backend';
        $fallbackDescription = Str::limit(strip_tags((string) $article->contentHtml), 150);
        $description = (string) (data_get($article->seo, 'description') ?? $fallbackDescription);
        $keywords = (string) (data_get($article->seo, 'keyword')
            ?? data_get($article->seo, 'keywords')
            ?? $defaultKeywords);
        $base = $this->siteBase();
        $customImage = data_get($article->seo, 'image');
        $hasCustomImage = is_string($customImage) && $customImage !== '';
        $ogImage = $hasCustomImage ? $customImage : $this->defaultOgImage($base);
        $title = (string) $article->title;
        $siteName = (string) config('seo.site_name');
        $canonical = route('articles.show', [
            'id' => $article->id,
            'slug' => ArticleSlug::from($article->slug, $article->title, $article->id),
        ]);
        $publishedTime = ($article->createdAt ?? $article->updatedAt)->toIso8601String();
        $modifiedTime = $article->updatedAt->toIso8601String();
        $person = $this->authorPerson($base);

        $jsonLd = [
            [
                '@context' => 'https://schema.org',
                '@type' => 'BlogPosting',
                'headline' => $title,
                'description' => $description,
                'datePublished' => $publishedTime,
                'dateModified' => $modifiedTime,
                'author' => $person,
                'publisher' => $person,
                'mainEntityOfPage' => $canonical,
                'image' => $ogImage,
                'inLanguage' => (string) config('seo.locale'),
                'keywords' => $keywords,
            ],
            [
                '@context' => 'https://schema.org',
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => $siteName, 'item' => $base.'/'],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => '文章列表', 'item' => route('articles.index')],
                    ['@type' => 'ListItem', 'position' => 3, 'name' => $title, 'item' => $canonical],
                ],
            ],
        ];

        return new SeoMetaData(
            title: $title,
            description: $description,
            keywords: $keywords,
            canonical: $canonical,
            siteName: $siteName,
            ogImage: $ogImage,
            articleAuthor: (string) $article->authorName,
            articleModifiedTime: $modifiedTime,
            articlePublishedTime: $publishedTime,
            ogImageAlt: $title,
            // 自訂圖若在 seo 帶了 image_width/image_height 就輸出，否則省略（尺寸未知）
            ogImageWidth: $hasCustomImage ? ((int) data_get($article->seo, 'image_width') ?: null) : (int) config('seo.og_image.width'),
            ogImageHeight: $hasCustomImage ? ((int) data_get($article->seo, 'image_height') ?: null) : (int) config('seo.og_image.height'),
            jsonLd: $jsonLd,
        );
    }

    private function siteBase(): string
    {
        return rtrim((string) config('app.url', 'http://localhost'), '/');
    }

    private function defaultOgImage(string $base): string
    {
        return $base.config('seo.og_image.path');
    }

    /**
     * @return array<string, string>
     */
    private function authorPerson(string $base): array
    {
        return [
            '@type' => 'Person',
            'name' => (string) config('seo.author_name'),
            'url' => $base.'/',
        ];
    }

    /**
     * Return fixed popular keyword list.
     *
     * @return array<int, string>
     */
    private function buildPopularKeywords(): array
    {
        return [
            'Docker',
            'Linux',
            'Node.js',
            'Nginx',
            'Ubuntu',
            'Laravel',
            'PHP',
            'MySQL',
            'Redis',
            'Git',
            'JavaScript',
            'Vue.js',
            'AWS',
            'Python',
            'React',
        ];
    }
}
