# API 與頁面合約

## 頁面路由（公開）

- `GET /`：首頁文章列表
- `GET /article`：文章列表（支援 `page`, `search`）
- `GET /article/{id}`：文章詳情
- `GET /sitemap.xml`：網站地圖

## 關鍵 Query 參數

- `page`：正整數，預設 1
- `search`：字串，最大 100 字

## 回應合約（頁面）

- 文章列表每筆最少包含：
  - `id`, `title`, `content_preview`, `author_name`, `created_at`, `comments_count`
- 文章詳情最少包含：
  - `id`, `title`, `content_html`, `seo`, `updated_at`

## 留言 API（建議）

- `POST /api/comments`
  - request: `article_id`, `content`
  - response: `id`, `article_id`, `content`, `created_at`

## 錯誤規格

- 400：驗證失敗
- 401：未登入
- 403：無權限
- 404：文章不存在
- 429：搜尋/留言頻率過高

## 安全規格

- 所有寫入 API 啟用 CSRF（Web）/ token 驗證（API）
- 留言內容做 XSS sanitize
- 搜尋、留言接口加 rate limit
