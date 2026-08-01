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

- 狀態: ✅(2026-08-01 完成,測試累計 49 passed,並用瀏覽器實際檢視)
- 內容: 統計卡片、圖表、最近活動列表
- 技術: Chart.js、Laravel API Resource
- 實作:

  - `GET /api/dashboard`(需 `users.view` 權限)回傳統計、30 天趨勢、角色分佈、最新註冊
  - 四張統計卡片:總人數 / 已驗證 / 待驗證 / 本週新增
  - 每日註冊趨勢折線圖(Chart.js,單一數列、明暗雙色票、可切換表格檢視)
  - 角色分佈用直接標註的 HTML 長條列(3–4 個名目類別不需要 canvas)
  - 沒有 `users.view` 的使用者看到說明面板而非壞掉的畫面 —— 順便展示 RBAC 有在運作

### 5. 使用者管理 CRUD

- 狀態: ✅(2026-08-01 完成,測試累計 68 passed,並用瀏覽器實際操作過)
- 內容: 搜尋、分頁、篩選
- 技術: Eloquent Query Builder、Vue 3 Composition API、Pinia
- 實作:

  - `apiResource('users')` 五條路由,授權全部走 `UserPolicy`
  - 查詢:姓名/Email 模糊搜尋、角色篩選、驗證狀態篩選、排序、分頁
  - 排序欄位白名單(`IndexUserRequest::SORTABLE`),避免 `orderBy` 注入
  - **提權防護**:manager 有 `users.update`,若不擋就能把自己升成 admin
  - 前端 `/users` 頁:防抖搜尋、三個篩選器、分頁、新增/編輯 Modal、刪除確認

### 6. 雙語系(UI)

- 狀態: ✅(2026-08-01 完成,測試累計 77 passed)
- 內容: EN / 繁中切換、Navbar 語言選單、記憶語言偏好
- 技術: vue-i18n 11、localStorage、`users.locale` 欄位同步、Laravel lang 檔
- 實作:

  - `App\Support\Locales` 單一語系清單,中介層 / 驗證規則 / 前端共用
  - `SetLocale` 中介層決定 API 回應語言:帳號偏好 > `Accept-Language` > 預設
  - `PUT /api/user/locale` 儲存偏好;前端切換時同步寫回帳號
  - `lang/zh-TW/`(auth / passwords / validation / pagination)+ `lang/zh-TW.json`
  - 前端所有畫面字串抽成 `resources/js/i18n/locales/{en,zh-TW}.json`

### 7. 產品模組(官網內容管理)

- 狀態: ✅(2026-08-01 完成,測試累計 101 passed,並在瀏覽器實際建立產品驗證)
- 實作:

  - `products` + `product_translations` 兩張表,翻譯分表所以新增語言不用改結構
  - `apiResource('products')` + `POST /api/products/reorder` + 封面圖上傳/移除
  - `products.view/create/update/delete` 四個權限,manager 全部擁有
  - **HTML 淨化**:`App\Support\RichText` 白名單機制,寫入時淨化
  - TipTap 編輯器(粗體/斜體/刪除線/H2–H4/清單/引用/程式碼/連結/圖片/復原重做)
  - vuedraggable 拖曳排序,位置由陣列索引推導而非信任前端
  - 語言分頁式編輯,未填寫的語言有 • 標記;建立時自動從名稱產生 slug

- 待辦(2026-08-01 使用者提出):**原始 HTML 編輯模式**,類似 WordPress 的
  「視覺 / 文字」雙檢視。UI 面很單純(切換成 textarea 顯示 `editor.getHTML()`,
  切回時 `setContent()`),重點在於淨化白名單要怎麼處理 —— 見下方筆記。
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

- 狀態: ✅(2026-08-01 完成,測試累計 114 passed)
- 內容: 上傳、預覽、刪除、拖曳上傳、複製網址
- 技術: Laravel Filesystem、Vue 上傳元件
- 實作:

  - `media` 資料表,`path`(隨機儲存鍵)與 `name`(顯示用原始檔名)分開存
  - `media.view/upload/delete` 三個權限;**上傳者永遠能刪自己的檔案**
  - 副檔名白名單,**刻意不含 SVG**(理由見筆記)
  - 刪除記錄時由模型的 `deleting` 事件一併刪檔,不會留下孤兒檔案
  - 媒體庫頁面:拖曳上傳、圖片預覽、複製絕對網址、分頁
  - **選圖器串進 TipTap 的圖片按鈕** —— 插圖直接從媒體庫選,不再手貼網址

