# Laravel Admin Starter

以 Laravel 13 與 Vue 3 建置的開源後台管理系統與官網前台,包含權限控管(RBAC)、中英雙語介面、
所見即所得的產品內容管理、媒體庫,以及操作稽核紀錄。全部跑在 Docker 裡,
本機除了 Docker Desktop 之外不需要安裝任何東西。

**English: [README.md](README.md)**

後台在 `/admin`,根路徑是讀取同一份型錄的官網前台。後台改完,訪客立刻看得到 ——
重點就在這裡:這是一套會動的系統,不是一堆管理表單。

| | |
| --- | --- |
| ![後台儀表板](docs/screenshots/admin-dashboard.png) | ![操作紀錄](docs/screenshots/admin-activity.png) |
| 儀表板 —— 統計、註冊趨勢、最近操作 | 操作紀錄 —— 誰在什麼時候做了什麼 |
| ![產品管理](docs/screenshots/admin-products.png) | ![官網前台](docs/screenshots/public-products.png) |
| 產品 —— 拖曳決定官網顯示順序 | 官網,依照那個順序呈現 |

## 包含哪些功能

- **認證系統** —— 註冊、登入、登出、忘記密碼、重設密碼、Email 驗證。
  Sanctum 採 SPA cookie 模式,瀏覽器端不儲存任何 token。
- **RBAC 權限** —— 角色與權限為多對多關聯,`Gate::before` 讓 admin 通行,
  路由中介層負責「能不能碰這個功能」,Policy 負責「能不能碰這一筆資料」。
- **儀表板** —— 統計卡片、30 天註冊趨勢(Chart.js)、角色分佈、最近操作。
- **使用者管理** —— 搜尋、角色與驗證狀態篩選、排序、分頁,
  並防止 manager 把自己升成 admin。
- **雙語介面** —— 前端**與 API 都**支援中英切換,所以驗證錯誤訊息會跟著介面語言走。
  語言偏好跟著帳號跑。
- **產品型錄** —— TipTap 富文本(寫入時淨化)、分語言內容、封面圖、草稿/發佈,
  以及官網真的會遵守的拖曳排序。
- **媒體庫** —— 拖曳上傳、預覽、刪除,選圖器直接串進編輯器的圖片按鈕。
- **操作紀錄** —— 登入、登入失敗與內容異動,而且在對應的帳號或資料被刪除之後仍然讀得懂。
- **官網前台** —— 產品列表與詳情頁,免登入,只顯示已發佈的產品。

141 個測試,全數通過。

## 技術棧

| 分類 | 技術 |
| --- | --- |
| 後端 | Laravel 13, PHP 8.4 |
| 認證 | Laravel Sanctum(SPA / cookie 模式) |
| 前端 | Vue 3, Vue Router, Pinia, Vite 8, Tailwind CSS 4 |
| 編輯器 | TipTap 3(ProseMirror) |
| HTML 淨化 | symfony/html-sanitizer |
| 圖表 | Chart.js 4 |
| 多語系 | vue-i18n 11 + Laravel lang 檔 |
| 資料庫 | MySQL 8.4 |
| 快取 / 佇列 | Redis 7 |
| 網頁伺服器 | Nginx |
| 容器化 | Docker Compose |

## 開始使用

