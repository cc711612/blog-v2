# blog-v2

Laravel 12 部落格重構專案。此版本以「SEO 友善 + 舊資料相容 + 分層架構」為核心，逐步從舊版 `blog` 遷移。

---

## 中文說明

### 1) 技術棧

- PHP 8.2+
- Laravel 12
- Livewire 3（互動留言區）
- MySQL（主要資料庫）
- SQLite（測試用）
- PWA（manifest + service worker）

### 2) 架構設計

內容模組採用 **Entity / Service / Repository 三層式架構**（對應 DDD 常見分層）：

- **Entity（領域模型）**
  - 路徑：`app/Modules/Content/Domain/Entities`
  - 例：`ArticleEntity`、`CommentEntity`
- **Service（應用服務）**
  - 路徑：`app/Modules/Content/Application/Services`
  - 例：`ListArticlesService`、`ShowArticleService`、`AddCommentService`
- **Repository（資料存取抽象 + 實作）**
  - 介面：`app/Modules/Content/Domain/Repositories`
  - 實作：`app/Modules/Content/Infrastructure/Persistence`（`Eloquent` / `InMemory`）

分層責任：

- Service 只處理流程與用例，不直接耦合 ORM
- Repository 負責資料來源切換與查詢細節
- Entity 保持框架無關的領域資料結構

其他 Web 層：
- `app/Http/Controllers/Web`
  - 頁面組裝、SEO 資料整合
- `app/ViewModels/SeoMetaData.php`
  - 統一 SEO 載體（避免 Blade 做過多邏輯）

前端策略：

- 列表/內頁採 Blade SSR（SEO 優先）
- 留言採 Livewire（互動功能）
- highlight.js 僅於文章內頁載入

### 3) 功能重點

- 文章列表與內頁
- SEO meta（title/description/keywords/og/canonical）
- 留言新增與顯示
- 動態 sitemap：`/sitemap.xml`
- PWA：`/manifest.json`、`public/sw.js`
- 舊資料相容（欄位/內容格式）

### 4) 安裝與啟動

```bash
composer install
cp .env.example .env
php artisan key:generate
```

設定 `.env` 資料庫後執行：

```bash
php artisan migrate --seed
php artisan serve
```

Docker 部署（參考舊版 local-blog 結構）：

```bash
cp deployment/local-blog/.env.example deployment/local-blog/.env
# 編輯 deployment/local-blog/.env，至少調整 PROJECT_PATH
docker compose --env-file deployment/local-blog/.env -f deployment/local-blog/docker-compose.yml up -d --build
```

若目前目錄已在 `deployment/local-blog`，可直接使用：

```bash
docker compose -p blogv2 --env-file .env -f docker-compose.yml up -d --build
```

同一台 VM 跑多個專案時，建議加 `-p` 指定專案名稱避免資源命名衝突：

```bash
docker compose -p blogv2 --env-file deployment/local-blog/.env -f deployment/local-blog/docker-compose.yml up -d --build
```

啟動後：

- App: `http://localhost:${NGINX_HTTP_PORT}`
- DB: 請使用專案 `.env` 內設定的外部 MySQL 連線

重建資料：

```bash
php artisan migrate:fresh --seed
```

### 5) 測試

```bash
php artisan test
```

`tests/TestCase.php` 有安全防護：

- 必須是 `APP_ENV=testing`
- 必須使用 `sqlite`

避免 `RefreshDatabase` 誤傷正式資料庫。

### 6) 主要路由

- `GET /`、`GET /article`：文章列表
- `GET /article/{id}`：文章內頁
- `GET /api/articles`：文章 API
- `POST /api/comments`：留言 API
- `GET /sitemap.xml`：動態 sitemap
- `GET /manifest.json`：PWA manifest

---

## English

### 1) Tech Stack

- PHP 8.2+
- Laravel 12
- Livewire 3 (interactive comments)
- MySQL (primary DB)
- SQLite (testing)
- PWA (manifest + service worker)

### 2) Architecture

The content module follows an **Entity / Service / Repository** layered design:

- **Entity layer**
  - Path: `app/Modules/Content/Domain/Entities`
  - Examples: `ArticleEntity`, `CommentEntity`
- **Service layer**
  - Path: `app/Modules/Content/Application/Services`
  - Use cases: `ListArticlesService`, `ShowArticleService`, `AddCommentService`
- **Repository layer**
  - Contracts: `app/Modules/Content/Domain/Repositories`
  - Implementations: `app/Modules/Content/Infrastructure/Persistence` (`Eloquent`, `InMemory`)

Layer responsibilities:

- Services orchestrate use cases without direct ORM coupling
- Repositories encapsulate data-access details and source switching
- Entities keep domain data framework-agnostic

Other web-layer parts:
- `app/Http/Controllers/Web`
  - Page orchestration and SEO assembly
- `app/ViewModels/SeoMetaData.php`
  - Typed SEO payload for views

Frontend strategy:

- Blade SSR for SEO-sensitive pages
- Livewire for interactive parts (comments)
- highlight.js loaded only on article detail page

### 3) Features

- Article list and detail pages
- SEO meta generation (title/description/keywords/og/canonical)
- Comment posting and listing
- Dynamic sitemap at `/sitemap.xml`
- PWA support (`/manifest.json`, `public/sw.js`)
- Legacy schema/content compatibility

### 4) Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Configure DB in `.env`, then run:

```bash
php artisan migrate --seed
php artisan serve
```

Docker deploy (based on legacy local-blog layout):

```bash
cp deployment/local-blog/.env.example deployment/local-blog/.env
# Edit deployment/local-blog/.env, especially PROJECT_PATH
docker compose --env-file deployment/local-blog/.env -f deployment/local-blog/docker-compose.yml up -d --build
```

If your current directory is already `deployment/local-blog`, you can run:

```bash
docker compose -p blogv2 --env-file .env -f docker-compose.yml up -d --build
```

For multi-project VM usage, prefer `-p` to isolate resource naming:

```bash
docker compose -p blogv2 --env-file deployment/local-blog/.env -f deployment/local-blog/docker-compose.yml up -d --build
```

After startup:

- App: `http://localhost:${NGINX_HTTP_PORT}`
- DB: use external MySQL defined in app `.env`

Reset database:

```bash
php artisan migrate:fresh --seed
```

### 5) Testing

```bash
php artisan test
```

Safety guard in `tests/TestCase.php` requires:

- `APP_ENV=testing`
- `sqlite` driver

This prevents destructive test execution on non-test databases.

### 6) Main Routes

- `GET /`, `GET /article` - article list
- `GET /article/{id}` - article detail
- `GET /api/articles` - article API
- `POST /api/comments` - comment API
- `GET /sitemap.xml` - dynamic sitemap
- `GET /manifest.json` - PWA manifest