### 9. Activity Log

- 狀態: ✅(2026-08-02 完成,測試累計 132 passed,並在瀏覽器實際登入 / 登出驗證)
- 內容: 登入紀錄、CRUD 操作紀錄
- 技術: Polymorphic 關聯(subject_type / subject_id)
- 實作:

  - `activity_log` 資料表,**只有 `created_at`**,沒有 `updated_at`
  - `causer_name` / `subject_label` 兩個冗餘欄位,讓紀錄在對象被刪除後仍然讀得懂
  - **Morph map**:資料庫存 `product` 而不是 `App\Models\Product`
  - `LogsActivity` trait 掛在 User / Product / Media,聽模型事件而非控制器
  - `RecordAuthenticationActivity` 聽 Laravel 的 `Login` / `Logout` / `Failed`
  - **密碼記欄位不記值**;`locale`、`remember_token` 整個略過(理由見筆記)
  - `activity.view` 權限**只有 admin 有**,manager 拿不到
  - 唯讀 API(只有 `GET /api/activity`)+ `/activity` 頁面 + 儀表板「最近的操作」面板

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

### 筆記 4 — Dashboard 與資料視覺化

**趨勢資料一定要補零**
`GROUP BY DATE(created_at)` 只會回傳「有註冊的那幾天」。直接畫上去的話,
折線會從 7/3 一路直接連到 7/28,讓中間那些沒人註冊的日子憑空消失,
視覺上會變成「持續成長」。後端用 `CarbonPeriod` 把 30 天全部展開並補 0。

**不是每個統計都該畫成圖**
「角色分佈」只有 3–4 個名目類別,用 canvas 畫長條圖反而更差:
標籤會被裁切、要 hover 才看得到數字。改用直接標註數值的 HTML 長條列,
每個值都直接可讀,也不需要 tooltip。折線圖則是真的需要 —— 30 個時間點,
形狀本身就是資訊。

**長條圖不要用「越大越深」的漸層**
那是把長度這個資訊用顏色再編碼一次,浪費掉唯一的自由通道,
而且名目類別本來就沒有大小順序。一組數列就用同一個顏色。

**色票是算出來的,不是挑出來的**
用 dataviz skill 的驗證腳本對本專案實際的背景色(亮 `#ffffff` / 暗 `#171717`)
跑過亮度區間、彩度下限與 3:1 對比,兩個模式都 PASS 才寫進 `charts.js`。
暗色模式的顏色是「為暗背景重新選過的一組」,不是把亮色模式自動反轉。

**實際打開來看,才抓得到這種 bug**
測試 49 個全過、build 也過,但用瀏覽器登入 manager 後統計整個不見。
原因是 `POST /api/login` 回傳的 UserResource 沒有 eager-load `roles`,
而 `whenLoaded` 是條件輸出 —— 前端拿到的 user 沒有 permissions,
要等重新整理走 `/api/user` 才正常。這種「單元測試全綠但實際壞掉」的洞,
只有真的把畫面打開才看得到。修法是在登入/註冊回應一併 load,並補上測試。

### 筆記 5 — 使用者管理 CRUD

**最重要的一條:提權防護**
manager 擁有 `users.update`。如果不特別處理,他可以送出
`PATCH /api/users/{自己的 id}` 帶 `roles: ["admin"]`,一秒把自己升成管理員,
接著 `Gate::before` 就會放行他做任何事。RBAC 做得再漂亮,漏掉這一刀就等於沒做。
規則:**只有現任 admin 能授予或收回 admin 角色**,寫在 Store/UpdateUserRequest 的
`withValidator()` 裡,新增與修改兩條路徑都擋。

**`Gate::before` 的第二個坑:admin 可以刪自己**
`UserPolicy::delete()` 寫了「任何人都不能刪除自己」,但 `Gate::before` 讓 admin
無條件通過,那條規則對 admin 根本沒執行到 —— 而 admin 正是最不該被刪掉的帳號。
修法是讓 bypass 在「操作對象就是操作者本人」時讓位,交還給 policy 判斷。
這個 bug 是寫測試時才浮出來的,單看程式碼兩邊都「看起來對」。