需要 [Docker Desktop](https://www.docker.com/products/docker-desktop/)。
PHP、Node、MySQL、Redis 全部跑在容器裡。

```bash
git clone https://github.com/<your-account>/laravel-admin-starter.git
cd laravel-admin-starter

cp .env.example .env

docker compose up -d --build

docker compose exec php php artisan key:generate
docker compose exec php php artisan migrate --seed
docker compose exec php php artisan storage:link
```

接著開啟 <http://localhost:8080> 看官網,<http://localhost:8080/admin> 進後台。

### Demo 帳號

由 `artisan db:seed` 建立,同時會塞入一小份範例型錄。三個帳號密碼都是 `password`。

| Email | 角色 | 可以做什麼 |
| --- | --- | --- |
| `admin@example.com` | 管理員 | 全部,包含操作紀錄 |
| `manager@example.com` | 管理者 | 產品、媒體,以及檢視/新增/編輯使用者 |
| `user@example.com` | 一般使用者 | 只有自己的個人資料 |

建議用 manager 登入看看 RBAC 從另一側長什麼樣子:側邊欄沒有操作紀錄、
使用者列表沒有刪除按鈕,而 API 拒絕的東西和介面藏起來的東西是同一批。

範例型錄裡刻意留了一筆草稿。它會出現在後台、不會出現在官網 ——
這是確認「發佈狀態真的有作用」最快的方法。

### 服務位址

| 服務 | 位址 |
| --- | --- |
| 應用程式 | <http://localhost:8080> |
| Vite dev server | <http://localhost:5173> |
| MySQL | `localhost:3306`(`laravel` / `secret`) |
| Redis | `localhost:6379` |

如果有埠號被佔用,在 `.env` 改 `APP_PORT`、`VITE_PORT`、`FORWARD_DB_PORT`、`FORWARD_REDIS_PORT`。

### 常用指令

```bash
docker compose exec php php artisan migrate:fresh --seed   # 重置資料庫
docker compose exec php php artisan test                   # 跑測試
docker compose exec php php vendor/bin/pint                # 格式化 PHP
docker compose exec node npm run build                     # 前端正式版打包
docker compose logs -f node                                # 追蹤 Vite dev server
docker compose down -v                                     # 停止並刪除 volume
```

## 專案結構

```text
app/
  Http/Controllers/          後台 API,以及官網用的 PublicProductController
  Http/Resources/            決定各種對象「看得到什麼」
  Models/Concerns/           HasRoles、LogsActivity
  Policies/                  以單筆資料為單位的授權
  Support/                   RichText(HTML 淨化)、ActivityRecorder、Locales
resources/js/
  layouts/                   AppLayout(後台側邊欄)、PublicLayout(官網)
  pages/                     每個功能一個目錄,官網在 pages/public/
  stores/auth.js             session、權限、語系
docs/
  ROADMAP.md                 功能追蹤與工程筆記(英文)
  ROADMAP.zh-TW.md           同上,繁體中文
```

動手改之前,有幾個決定值得先知道。每一條背後的理由,以及另外三十幾條,
都寫在 [docs/ROADMAP.zh-TW.md](docs/ROADMAP.zh-TW.md)。

- **HTML 在寫入時淨化,不是在輸出時。** 資料庫裡永遠只有安全的 HTML,
  所以之後任何一個忘記轉義的模板都復活不了那個漏洞。這也是官網敢用 `v-html` 的原因。
- **`Gate::before` 讓 admin 通過一切,但操作對象是自己時例外。** 否則像
  「任何人都不能刪除自己」這種規則,對最需要它的那個帳號根本不會執行。
- **授權跑在驗證之前**(`HasMiddleware` 掛 `can:` 中介層),沒權限的人拿到乾脆的 403,
  而不是一串洩漏資料結構的 422。
- **公開的 API 資源用白名單。** 以後新增的欄位在有人決定公開之前,官網上是不存在的。
- **草稿回 404 而不是 403。** 403 等於確認那個產品存在。
- **多型欄位存短別名**,不是 `App\Models\Product`,這樣類別改名不會弄壞已經寫下的歷史。

## 測試

```bash
docker compose exec php php artisan test
```

測試跑在記憶體內的 SQLite 上,而且守的是授權邊界而不只是順利路徑:
提權、刪除自己、草稿從篩選旁邊溜過去、密碼寫進操作紀錄、SVG 進到媒體庫。

這個專案裡有兩個 bug 是**測試全綠之後、打開瀏覽器才發現的** ——
兩個都寫進了工程筆記,因為那個教訓是通用的。

## 已知限制

- **沒有伺服器端渲染。** `usePageMeta` 會設定 title 與 meta description,
  對瀏覽器和會執行 JavaScript 的爬蟲有效,不跑 JS 的爬蟲看到的仍是空殼。
  官網真的要做 SEO 需要 SSR 或預渲染。
- **還沒有原始 HTML 編輯模式。** 類似 WordPress「文字」檢視的功能已經設計好但尚未實作;
  作法(新增 `products.publish_html` 權限,搭配第二份定義在後端的白名單)寫在 ROADMAP 裡。
- **佇列目前是同步執行。** Redis 已經接好,但寄信等工作仍在程序內執行;
  需要的話改 `QUEUE_CONNECTION` 並加一個 worker 容器。
- **Vite dev server 用輪詢偵測檔案變更。** 在 Windows 與 macOS 上是必要的,
  因為 bind mount 不會把 inotify 事件送進容器。無害,但會吃一些 CPU。

## 參與貢獻

歡迎開 issue 與 pull request。本機檢查怎麼跑、以及這裡認為什麼是有用的 PR,
寫在 [CONTRIBUTING.md](CONTRIBUTING.md)。

## 授權

[MIT](LICENSE)
