<?php

namespace App\Support;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * 文章 HTML（存在 DB 的舊內容）輸出前的白名單 sanitize，對應 docs/rebuild/07-architecture-and-adr.md 的 ADR-001。
 *
 * 保留文章實際會用到的版面標籤：標題、段落、清單、pre/code（含 highlight.js 用的 class）、表格、圖片、連結、引用等；
 * 移除 script/style/iframe/object/form、on* 事件屬性與 javascript: 之類的危險 URL。
 * 若某篇文章需要嵌入 iframe（例如影片），要在這裡明確加白名單，不要直接放行。
 */
class ArticleHtmlSanitizer
{
    private static ?HtmlSanitizer $sanitizer = null;

    public static function clean(?string $html): string
    {
        return self::sanitizer()->sanitize((string) $html);
    }

    private static function sanitizer(): HtmlSanitizer
    {
        return self::$sanitizer ??= new HtmlSanitizer(self::config());
    }

    private static function config(): HtmlSanitizerConfig
    {
        // 注意：allowAttribute() 會把該屬性從「不在清單內」的元素移除，所以每個屬性只能呼叫一次、一次給完整元素清單
        $classIdElements = [
            'pre', 'code', 'span', 'div', 'p', 'a', 'img', 'table', 'thead', 'tbody', 'tfoot', 'tr', 'td', 'th',
            'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'ul', 'ol', 'li', 'blockquote', 'figure', 'figcaption',
            'strong', 'em', 'b', 'i', 'kbd', 'samp', 'section', 'details', 'summary',
        ];

        return (new HtmlSanitizerConfig)
            // W3C Sanitizer API 的「安全」元素與屬性：不含 script/style/iframe/object/form/on* 事件
            ->allowSafeElements()
            // 圖片不在 safe 清單內，明確允許（只放行必要屬性）
            ->allowElement('img', ['src', 'alt', 'title', 'width', 'height', 'loading', 'class', 'id'])
            ->allowLinkSchemes(['http', 'https', 'mailto'])
            ->allowMediaSchemes(['http', 'https', 'data'])
            ->allowRelativeLinks()
            ->allowRelativeMedias()
            ->allowAttribute('class', $classIdElements)
            ->allowAttribute('id', $classIdElements)
            ->allowAttribute('target', 'a')
            ->forceAttribute('a', 'rel', 'noopener noreferrer')
            ->withMaxInputLength(2_000_000);
    }
}
