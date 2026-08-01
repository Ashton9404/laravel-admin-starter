# Laravel Admin Starter — Feature & Tech Tracker

> 這份文件是專案的「單一事實來源」。每完成一個功能就更新對應狀態,
> 最終這份文件的內容會被整理進 `README.md`(英文)與 `README.zh-TW.md`(繁中)。
>
> 狀態標記: `⬜ 未開始` `🟨 進行中` `✅ 完成`

---

## 開發原則(取代固定 30 天排程)

不綁定天數,採用「功能完成才進下一個」的順序開發。順序如下:

1. 專案初始化(Laravel + Docker + Vue 3 + Vite)
2. 認證系統(Login / Register / Forgot Password / Email Verification)
3. RBAC 權限系統(Middleware / Policy / Gate)
4. Dashboard(統計卡片 + Chart.js + 最近活動)
5. 使用者管理 CRUD
6. 雙語系(vue-i18n,EN / 繁中)
7. CMS 文章模組(多語系內容)
8. 檔案管理模組
9. Activity Log
10. README(英文 + 繁中)+ 開源文件整理

每完成一項,會在下方對應區塊把狀態改成 ✅,並補上「使用的技術」與「工程筆記」。

---

## 功能清單與狀態

### 1. 專案初始化

- 狀態: ✅(2026-08-01 完成,五個容器實測啟動並通過端對端驗證)
- 內容: Laravel 專案建立、Docker Compose(Nginx + PHP + MySQL + Redis)、Vue 3 + Vite 前端腳手架
- 技術: Laravel 13.23, PHP 8.4, Sanctum 4, Docker Compose, Nginx, MySQL 8.4, Redis 7, Vue 3, Vite 8, Pinia, Tailwind CSS 4
- 已完成:

  - Laravel 13 骨架 + Sanctum(SPA 模式,`statefulApi()` 已掛上)
  - Vue 3 SPA 外殼:`resources/views/app.blade.php` + Vue Router history 模式 + Pinia
  - `routes/web.php` catch-all 交給前端路由,排除 `api` / `sanctum` / `storage` / `up`
  - `GET /api/ping` 健康檢查,首頁會實際打這支 API 驗證前後端串接
  - Docker: `docker-compose.yml` + `docker/php/Dockerfile` + `docker/nginx/default.conf`

- 驗證結果(2026-08-01):

  - `docker compose up -d --build` → las-nginx / las-php / las-mysql / las-redis / las-node 五個容器全部 Up
  - `artisan migrate` 對 MySQL 8.4 容器跑完 4 個 migration
  - `GET http://localhost:8080/api/ping` → `{"message":"pong","laravel":"Laravel 13.23.0","php":"8.4.24"}`
  - Vite dev server 在容器內啟動,Blade 正確輸出 `http://localhost:5173/@vite/client`(HMR 可用)
  - `Cache::put/get` 走 Redis 成功(`store=redis`),phpredis 擴充已編進 image

### 2. 認證系統
- 狀態: ⬜
- 內容: 註冊、登入、忘記密碼、重設密碼、Email 驗證
- 技術: Laravel Sanctum(SPA 模式)、Laravel Notifications(Email)

### 3. RBAC 權限系統
- 狀態: ⬜
- 內容: Admin / Manager / User 角色、users.view/create/update/delete 權限
- 技術: Middleware、Policy、Gate、多對多關聯(roles ↔ permissions ↔ users)

### 4. Dashboard
- 狀態: ⬜
- 內容: 統計卡片、圖表、最近活動列表
- 技術: Chart.js、Laravel API Resource

### 5. 使用者管理 CRUD
- 狀態: ⬜
- 內容: 搜尋、分頁、篩選
- 技術: Eloquent Query Builder、Vue 3 Composition API、Pinia

### 6. 雙語系(UI)
- 狀態: ⬜
- 內容: EN / 中文切換、Navbar 語言選單、記憶語言偏好
- 技術: vue-i18n、localStorage、users.locale 欄位同步

### 7. CMS 文章模組
- 狀態: ⬜
- 內容: 建立/編輯/刪除文章、草稿/發佈、多語系內容、封面圖上傳
- 技術: articles + article_translations 資料表設計、檔案上傳

### 8. 檔案管理模組
- 狀態: ⬜
- 內容: 上傳、預覽、刪除
- 技術: Laravel Filesystem、Vue 上傳元件

### 9. Activity Log
- 狀態: ⬜
- 內容: 登入紀錄、CRUD 操作紀錄
- 技術: Polymorphic 關聯(subject_type / subject_id)

### 10. README 與開源文件
- 狀態: ⬜
- 內容: README.md(英文)、README.zh-TW.md(繁中)、Demo 帳號、Screenshots、Contribution Guide
- 技術: Markdown、GitHub Actions(可選,CI badge)

