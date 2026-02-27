# UI 風格規劃（含 Dark Mode）

## 設計方向

- 主題：Technical Editorial（技術內容導向）
- 關鍵字：清晰、專業、可讀性高、程式碼友善
- 重點：文章閱讀體驗優先，互動元件次之

## 字體規劃

- 內文：`"Noto Serif TC", "Noto Serif", serif`
- UI 元件：`"Noto Sans TC", "Segoe UI", sans-serif`
- 程式碼：`"JetBrains Mono", "Fira Code", monospace`

## 色彩 Token

```css
:root {
  --bg: #f6f7f9;
  --surface: #ffffff;
  --text: #1f2937;
  --muted: #6b7280;
  --primary: #0f766e;
  --accent: #c2410c;
  --border: #d1d5db;

  --code-bg: #2f333d;
  --code-text: #cdd3de;
  --code-border: #292c33;
}

[data-theme="dark"] {
  --bg: #0f172a;
  --surface: #111827;
  --text: #e5e7eb;
  --muted: #9ca3af;
  --primary: #14b8a6;
  --accent: #fb923c;
  --border: #374151;

  --code-bg: #111827;
  --code-text: #e5e7eb;
  --code-border: #374151;
}
```

## 版型規格

- 內容寬度：`max-width: 900px`
- 文章行高：`1.8`
- 段落間距：`1.1em`
- 標題節奏：`h1 > h2 > h3` 明確層次

## Code Block 規格

- 區塊：`pre > code` 固定結構
- 可橫向捲動，不破壞版面
- 保持等寬字體與固定內距
- 亮暗主題都要有足夠對比

## 元件樣式規範

- 按鈕：三層級（primary/secondary/ghost）
- 卡片：圓角 10-12px、輕陰影
- 表單：focus ring 明確、錯誤態一致
- 提示：成功/警告/錯誤色彩一致

## Dark Mode 規劃

## 切換方式

- Header 提供切換按鈕（sun/moon icon）
- 使用 `localStorage.theme = light|dark|system`
- 首次進站預設 `system`

## 實作規範

- 在 `<html>` 或 `<body>` 上使用 `data-theme`
- 禁止在文章內容寫死 inline 顏色
- 圖片/插圖避免白底刺眼，可加暗色背景容器

## 可用性

- 文字對比至少達 WCAG AA
- 聚焦狀態可見
- 行動裝置下按鈕觸控區 >= 44px
