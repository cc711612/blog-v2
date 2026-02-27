# 工程開發規範（SOLID / TDD / 模組化）

## 目標

- 確保重做專案具備可測試、可替換、可維護的程式結構。
- 將「業務邏輯」從 Laravel 框架細節中解耦。

## 1) SOLID 準則（必遵守）

- `S`：每個 class 單一職責
  - Controller 只做 I/O 與協調，不寫業務邏輯
  - Service 只做 Use Case
- `O`：透過介面擴充，不改既有核心流程
- `L`：實作類可被介面替換，不破壞行為
- `I`：依功能拆小介面（不要巨型 repository interface）
- `D`：高層模組依賴 abstraction，不直接依賴 Eloquent 實作

## 2) 分層與模組（Module + Entity + Service + Repository）

建議目錄：

```text
app/
  Modules/
    Content/
      Domain/
        Entities/
        Repositories/
        Services/
      Application/
        UseCases/
        DTOs/
      Infrastructure/
        Persistence/
          Eloquent/
            Repositories/
      Http/
        Controllers/
        Requests/
        Resources/
```

規範：

- `Entity`：業務狀態與規則（非 Eloquent model）
- `Repository Interface`：Domain 層定義
- `Repository Implementation`：Infrastructure 層實作（Eloquent/Cache）
- `Service/UseCase`：組合 repository 完成用例
- `Controller`：收 request -> 呼叫 use case -> 回 resource

## 3) Laravel Form Request（必用）

- 所有寫入 API（store/update/vote/like）必須使用 `FormRequest`。
- 驗證規則與授權放在 Request class，不放 Controller。
- 需提供一致錯誤格式（422 JSON 或 Blade error bag）。

## 4) Laravel API Resource（必用）

- API 輸出統一走 `Resource` / `ResourceCollection`。
- 禁止直接回傳 Eloquent model 到外部。
- Resource 內處理欄位白名單、型別、時間格式。

## 5) TDD 規範（必用）

- 開發流程：
  1. 先寫測試（Fail）
  2. 最小實作（Pass）
  3. 重構（Refactor）

測試分層：

- Unit Test：UseCase / Service / Domain Entity
- Contract Test：Repository Interface 對實作行為一致
- Feature Test：Controller + Request + Resource 的輸入輸出

## 6) 測試限制（你指定的規範）

- 測試 **不觸碰實際 database**。
- 測試 **不使用 `RefreshDatabase`**。

執行方式：

- Unit/UseCase 測試以 mock/fake repository 完成。
- Feature 測試用 in-memory fake 或 repository fake，驗證 HTTP contract。
- 若一定要整合 DB，放到獨立 integration test pipeline，不算日常 TDD 套件。

## 7) 範例測試策略

- `CreateCommentUseCaseTest`
  - mock `CommentRepositoryInterface`
  - 驗證內容 sanitize、狀態預設值、回傳 DTO
- `SearchArticlesActionTest`
  - fake repository 回傳固定資料
  - 驗證分頁、排序、關鍵字過濾
- `ArticleControllerTest`
  - 驗證 `FormRequest` 驗證失敗格式
  - 驗證 `Resource` 回傳欄位白名單

## 8) Code Review Gate

PR 必須滿足：

- 新功能有對應測試
- 未引入胖 Controller / 胖 Model
- Request/Resource 覆蓋完整
- 無直接耦合 `DB::table` 在 Domain/Application 層
- 測試不含 `RefreshDatabase`
