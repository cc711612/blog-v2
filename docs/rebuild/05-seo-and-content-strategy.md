# SEO 與內容策略

## URL 策略

- 文章 canonical：`/article/{id}`（未來可加 slug）
- 列表頁可索引分頁：`/article?page=n`
- 搜尋頁：`/article?search=...`（建議 noindex）

## Sitemap 策略

- 收錄：首頁、文章列表、公開文章頁
- `lastmod` 使用資料庫 `updated_at`
- 每日定時重建 sitemap（或內容更新即觸發）

## Meta 策略

- 首頁：固定品牌 SEO
- 文章頁：title/description/og:image 由文章 seo 欄位或 fallback 生成
- canonical 必填，避免重複內容

## 301 轉址規格

- 舊站文章 URL -> 新站 canonical URL
- 保留查詢參數的必要部分（例如 `utm_*`）

## SEO 驗證

- Search Console：coverage 無大量 soft 404
- Lighthouse SEO >= 90
- 隨機抽樣 20 篇文章，meta 與 og 正常
