# 維運與監控規劃

## 監控指標

- Web：
  - HTTP 5xx rate
  - P95 latency
  - 首頁 LCP
- App：
  - 留言建立失敗率
  - 搜尋 429 次數
  - Livewire 例外數
- DB/Cache：
  - 慢查詢數
  - Redis hit ratio

## 告警門檻（建議）

- 5xx > 1%（5 分鐘）
- P95 > 1.5s（10 分鐘）
- 留言失敗率 > 3%（10 分鐘）

## 日誌規範

- 必記：request_id, user_id, route, status, duration
- 例外需包含 stacktrace 與上下文
- 敏感資料（token/email）需遮罩

## 值班 SOP

1. 先看告警來源（app/db/network）
2. 查 error log 與近期 deploy
3. 判斷是否回滾
4. 事故後補 RCA

## 週期性維護

- 每週：慢查詢與索引檢討
- 每月：sitemap / redirect 清單抽查
- 每季：依賴套件安全更新