**授權要跑在驗證之前**
Laravel 11+ 拿掉了控制器建構子的 middleware,`authorizeResource()` 不能用了,
改用 `HasMiddleware` 介面宣告 `can:` 中介層。這不只是換寫法 —— 中介層跑在
FormRequest 驗證**之前**,所以沒權限的人拿到的是乾脆的 403,
而不是一串「你這個欄位格式不對」的 422,後者等於洩漏了他不該碰的資料結構。

**排序欄位一定要白名單**
`orderBy($request->input('sort'))` 會把字串直接串進 SQL。
`IndexUserRequest` 用 `Rule::in(self::SORTABLE)` 限死三個欄位。

**Windows + Docker 的 Vite 陷阱**
改了 router 卻一直 404,查出來是容器裡的 Vite serve 著舊模組。
Windows 主機的 bind mount **不會把 inotify 事件傳進 Linux 容器**,
Vite 的檔案監看器從來沒被觸發,轉譯快取也永遠不失效 ——
表現就是「存檔後畫面完全沒反應」。解法是 `server.watch.usePolling: true`。
任何在 Windows/macOS 上用 Docker 跑 Vite 的人都會遇到,值得寫進 README。

### 筆記 6 — 雙語系

**後端也要跟著切語言,不然體驗會斷在驗證錯誤**
前端翻得再完整,只要表單一送出失敗,Laravel 回的 422 訊息還是英文,
整個中文介面瞬間破功。所以 axios 每次請求都帶 `Accept-Language`,
後端用 `SetLocale` 中介層決定回應語言。

**優先序:帳號偏好 > 瀏覽器標頭 > 預設**
帳號上存的是使用者「刻意選過」的,瀏覽器標頭只是猜測,所以前者優先。
`users.locale` 設成 nullable 而非給預設值 —— null 代表「從沒選過」,
這樣才有機會退回去用瀏覽器語言,而不是把所有人默默釘在英文。

**訪客先選的語言,登入後不能弄丟**
沒登入時語言存在 localStorage。登入後若帳號還沒有偏好,就把 localStorage
那個推上去存起來;若帳號已有偏好則以帳號為準。這樣「選了語言 → 才去登入」
的順序不會把選擇吃掉。

**`zh-CN` 不要自作聰明對應到 `zh-TW`**
簡體與繁體是不同書寫系統。`Locales::fromAcceptLanguage()` 只比對完整標籤,
比對不到就退回預設,寧可給英文也不要給錯的字體。

**Windows + Docker 的 Vite polling 第二層陷阱**
上一節加了 `usePolling: true` 解決 HMR 失效,結果 dev server 直接卡死 ——
因為 polling 會去輪詢**整個專案目錄**,包含 `vendor/`(上萬個檔案)和 `.git`,
在 Windows bind mount 上等於自殺。必須同時設 `watch.ignored` 把
`vendor` / `node_modules` / `storage` / `.git` 排除掉。
「開了 polling」和「開對 polling」是兩件事。

### 筆記 7 — 產品模組

**淨化要做在「寫入時」而不是「輸出時」**
兩種做法都能擋 XSS,但寫入時淨化讓資料庫裡永遠只有安全的 HTML ——
之後任何一個忘記轉義的模板都復活不了那個漏洞。輸出時淨化則是每個渲染點
都要記得做,漏一個就破功。代價是原始輸入不可回復,但所見即所得的內容
本來就沒有保留惡意標記的理由。

**編輯器白名單與後端白名單必須對齊**
`RichText::ALLOWED` 允許 h2–h4,所以 TipTap 的 `heading.levels` 也設 `[2,3,4]`;
白名單沒有 `<u>`,所以 StarterKit 的 `underline` 直接關掉。
不對齊的後果是使用者辛苦排版完、按下儲存,格式無聲無息消失 ——
這種 bug 使用者不會回報,只會覺得「這個編輯器很爛」。

**排序位置由後端從陣列索引推導**
前端送的是「新的 id 順序」,不是「每個 id 的新位置」。
後端用 `foreach ($ids as $position => $id)` 寫入,所以結果必然是
0,1,2,… 連續無重複,前端再怎麼送都不可能寫出有洞的序列。

