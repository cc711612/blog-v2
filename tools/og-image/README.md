# 文章分享圖（og:image）產生方式

- 版型：`og-article.html`（每篇文章）、`og-default.html`（全站預設，輸出成 `public/images/og-default.png`）
- 規格：1200×630，JPEG quality 86，輸出到 `public/images/og/{文章 id}.jpg`
- 產生：用 Playwright（或任何 headless Chromium）開啟 `og-article.html`，設定 viewport 1200×630，
  呼叫 `window.render({ cat, title, series })` 後截圖：
  - `cat`：標題【】裡的分類（例：監控）
  - `title`：去掉【分類】後的標題
  - `series`：系列文才填（例：`自架 VM 維運實戰 4/5`），一般文章填 `null`
- 套用：文章 `seo` 欄位加上
  `{"image": "https://roy.usongrat.tw/images/og/{id}.jpg", "image_width": 1200, "image_height": 630}`
- 字型：版型用 PingFang TC / Noto Sans TC，請在有中文字型的環境產生（例如 macOS）。
