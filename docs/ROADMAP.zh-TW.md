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

- 狀態: ✅(2026-08-01 完成,26 個 Feature 測試全過 + 端對端實測)
- 內容: 註冊、登入、登出、忘記密碼、重設密碼、Email 驗證
- 技術: Laravel Sanctum(SPA cookie 模式)、Laravel Notifications、Pinia、Vue Router 導航守衛
- API:

  - `POST /api/register`(註冊即登入,並寄出驗證信)
  - `POST /api/login`(email + IP 複合限流,5 次鎖定)
  - `POST /api/logout`
  - `POST /api/forgot-password`、`POST /api/reset-password`
  - `GET /api/verify-email/{id}/{hash}`(簽名連結)、`POST /api/email/verification-notification`
  - `GET /api/user`

- 前端頁面: Login / Register / ForgotPassword / ResetPassword / VerifyEmail / Dashboard
- 驗證結果(2026-08-01):

  - `php artisan test` → 26 passed(69 assertions)
  - 端對端: CSRF 交握 → 註冊 201 → `/api/user` 200 → 登出 200 → 登入 200 → 未登入 401
  - 驗證信連結實際點擊後 `email_verified_at` 正確寫入

### 3. RBAC 權限系統

- 狀態: ✅(2026-08-01 完成,測試累計 42 passed)
- 內容: Admin / Manager / User 角色、users.view/create/update/delete 權限
- 技術: Middleware、Policy、Gate、多對多關聯(roles ↔ permissions ↔ users)
- 實作:

  - 資料表 `roles` / `permissions` / `permission_role` / `role_user`(複合主鍵 + cascade delete)
  - `HasRoles` trait:`hasRole()` / `hasPermission()` / `permissionNames()`,權限清單在實例內記憶化
  - `Gate::before` 讓 admin 通過所有檢查(回傳 `null` 而非 `false`,才不會蓋掉其他 policy)
  - `permission:users.view` 路由中介層,支援多權限擇一(OR)
  - `UserPolicy`:本人可讀寫自己;任何人都不能刪除自己
  - Demo 帳號 `admin@ / manager@ / user@example.com`,密碼皆為 `password`

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

### 7. 產品模組(官網內容管理)

- 狀態: ⬜
- 內容: 後台新增 / 編輯 / 刪除產品、所見即所得(WYSIWYG)富文本內容、
  封面圖與圖庫上傳、草稿 / 發佈、**拖曳決定官網顯示順序**、多語系內容
- 技術: products + product_translations 資料表(含 `sort_order`)、
  **TipTap** 富文本編輯器、HTML 淨化、檔案上傳、vuedraggable 拖曳排序
- 備註: 2026-08-01 由使用者提出。原規劃的「CMS 文章模組」併入此模組,
  資料表設計相同,差別只在語意;若之後真的需要部落格文章再獨立拆出。

### 11. 官網前台(公開頁面)

- 狀態: ⬜
- 內容: 不需登入即可瀏覽的產品列表 `/products` 與詳情頁 `/products/{slug}`,
  排序直接吃後台設定的 `sort_order`,只顯示已發佈的產品
- 技術: 公開 API(`GET /api/public/products`)、Vue Router 公開路由、SEO meta
- 備註: 2026-08-01 追加。目的是讓後台的操作有地方「看得到結果」——
  排序拖一拖、內容改一改,前台立刻反映,這樣這個專案在履歷與作品集上
  才是一個「能實際運作的系統」,而不只是一堆管理表單。

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
| --- | --- |
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

### 筆記 3 — RBAC

**為什麼 admin 角色在 seeder 裡一條權限都沒有?**
因為 `Gate::before` 已經讓它無條件通過。如果同時又在資料表裡列出 admin 的所有權限,
就變成兩個事實來源——之後新增一個 `products.delete`,忘了補進 admin 的清單,
就會出現「管理員反而不能刪產品」的鬼故事。讓 admin 保持空集合,權限只描述「非管理員」。