**篩選中不允許拖曳**
在「只顯示已發佈」的畫面裡把第 3 筆拖到第 1 筆,寫進去的位置會忽略所有
被隱藏的草稿,實際順序就亂了。所以有篩選條件時直接停用拖曳並說明原因。

**查詢的 OR 一定要分組**
`whereHas(...)->orWhere(...)` 不包在 `where(fn ...)` 裡的話,
`status = 'published' AND EXISTS(name) OR slug LIKE ...` 的優先序會讓草稿
從 status 篩選旁邊溜過去。已寫了一個專門的回歸測試守住這條。

**原始 HTML 編輯要怎麼做才不會把淨化機制廢掉**
使用者想要 WordPress 那種「可以直接打 HTML」的模式。切換 UI 很簡單,
難的是:若手打的 `<iframe>` 存檔後被剝掉,使用者會覺得功能是壞的;
但若為此把白名單開放,等於把好不容易築起的 XSS 防線拆掉。

WordPress 的解法是**權限分級**:只有具備 `unfiltered_html` 能力的角色
(單站台預設僅管理員)能送出未過濾的 HTML。我們的 RBAC 可以直接表達 ——
新增 `products.publish_html` 權限,有此權限者套用寬鬆白名單
(加開 `iframe` / `table` / `class` 等),沒有的人維持現行嚴格版。
關鍵是**兩份白名單都在後端定義**,前端的模式切換只是 UI,
絕不能變成「前端說我是進階模式,後端就放行」。

**`#[Fillable]` 會靜靜吃掉外鍵**
`ProductTranslation::create(['product_id' => ...])` 在 product_id 不在
fillable 名單時不會報錯,只會把它丟掉,然後在資料庫層炸 NOT NULL。
要用 `$product->translations()->create([...])` 讓 Eloquent 自己帶外鍵。

### 筆記 8 — 檔案管理

**為什麼不接受 SVG 上傳**
一開始我把 `svg` 放進白名單,寫到一半才意識到這是自打嘴巴:
SVG 是 XML,可以內嵌 `<script>`,而從同源提供時瀏覽器會當成文件執行 ——
等於一邊在產品內容嚴防 XSS,一邊從檔案上傳開了一道後門。
要安全支援 SVG 得先淨化 XML,或把上傳檔案放到獨立網域提供;
在那之前 PNG / WebP 覆蓋了同樣的需求。已寫測試釘住這條。

**白名單而不是黑名單**
列舉「危險的副檔名」這場仗,在別人找到你漏掉的那一個時就輸了。
只列舉允許的類型,漏掉的頂多是功能缺失,不會是漏洞。

**`path` 與 `name` 要分開**
`path` 用 Laravel 產生的隨機儲存鍵,`name` 存使用者認得的原始檔名。
兩個人各上傳一個 `logo.png` 才不會後者蓋掉前者 —— 這種資料遺失很難查,
因為系統不會報錯,只會安靜地換掉別人的檔案。

**檔案與記錄要綁在一起刪**
刪檔寫在模型的 `deleting` 事件裡,而不是控制器。這樣不論從哪條路徑刪除,
磁碟上的位元組和資料庫的列都不會各走各的。

**上傳者永遠能刪自己的檔案**
`MediaPolicy::delete()` 除了看 `media.delete` 權限,也放行檔案的上傳者。
傳錯檔案卻要去求別人幫忙刪,是很蠢的體驗。

**產品封面與媒體庫刻意不共用**
封面是產品自己的資產,隨產品刪除;媒體庫是內容用的共享素材。
硬要統一的話,刪掉一張媒體就會讓某個產品的封面變成破圖。

### 筆記 9 — Activity Log

**紀錄要在對象消失之後還讀得懂**
第一版只存 `causer_id` 與 `subject_id`,兩個外鍵。問題是刪除事件正是最值得
記錄的事件,而它記完之後那筆資料就沒了 —— 關聯查回來是 null,
畫面只能顯示「某人刪除了某筆資料」,等於什麼都沒說。
所以另外存 `causer_name` 與 `subject_label` 兩個冗餘欄位。
一般情況下反正規化是壞味道,但稽核紀錄是**對過去某個瞬間的陳述**,
它本來就不該跟著現在的資料一起變 —— 這裡的冗餘是刻意的。