---

## 完整技術棧總覽(隨進度持續更新)

| 分類 | 技術 |
|---|---|
| 後端框架 | Laravel 13, PHP 8.4 |
| 前端框架 | Vue 3, Vite, Pinia, Axios, Tailwind CSS |
| 認證 | Laravel Sanctum |
| 資料庫 | MySQL 8 |
| 快取/佇列 | Redis |
| 容器化 | Docker, Docker Compose, Nginx |
| 多語系 | vue-i18n(前端)、Laravel lang(後端) |
| 圖表 | Chart.js |
| 版本控制策略 | GitHub Flow |

---

## 環境決策紀錄(Environment Decisions)

- **2026-08-01**: 原規劃寫 Laravel 11 / PHP 8.3。實際盤點時 Laravel 最新穩定版為 13.x(最低需求 PHP 8.3),
  故決定升級至 **PHP 8.4 + Laravel 13**,讓開源專案在 README 上維持「最新版」的說服力。
- **2026-08-01**: 開發環境選擇 **Docker Desktop 全程容器化**(而非本機 XAMPP),理由是環境乾淨、
  貼近正式部署,且 Docker Compose 本身就是這個 starter 要展示的技術之一。
- **2026-08-01**: Docker 資料碟從 C 槽搬到 `D:\DockerData`(C 槽只剩 60 GB)。
  WSL2 後端的「Disk image location」只能從 GUI 改,而本機 GUI 無法啟動(見下方筆記),
  故改用 NTFS junction:`%LOCALAPPDATA%\Docker\wsl` → `D:\DockerData\wsl`。
  已實測 image pull 後 vhdx 在 D 槽長大,C 槽無殘留。

---

## 工程筆記(Engineering Notes)

> 每完成一個功能後,在這裡補充「為什麼這樣設計」的簡短說明,
> 這是你未來面試或撰寫技術部落格時最直接可用的素材。

### 筆記 1 — 專案初始化

**為什麼選 Sanctum SPA(cookie)而不是 Token?**
前後端同源(都由 Nginx 出去,`localhost:8080`),用 session cookie 就不必在前端存 token,
天然免疫 localStorage XSS 竊取。代價是要設定 `SANCTUM_STATEFUL_DOMAINS` 和 `SESSION_DOMAIN`,
且 axios 必須開 `withCredentials` / `withXSRFToken`。

**為什麼 `routes/web.php` 用一條 catch-all?**
Vue Router 走 history 模式,使用者直接輸入 `/users/3` 時請求會先到 Laravel。
catch-all 讓所有非 API 路徑都回同一份 Blade 外殼,再由前端路由決定畫面;
regex 排除 `api|sanctum|storage|up`,避免把 API 與健康檢查也吃掉。

**為什麼 node 服務要掛 anonymous volume 在 `node_modules`?**
專案目錄是 Windows bind mount,主機上裝的是 Windows 版 esbuild/rollup 原生二進位檔,
直接讓 Alpine 容器共用會炸。用 anonymous volume 讓容器保有自己的 Linux 版 `node_modules`,
主機的則留給 IDE 做型別提示,兩邊互不干擾。

**為什麼 MySQL 要設 healthcheck + `depends_on: condition: service_healthy`?**
MySQL 容器「啟動」和「可連線」之間有數秒落差,沒有 healthcheck 的話
第一次 `artisan migrate` 會隨機失敗。這是 Docker 新手最常踩的坑。

**Docker Desktop GUI 打不開 ≠ Docker 壞掉**
本機的 Docker Desktop 視窗一開就跳 "Unable to launch Docker Desktop",log 顯示是 Electron 的
GPU 子程序以 `0xC0000005`(存取違規)崩潰。原因是機器上跑著 NVIDIA Overlay / MSI Afterburner /
Wallpaper Engine 這類會注入 hook DLL 的 overlay 軟體,Chromium 的 GPU sandbox 對此極度敏感。
關鍵認知:**GUI 只是個 Electron 前端,引擎是獨立的**。`docker desktop start/stop`、
`docker compose`、`docker exec` 全部照常運作,日常開發完全不需要那個視窗。
(`--disable-gpu` 無效,因為 `Docker Desktop.exe` 只是 Go 啟動器,不轉送參數給 Electron。)

**PHP 版本決策**
Laravel 13 最低需求是 PHP 8.3,本機 XAMPP 是 8.2,因此另外裝了 PHP 8.4 到 `C:\php84`。
注意 Windows 的 system PATH 排在 user PATH 前面,所以直接打 `php` 仍會是 XAMPP 的 8.2,
執行 composer/artisan 前要先 `$env:Path = 'C:\php84;' + $env:Path`。