**`Gate::before` 為什麼要回傳 `null` 而不是 `false`?**
`false` 是「明確拒絕」,會直接短路掉後面的 policy;`null` 是「我沒意見」,
交還給正常的授權流程決定。回錯的話所有非管理員會被一律擋死。

**中介層 vs Policy 怎麼分?**
`permission:users.view` 這種路由中介層回答的是「你有沒有資格碰這個功能」;
Policy 回答的是「你有沒有資格碰**這一筆**資料」。
使用者可以改自己的資料但不能改別人的——這種依 record 而異的判斷只有 Policy 做得到。

**權限清單記憶化**
一個請求裡可能檢查十幾次權限,若每次都查 pivot table 就是十幾條 query。
`HasRoles::permissionNames()` 用 `??=` 把結果快取在模型實例上,
並提供 `forgetCachedPermissions()` 在改動角色後手動失效。

### 選型決策 — 為什麼不用 Summernote,改用 TipTap

使用者原本指名 Summernote。它是 jQuery + Bootstrap 時代的編輯器,而本專案是
Vue 3 + Tailwind 4、完全沒有 jQuery 也沒有 Bootstrap。硬接的代價是:
額外引入 jQuery 與整包 Bootstrap CSS(bundle 約 +300KB)、Bootstrap 的
global reset 會跟 Tailwind 打架、且 jQuery 直接操作 DOM 會與 Vue 的
virtual DOM 搶控制權,元件卸載時容易殘留節點。

改用 **TipTap**(ProseMirror 核心):Vue 3 官方支援、無頭設計所以工具列
完全用 Tailwind 自己刻、輸出乾淨 HTML 方便存進資料庫與前台渲染。
使用者要的功能(粗體/標題/清單/連結/圖片上傳/表格)一項不少。

安全性提醒:所見即所得編輯器的產出是使用者可控的 HTML,存進資料庫前
**必須做 HTML 淨化**,否則後台就成了儲存型 XSS 的入口。

### 筆記 2 — 認證系統

**為什麼登入限流用「email + IP」複合金鑰?**
只鎖 IP 的話,同一間公司 / 學校出口的所有人會被一個手殘的同事連坐;
只鎖 email 的話,攻擊者可以拿一個 IP 去噴幾千組帳號而不受限。
複合金鑰讓「針對單一帳號的暴力破解」被擋,又不影響其他人。

**為什麼 Email 驗證路由不要求登入?**
Laravel 預設的 `EmailVerificationRequest` 掛 `auth` 中介層。但驗證信是在
**信箱**裡點開的,很可能開在另一個瀏覽器(手機收信、公司電腦註冊),
那邊沒有 session,使用者就會看到 403 而一頭霧水。
簽名連結本身已經包含 user id + email 的 sha1 且有效期 60 分鐘,
「能拿到這個連結」就等於「能收到這個信箱的信」——這正是驗證要證明的事,
所以拿掉 `auth` 不會降低安全性,只會少一個爛體驗。

**測試踩雷:Sanctum 是看 `Referer` / `Origin`,不是看 host**
`EnsureFrontendRequestsAreStateful` 用請求標頭裡的 Referer 或 Origin 去比對
`config('sanctum.stateful')`,決定要不要掛上 session 中介層。
瀏覽器一定會送這個標頭,但 PHPUnit 的測試客戶端不會,
結果就是所有 `/api` 路由在測試裡沒有 session,
`$request->session()->regenerate()` 直接噴 500 "Session store not set on request"。
解法是在 `tests/TestCase.php` 統一補上 Referer 標頭(見該檔註解)。

**測試踩雷:登出後要斷言 `assertGuest('web')` 而不是 `assertGuest()`**
`auth:sanctum` 中介層驗證成功後會呼叫 `Auth::shouldUse('sanctum')`,
把預設 guard 換掉。而 sanctum 這個 RequestGuard 會把解析出來的 user 快取在記憶體,
所以就算 session 已經被 invalidate,不指定 guard 的 `assertGuest()` 還是會看到已登入。
真正承載登入狀態的是 session,所以要斷言的是 `web` guard。

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