**沒有 `updated_at`**
稽核紀錄寫完就不該再動。留一個 `updated_at` 等於在告訴讀者「這裡可以改」,
而一份管理員能編輯的紀錄,證明不了任何事。API 也只有 `GET`,
沒有寫入路由可以在未來某次改動中被忘記加上保護。

**Morph map:不要把 namespace 寫進資料庫**
Laravel 預設在多型欄位存完整類別名 `App\Models\Product`。這等於讓 PHP 的
namespace 變成資料庫 schema 的一部分 —— 之後想改名或搬目錄,
已經寫下的歷史就壞了。改用 `Relation::enforceMorphMap()` 存 `product`,
順便強制新的可記錄模型要先在這裡登記,而不是第一次用到時就把 namespace 漏出去。

**踩雷:`$touches` 不會觸發模型事件**
產品的內文放在 `product_translations`,所以「只改內文」不會動到 `products`
的任何欄位,Eloquent 也就不會發 `updated` 事件,整筆編輯不會被記錄。
我第一個想法是在子模型加 `protected $touches = ['product']`,想讓它去戳父層。
結果測試直接告訴我沒用:Laravel 的 `touchOwners()` 走的是 query builder 的
`rawUpdate()`,**完全不發模型事件**。
最後改成在控制器明確補一筆,並在 recorder 用「同一個請求內同一筆資料的
同一種事件只記一次」的規則擋掉重複。
`$touches` 還是留著,因為它讓 `products.updated_at` 誠實 —— 但它的用途僅止於此。

**踩雷:listener 被註冊了兩次**
我在 `AppServiceProvider` 用 `Event::listen()` 明確註冊了三個認證事件的
listener,結果每次登入都寫兩筆。原因是 Laravel 11+ 會自動掃描 `app/Listeners`,
把任何 `handle*` 開頭、參數有 type-hint 事件的方法都註冊起來 ——
我寫的 `handleLogin(Login $event)` 剛好完全符合。手動註冊反而變成重複訂閱。
拿掉手動註冊,靠框架的自動發現就好。

**密碼:記欄位,不記值**
「有人改了這個帳號的密碼」是稽核紀錄存在的理由之一,一定要記。
但雜湊值不能記 —— 那是一份放在管理員看得到的表裡的離線破解素材,
而且會活得比密碼本身更久(使用者之後換了密碼,舊雜湊還躺在紀錄裡)。
作法是保留欄位名、把值換成 `[redacted]`,新舊值都要換,
不然「舊值」就洩漏了「新值」想藏的東西。

**沒人決定過的變更不要記**
上線後第一次用瀏覽器點開,馬上看到「Admin User 編輯了 Admin User /
異動欄位:locale」。原因是前端在首次登入時,會把瀏覽器的語言寫回帳號。
`remember_token` 也一樣,勾了「記住我」就會換一次。
這兩個都不是任何人「決定」要做的事,卻會在幾乎每次登入後各留一筆,
真正的操作紀錄就被淹掉了。稽核紀錄最常見的死法不是漏記,是雜訊太多沒人看。
所以這兩個欄位整個略過,而且當一次更新裡所有異動欄位都被略過時,直接不寫這筆。
—— 這個問題 132 個測試沒有一個抓得到,是打開瀏覽器看到的。跟第 4 項一樣的教訓。

**為什麼 manager 沒有 `activity.view`**
其他權限 manager 幾乎都有,這個刻意沒給。這份紀錄的用途之一,
就是看 manager 拿他那些內容權限做了什麼;把紀錄也交給他,
等於讓被監督的人自己挑監督者。

**排序要有 tiebreak**
同一個請求裡寫進去的多筆紀錄,`created_at` 會一模一樣。
只用時間排序的話,「最新」在每次查詢之間可能不一樣。
`scopeLatestFirst()` 補上 `id` 遞減,順序才穩定。

**recorder 用容器單例,不用 static**
「同一請求內已經記過什麼」這個狀態要跟著請求生滅。
用 static 屬性會活得比請求久 —— 測試全部跑在同一個 PHP 行程裡,
狀態會從一個測試漏到下一個。註冊成 singleton 就自然隨請求重建。

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
