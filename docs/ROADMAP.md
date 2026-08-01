# Laravel Admin Starter — Feature & Tech Tracker

> **繁體中文:[ROADMAP.zh-TW.md](ROADMAP.zh-TW.md)** — this English version is the default; the two are kept in sync.
>
> This document is the project's single source of truth. Each feature updates its own status
> as it lands, and the contents will eventually be distilled into `README.md` (English) and
> `README.zh-TW.md` (Traditional Chinese).
>
> Status markers: `⬜ not started` `🟨 in progress` `✅ done`

---

## How this is built (instead of a fixed 30-day schedule)

No day counts. One feature at a time, in order, and the next one does not start until the
current one is finished:

1. Project setup (Laravel + Docker + Vue 3 + Vite)
2. Authentication (login / register / forgot password / email verification)
3. RBAC (middleware / policy / gate)
4. Dashboard (stat tiles + Chart.js + recent activity)
5. User management CRUD
6. Bilingual UI (vue-i18n, EN / zh-TW)
7. CMS / product module (translated content)
8. Media library
9. Activity log
10. README (English + Traditional Chinese) and open-source docs

Every finished item flips its status to ✅ below and gains a "what was used" list and an
engineering note.

---

## Features and status

### 1. Project setup

- Status: ✅ (2026-08-01, five containers verified end to end)
- Scope: Laravel skeleton, Docker Compose (Nginx + PHP + MySQL + Redis), Vue 3 + Vite front end
- Stack: Laravel 13.23, PHP 8.4, Sanctum 4, Docker Compose, Nginx, MySQL 8.4, Redis 7, Vue 3, Vite 8, Pinia, Tailwind CSS 4
- Delivered:

  - Laravel 13 skeleton with Sanctum in SPA mode (`statefulApi()` registered)
  - Vue 3 SPA shell: `resources/views/app.blade.php` + Vue Router in history mode + Pinia
  - `routes/web.php` catch-all hands everything to the front-end router, excluding `api` / `sanctum` / `storage` / `up`
  - `GET /api/ping` health check, which the landing page actually calls to prove the wiring
  - Docker: `docker-compose.yml`, `docker/php/Dockerfile`, `docker/nginx/default.conf`

- Verified (2026-08-01):

  - `docker compose up -d --build` → las-nginx / las-php / las-mysql / las-redis / las-node all Up
  - `artisan migrate` ran four migrations against the MySQL 8.4 container
  - `GET http://localhost:8080/api/ping` → `{"message":"pong","laravel":"Laravel 13.23.0","php":"8.4.24"}`
  - Vite dev server running inside the container; Blade emits `http://localhost:5173/@vite/client` and HMR works
  - `Cache::put/get` round-trips through Redis (`store=redis`); phpredis is compiled into the image

### 2. Authentication

- Status: ✅ (2026-08-01, 26 feature tests plus an end-to-end run)
- Scope: register, sign in, sign out, forgot password, reset password, email verification
- Stack: Laravel Sanctum (SPA cookie mode), Laravel Notifications, Pinia, Vue Router navigation guards
- API:

  - `POST /api/register` (signs in immediately and sends the verification email)
  - `POST /api/login` (rate limited on a composite email + IP key, five attempts)
  - `POST /api/logout`
  - `POST /api/forgot-password`, `POST /api/reset-password`
  - `GET /api/verify-email/{id}/{hash}` (signed URL), `POST /api/email/verification-notification`
  - `GET /api/user`

- Pages: Login / Register / ForgotPassword / ResetPassword / VerifyEmail / Dashboard
- Verified (2026-08-01):

  - `php artisan test` → 26 passed (69 assertions)
  - End to end: CSRF handshake → register 201 → `/api/user` 200 → logout 200 → login 200 → 401 when signed out
  - Clicking the real verification link wrote `email_verified_at` correctly

### 3. RBAC

- Status: ✅ (2026-08-01, 42 tests passing)
- Scope: admin / manager / user roles, `users.view/create/update/delete` permissions
- Stack: middleware, policies, gates, many-to-many (roles ↔ permissions ↔ users)
- Delivered:

  - `roles` / `permissions` / `permission_role` / `role_user` tables (composite keys, cascade delete)
  - `HasRoles` trait: `hasRole()` / `hasPermission()` / `permissionNames()`, memoised per instance
  - `Gate::before` lets admins through everything (returns `null`, not `false`, so other policies still run)
  - `permission:users.view` route middleware, accepting several permissions as OR
  - `UserPolicy`: you may read and write your own record; nobody may delete themselves
  - Demo accounts `admin@ / manager@ / user@example.com`, password `password`

### 4. Dashboard

- Status: ✅ (2026-08-01, 49 tests passing, checked in a real browser)
- Scope: stat tiles, chart, recent activity list
- Stack: Chart.js, Laravel API Resources
- Delivered:

  - `GET /api/dashboard` (requires `users.view`) returns totals, a 30-day trend, role split and newest accounts
  - Four stat tiles: total / verified / awaiting verification / new this week
  - Daily sign-up line chart (Chart.js, single series, separate light and dark palettes, togglable table view)
  - Role split as a directly-labelled HTML bar list — three or four nominal categories do not need a canvas
  - Someone without `users.view` gets an explanatory panel instead of a broken screen, which also demonstrates that RBAC is doing something

### 5. User management CRUD

- Status: ✅ (2026-08-01, 68 tests passing, exercised in a real browser)
- Scope: search, pagination, filters
- Stack: Eloquent query builder, Vue 3 Composition API, Pinia
- Delivered:

  - `apiResource('users')`, every action authorised through `UserPolicy`
  - Queries: fuzzy name/email search, role filter, verification filter, sorting, pagination
  - Sort columns are an allow-list (`IndexUserRequest::SORTABLE`) so `orderBy` cannot be injected
  - **Privilege-escalation guard**: a manager holds `users.update` and could otherwise promote themselves
  - `/users` page: debounced search, three filters, pagination, create/edit modal, delete confirmation

### 6. Bilingual UI

- Status: ✅ (2026-08-01, 77 tests passing)
- Scope: EN / zh-TW switching, language menu, remembered preference
- Stack: vue-i18n 11, localStorage, a `users.locale` column, Laravel lang files
- Delivered:

  - `App\Support\Locales` is the one list of locales, shared by middleware, validation rules and the front end
  - `SetLocale` middleware picks the response language: account preference > `Accept-Language` > default
  - `PUT /api/user/locale` stores the preference; the front end writes back when you switch
  - `lang/zh-TW/` (auth / passwords / validation / pagination) plus `lang/zh-TW.json`
  - Every front-end string extracted into `resources/js/i18n/locales/{en,zh-TW}.json`

### 7. Product module (website content)

- Status: ✅ (2026-08-01, 101 tests passing, products created through the browser)
- Delivered:

  - `products` + `product_translations`, so adding a language needs no schema change
  - `apiResource('products')` + `POST /api/products/reorder` + cover upload and removal
  - `products.view/create/update/delete`, all four held by managers
  - **HTML sanitisation**: `App\Support\RichText`, an allow-list applied on write
  - TipTap editor (bold / italic / strike / H2–H4 / lists / quote / code / link / image / undo / redo)
  - vuedraggable reordering, with position derived from the array index rather than trusted from the client
  - Tabbed per-language editing, a • marker on languages left blank, slug generated from the name on create

- Open (raised 2026-08-01): a **raw HTML editing mode**, like WordPress's Visual / Text tabs. The
  UI is trivial (swap in a textarea showing `editor.getHTML()`, `setContent()` on the way back);
  the real question is what happens to the sanitiser's allow-list — see the notes below.
- Scope: create / edit / delete products, WYSIWYG rich text, cover images and a media library,
  draft / published, **drag to set the public display order**, translated content
- Stack: products + product_translations (with `sort_order`), **TipTap**, HTML sanitisation,
  file uploads, vuedraggable
- Note: raised by the project owner on 2026-08-01. The originally planned "CMS articles module"
  was folded into this one — the schema is the same and only the wording differs. If a blog is
  genuinely needed later it can be split back out.

### 8. Media library

- Status: ✅ (2026-08-01, 114 tests passing)
- Scope: upload, preview, delete, drag-and-drop upload, copy URL
- Stack: Laravel Filesystem, Vue upload component
- Delivered:

  - `media` table with `path` (randomised storage key) stored separately from `name` (the original filename)
  - `media.view/upload/delete`; **uploaders can always delete their own files**
  - Extension allow-list, **deliberately without SVG** (see the note)
  - The model's `deleting` event removes the file, so no orphans are left on disk
  - Library page: drag-and-drop upload, image preview, copy absolute URL, pagination
  - **The picker is wired into TipTap's image button** — insert from the library rather than pasting a URL

### 9. Activity log

- Status: ✅ (2026-08-02, 132 tests passing, sign-in / sign-out verified in a real browser)
- Scope: sign-in records, CRUD records
- Stack: polymorphic relations (subject_type / subject_id)
- Delivered:

  - `activity_log` table with **`created_at` only** — no `updated_at`
  - `causer_name` / `subject_label` denormalised so entries still read sensibly after their subject is deleted
  - **Morph map**: the database stores `product`, not `App\Models\Product`
  - `LogsActivity` trait on User / Product / Media, listening to model events rather than controllers
  - `RecordAuthenticationActivity` listens to Laravel's `Login` / `Logout` / `Failed`
  - **Passwords are recorded as a field name, never a value**; `locale` and `remember_token` are dropped entirely (see the note)
  - `activity.view` is held by **admins only** — managers do not get it
  - Read-only API (`GET /api/activity` and nothing else), an `/activity` page, and a dashboard panel

### 10. README and open-source docs

- Status: ⬜
- Scope: `README.md` (English), `README.zh-TW.md` (Traditional Chinese), demo accounts, screenshots, contribution guide
- Stack: Markdown, GitHub Actions (optional, CI badge)

### 11. Public website

- Status: ⬜
- Scope: a product list at `/products` and a detail page at `/products/{slug}`, readable without
  signing in, ordered by the `sort_order` the admin sets, published products only
- Stack: public API (`GET /api/public/products`), public Vue Router routes, SEO meta
- Note: added 2026-08-01. The point is to give the admin actions somewhere to *show up* — drag the
  order, edit the copy, and the public site reflects it immediately. That is what makes this a
  system that works rather than a pile of management forms.

---

## Stack overview (updated as the project grows)

| Area | Technology |
| --- | --- |
| Backend | Laravel 13, PHP 8.4 |
| Frontend | Vue 3, Vite, Pinia, Axios, Tailwind CSS |
| Auth | Laravel Sanctum |
| Database | MySQL 8 |
| Cache / queue | Redis |
| Containers | Docker, Docker Compose, Nginx |
| i18n | vue-i18n (front end), Laravel lang files (back end) |
| Charts | Chart.js |
| Branching | GitHub Flow |

---

## Environment decisions

- **2026-08-01**: originally planned for Laravel 11 / PHP 8.3. The current stable release turned
  out to be Laravel 13.x (minimum PHP 8.3), so this moved to **PHP 8.4 + Laravel 13** — a starter
  template is more convincing on a README when it is actually current.
- **2026-08-01**: development happens **entirely in Docker Desktop** rather than a local XAMPP.
  The environment stays clean, it resembles production, and Docker Compose is one of the things
  this starter is meant to demonstrate in the first place.
- **2026-08-01**: Docker's data disk moved from C: to `D:\DockerData` (C: was down to 60 GB). The
  GUI would not launch at the time and the WSL2 backend only exposes "Disk image location" through
  the GUI, so an NTFS junction was used as a stopgap.
- **2026-08-02**: the Docker Desktop GUI failure turned out to be the **install mode** (per-user
  rather than system-wide); reinstalling fixed it. The data disk now uses the supported setting
  pointing at `D:\DockerData\diskimage`, and the junction was removed.
- **2026-08-02**: the admin layout moved from a horizontal top nav to a **left sidebar**, with the
  language switcher and account actions in the top right. A horizontal nav crowds as sections are
  added; a sidebar just gains a row. Narrow screens get a drawer.
- **2026-08-02**: documentation is **English by default** (`docs/ROADMAP.md`) with a Traditional
  Chinese counterpart (`docs/ROADMAP.zh-TW.md`). Code comments stay in English only, deliberately:
  comments have to change with the code they describe, two languages will eventually drift apart,
  and a comment that disagrees with its code is worse than no comment at all.

---

## Engineering notes

> A short "why it was built this way" for each feature. This is the material that is directly
> usable in an interview or a technical blog post.

### Note 1 — Project setup

**Why Sanctum's SPA (cookie) mode rather than tokens?**
The front and back ends share an origin (both served by Nginx on `localhost:8080`), so a session
cookie means no token is stored in the browser and localStorage XSS theft is simply not available
as an attack. The price is configuring `SANCTUM_STATEFUL_DOMAINS` and `SESSION_DOMAIN`, and
turning on `withCredentials` / `withXSRFToken` in axios.

**Why one catch-all in `routes/web.php`?**
Vue Router runs in history mode, so typing `/users/3` directly hits Laravel first. The catch-all
returns the same Blade shell for every non-API path and lets the front-end router decide what to
render; the regex excludes `api|sanctum|storage|up` so the API and the health check are not
swallowed too.

**Why does the node service mount an anonymous volume over `node_modules`?**
The project directory is a Windows bind mount, and the host's `node_modules` holds Windows-native
esbuild/rollup binaries that an Alpine container cannot execute. An anonymous volume gives the
container its own Linux build while the host keeps its copy for the IDE's type hints.

**Why does MySQL need a healthcheck plus `depends_on: condition: service_healthy`?**
There is a gap of several seconds between a MySQL container starting and accepting connections.
Without the healthcheck the first `artisan migrate` fails at random — the single most common
Docker beginner's trap.

### Note 2 — Authentication

**Why is login throttling keyed on email + IP together?**
Keying on IP alone means everyone behind one company or campus gateway is punished for a colleague's
typo. Keying on email alone lets an attacker use a single IP against thousands of accounts freely.
The composite key blocks brute force against one account without affecting anyone else.

**Why does the email verification route not require authentication?**
Laravel's `EmailVerificationRequest` ships with `auth` middleware. But verification links are opened
**from an inbox**, quite possibly in a different browser — registered on a work machine, mail read
on a phone — where there is no session, so the user meets a baffling 403. The signed link already
contains a sha1 of the user id and email and expires in 60 minutes: being able to open that link
*is* being able to receive mail at that address, which is exactly what verification proves. Dropping
`auth` removes a bad experience without weakening anything.

**Test trap: Sanctum looks at `Referer` / `Origin`, not the host**
`EnsureFrontendRequestsAreStateful` matches the Referer or Origin header against
`config('sanctum.stateful')` to decide whether to attach the session middleware. Browsers always
send one; PHPUnit's test client sends none. The result is that no `/api` route has a session under
test and `$request->session()->regenerate()` throws a 500, "Session store not set on request". The
fix is to add the header once in `tests/TestCase.php` (see the comment there).

**Test trap: after signing out, assert `assertGuest('web')`, not `assertGuest()`**
Once `auth:sanctum` succeeds it calls `Auth::shouldUse('sanctum')`, replacing the default guard —
and Sanctum's RequestGuard caches the resolved user in memory. So even after the session is
invalidated, an unqualified `assertGuest()` still sees someone signed in. The session is what
actually carries the login state, so `web` is the guard to assert on.

**A Docker Desktop GUI that will not open is not a broken Docker**
The Docker Desktop window here failed instantly with "Unable to launch Docker Desktop", and the
logs showed both the GPU **and** renderer child processes dying with `0xC0000005` (access
violation). The thing worth internalising: **the GUI is just an Electron front end and the engine
is separate**. `docker desktop start/stop`, `docker compose` and `docker exec` all kept working,
and items 1–8 were built entirely with the GUI broken.

The actual cause was the **install mode** — Docker Desktop had been installed per-user under
`%LOCALAPPDATA%\Programs\DockerDesktop` rather than into `C:\Program Files\Docker\Docker`.
Reinstalling system-wide fixed it outright.

Ruled out along the way, recorded so nobody repeats them: NVIDIA overlay, OBS, the GPU driver,
`AppInit_DLLs`, Exploit Protection, third-party antivirus, Defender's CFA / ASR / Smart App
Control, the Electron cache, and the flags `--disable-gpu`, `--disable-gpu-sandbox` and
`--in-process-gpu`.

Two mistakes of my own from that hunt. The first `--disable-gpu` test was invalid because only the
UI process was killed while `com.docker.backend` stayed alive, so the new instance simply handed
control back to the old one. And at one point I concluded "the install is corrupted" and stopped
there — which was not deep enough. The question worth asking was **where** it was installed, not
whether the installer was intact.

**Moving Docker's data disk to another drive, properly**
While the GUI was broken this was done with an NTFS junction (the `DataFolder` key in
`settings-store.json` is ignored by the WSL2 backend). Once the GUI worked it moved to the
supported setting — Settings → Resources → Disk image location, pointed at `D:\DockerData\diskimage`,
where Docker creates `DockerDesktopWSL` itself. The junction works, but it is a workaround for a
broken GUI, not a recommendation.

**PHP version decision**
Laravel 13 needs PHP 8.3 at minimum and the local XAMPP is 8.2, so PHP 8.4 was installed separately
at `C:\php84`. Note that the Windows system PATH is searched before the user PATH, so plain `php`
is still XAMPP's 8.2 — prefix with `$env:Path = 'C:\php84;' + $env:Path` before running composer or
artisan on the host. (Inside the containers none of this applies.)

### Note 3 — RBAC

**Why does the admin role have no permissions at all in the seeder?**
Because `Gate::before` already lets it through unconditionally. Listing admin's permissions in the
table as well would create a second source of truth: add `products.delete` later, forget to add it
to admin's list, and you get the ghost story where the administrator is the one account that
cannot delete a product. Admin stays an empty set, and permissions describe non-administrators.

**Why does `Gate::before` return `null` rather than `false`?**
`false` is an explicit denial and short-circuits every policy behind it. `null` means "no opinion"
and hands the decision back to the normal flow. Get this wrong and every non-administrator is
denied everything.

**Middleware or policy?**
Route middleware like `permission:users.view` answers "may you touch this feature at all". A policy
answers "may you touch **this record**". A user may edit their own account but not someone else's,
and only a policy can make a decision that varies per record.

**Memoising the permission list**
A single request may check permissions a dozen times, and hitting the pivot table each time is a
dozen queries. `HasRoles::permissionNames()` caches the result on the instance with `??=` and
offers `forgetCachedPermissions()` for after a role change.

### Note 4 — Dashboard and data visualisation

**Trend data has to be zero-filled**
`GROUP BY DATE(created_at)` only returns the days that had sign-ups. Plotted directly, the line
runs straight from 3 July to 28 July and the quiet days in between vanish — which reads as steady
growth. The back end expands all 30 days with `CarbonPeriod` and fills the gaps with zero.

**Not every statistic should be a chart**
The role split has three or four nominal categories, and drawing it on a canvas makes it worse:
labels get clipped and you have to hover to read a number. A directly-labelled HTML bar list makes
every value readable without a tooltip. The line chart genuinely earns its canvas — 30 points where
the shape itself is the information.

**No "bigger is darker" gradient on bars**
That encodes length a second time as colour, wasting the one free channel, and nominal categories
have no inherent order anyway. One series, one colour.

**The palette was computed, not picked**
The dataviz skill's validator was run against this project's actual backgrounds (`#ffffff` light,
`#171717` dark) for lightness range, minimum chroma and 3:1 contrast. Both modes had to pass before
the values went into `charts.js`. The dark palette is a set chosen for a dark background, not the
light one auto-inverted.

**Some bugs only appear when you open the thing**
All 49 tests passed and the build was clean, but signing in as a manager showed no statistics at
all. `POST /api/login` returned a `UserResource` without eager-loading `roles`, and `whenLoaded`
emits conditionally — so the front end received a user with no permissions and only recovered after
a refresh went through `/api/user`. A hole like that, green tests over a broken screen, is only
visible in a browser. Fixed by loading the relation in the login and register responses, with a
regression test.

### Note 5 — User management CRUD

**The important one: privilege escalation**
A manager holds `users.update`. Left alone, they can `PATCH /api/users/{their own id}` with
`roles: ["admin"]`, promote themselves in one request, and from then on `Gate::before` waves them
through everything. However elegant the rest of the RBAC is, missing this makes it decorative.
The rule — **only a current admin may grant or revoke the admin role** — lives in
`withValidator()` on both the store and update requests, so neither path is open.

**`Gate::before`'s second trap: an admin could delete themselves**
`UserPolicy::delete()` says nobody may delete their own account, but `Gate::before` waved admins
past it, so the rule never ran for the one account it mattered most for. The fix is to make the
bypass step aside when the target is the actor and let the policy decide. This one only surfaced
while writing tests; read on its own, each half looked right.

**Authorisation must run before validation**
Laravel 11+ removed controller-constructor middleware, so `authorizeResource()` is gone; the
replacement is declaring `can:` middleware through the `HasMiddleware` interface. That is not just
a different spelling — middleware runs **before** FormRequest validation, so someone without
permission gets a clean 403 instead of a 422 listing field errors, and a 422 would have described
the shape of data they were never allowed to see.

**Sort columns need an allow-list**
`orderBy($request->input('sort'))` splices a user string straight into SQL. `IndexUserRequest`
pins it to three columns with `Rule::in(self::SORTABLE)`.

**The Windows + Docker Vite trap**
Router changes kept 404ing because Vite inside the container was serving stale modules. A Windows
bind mount **does not deliver inotify events into a Linux container**, so Vite's watcher never
fires and its transform cache never invalidates — which looks exactly like "saving does nothing".
The fix is `server.watch.usePolling: true`. Anyone running Vite in Docker on Windows or macOS hits
this, so it belongs in the README.

### Note 6 — Bilingual UI

**The back end has to switch too, or the illusion breaks at the first validation error**
However complete the front-end translation is, one failed form submit returns a 422 in English and
the Chinese interface falls apart. So axios sends `Accept-Language` on every request and the
`SetLocale` middleware picks the response language.

**Priority: account preference > browser header > default**
What is stored on the account was chosen deliberately; the browser header is a guess, so the
former wins. `users.locale` is nullable rather than defaulted — null means "never chose", which is
what makes falling back to the browser possible instead of quietly pinning everyone to English.

**A language chosen before signing in must not be lost by signing in**
While signed out the choice lives in localStorage. On sign-in, if the account has no preference,
that local choice is pushed up and saved; if the account does have one, the account wins. So the
"pick a language, then log in" order does not eat the choice.

**Do not be clever and map `zh-CN` to `zh-TW`**
Simplified and Traditional are different writing systems. `Locales::fromAcceptLanguage()` only
matches whole tags and falls back to the default otherwise — better English than the wrong script.

**The Windows + Docker Vite trap, second layer**
Adding `usePolling: true` fixed HMR and then hung the dev server outright, because polling walks
**the entire project directory** including `vendor/` (tens of thousands of files) and `.git`, which
on a Windows bind mount is suicide. `watch.ignored` has to exclude `vendor` / `node_modules` /
`storage` / `.git` at the same time. Turning polling on and turning it on correctly are two
different things.

### Note 7 — Product module

**Sanitise on write, not on output**
Both stop XSS, but sanitising on write means the database only ever holds safe HTML — no template
that forgets to escape can resurrect the hole later. Sanitising on output means every render site
has to remember, and missing one is enough. The cost is that the original input is unrecoverable,
but WYSIWYG content has no reason to preserve malicious markup in the first place.

**The editor's allow-list and the server's must agree**
`RichText::ALLOWED` permits h2–h4, so TipTap's `heading.levels` is `[2,3,4]`. The allow-list has no
`<u>`, so StarterKit's `underline` is switched off. When they disagree, someone formats a document,
presses save, and the formatting silently disappears — a bug nobody reports, they just conclude the
editor is bad.

**Positions are derived on the server from the array index**
The client sends the new order of ids, not a position for each id. The server writes them with
`foreach ($ids as $position => $id)`, so the result is necessarily 0, 1, 2, … with no gaps or
duplicates, whatever the client sends.

**No dragging while a filter is on**
Dragging the third row to the top in a list showing only published products would write positions
that ignore every hidden draft, and the real order would scramble. Dragging is therefore disabled
whenever a filter is active, with a line saying why.

**OR in a query has to be grouped**
Without wrapping `whereHas(...)->orWhere(...)` in a `where(fn ...)`, precedence turns it into
`status = 'published' AND EXISTS(name) OR slug LIKE ...` and drafts slip past the status filter.
There is a regression test specifically holding this line.

**How raw HTML editing could work without dismantling the sanitiser**
The request is for WordPress's "just type HTML" mode. Switching the UI is easy; the hard part is
that if a hand-written `<iframe>` is stripped on save the feature looks broken, while opening the
allow-list to accommodate it tears down the XSS defence that was just built.

WordPress solves this with **capabilities**: only roles holding `unfiltered_html` (administrators
only, on a single site) may submit unfiltered HTML. This project's RBAC can express that directly —
add a `products.publish_html` permission, apply a relaxed allow-list (adding `iframe`, `table`,
`class`) to whoever holds it, and keep the strict one for everyone else. The point is that **both
allow-lists are defined server-side**; the front-end mode switch is presentation and must never
become "the client says it is in advanced mode, so the server relaxes".

**`#[Fillable]` silently swallows foreign keys**
`ProductTranslation::create(['product_id' => ...])` does not complain when `product_id` is absent
from the fillable list. It drops the value and then explodes on a NOT NULL constraint in the
database. Use `$product->translations()->create([...])` so Eloquent supplies the key itself.

### Note 8 — Media library

**Why SVG uploads are not accepted**
`svg` was in the allow-list in a first draft, and halfway through writing it the contradiction
became obvious: SVG is XML, it can carry a `<script>` tag, and served from our own origin the
browser executes it — defending product content against stored XSS with one hand while opening a
back door with the other. Supporting it safely needs either sanitising the XML or serving uploads
from a separate origin; until one of those exists, PNG and WebP cover the same ground. A test now
pins the rejection.

**Allow-list, not block-list**
Enumerating the dangerous extensions is a game you lose the first time someone finds one you
forgot. Enumerating the permitted ones means the worst case is a missing feature, not a hole.

**`path` and `name` must be separate**
`path` is Laravel's randomised storage key; `name` is the filename the user recognises. Two people
uploading `logo.png` then get two files instead of the second silently replacing the first — a kind
of data loss that is very hard to trace, because nothing errors, it just quietly swaps someone
else's file.

**Files and rows are deleted together**
The delete lives in the model's `deleting` event, not the controller, so whichever path the delete
came through, the bytes on disk and the row in the database cannot drift apart.

**Uploaders can always delete their own files**
`MediaPolicy::delete()` checks `media.delete` but also lets the uploader through. Having to ask
someone else to remove a file you just uploaded by mistake is a silly experience.

**Product covers and the library stay separate on purpose**
A cover is an asset owned by its product and deleted with it; the library is shared material for
content. Unifying them would mean deleting one library image could leave a product showing a broken
cover.

### Note 9 — Activity log

**An entry has to be readable after its subject is gone**
The first version stored only `causer_id` and `subject_id`, two foreign keys. The problem is that
deletion is the single event most worth recording, and once it has happened the row it refers to no
longer exists — the relations resolve to null and the screen can only say "someone deleted
something", which says nothing. So `causer_name` and `subject_label` are stored alongside.
Denormalisation is usually a smell, but an audit entry is a **statement about a moment that has
passed**; it was never supposed to change when today's data does. This redundancy is the point.

**No `updated_at`**
An audit entry is written once and never touched. Keeping an `updated_at` column advertises that
entries can be rewritten, and a log an administrator can edit proves nothing. The API is `GET` only
for the same reason: there is no write route that a later change could forget to protect.

**Morph map: keep the namespace out of the database**
By default Laravel writes the full class name `App\Models\Product` into polymorphic columns, which
quietly makes the PHP namespace part of the database schema — rename or move the class later and
the history already written breaks. `Relation::enforceMorphMap()` stores `product` instead, and as
a bonus it refuses to guess, so a new loggable model has to be declared rather than leaking its
namespace the first time it is used.

**Trap: `$touches` does not fire model events**
A product's body lives in `product_translations`, so editing only the body changes no column on
`products`, Eloquent fires no `updated` event, and the whole edit goes unrecorded. The first idea
was `protected $touches = ['product']` on the child. A test said no: Laravel's `touchOwners()` goes
through the query builder's `rawUpdate()` and **fires nothing at all**. The controller records it
explicitly instead, and the recorder suppresses it when the product row already logged the real
diff in the same request. `$touches` stayed, because it keeps `products.updated_at` honest — but
that is all it does.

**Trap: the listener was registered twice**
Registering the three authentication listeners by hand with `Event::listen()` in
`AppServiceProvider` wrote two rows per sign-in. Laravel 11+ scans `app/Listeners` and registers
any method starting with `handle` whose parameter type-hints an event — and `handleLogin(Login
$event)` matches exactly. The manual registration was a duplicate subscription. Removed it and let
discovery do the work.

**Passwords: record the field, not the value**
"Someone changed this account's password" is one of the reasons an audit log exists, so it has to
be recorded. The hash must not be: it is offline cracking material sitting in a table
administrators read, and it outlives the password itself once the user rotates it. The field name
is kept and the value replaced with `[redacted]` — on both sides, or the old value leaks what the
new one hides.

**Do not record changes nobody decided to make**
The very first look in a browser showed "Admin User edited Admin User / Changed: locale". The
front end writes the browser's language onto the account at first sign-in; `remember_token`
rotates whenever "remember me" is ticked. Neither is something a person *decided* to do, yet each
would leave an entry after almost every sign-in and bury the real records. The usual way an audit
log dies is not missing entries — it is noise nobody reads. Both attributes are dropped entirely,
and an update whose every changed attribute was dropped writes nothing at all.
None of the 132 tests would have caught this; it came from opening the browser, exactly as in
item 4.

**Why managers do not get `activity.view`**
Managers hold nearly every other permission; this one is withheld deliberately. One purpose of the
log is to show what a manager did with those content permissions, and handing them the log too lets
the watched pick their own watchers.

**Ordering needs a tiebreak**
Entries written inside one request share a `created_at` to the second. Sorting on time alone means
"most recent" can differ between two identical queries. `scopeLatestFirst()` adds a descending `id`
so the sequence is stable.

**The recorder is a container singleton, not a static**
"What has already been recorded this request" has to live and die with the request. A static
property outlives it — and since every test shares one PHP process, that state leaks from one test
into the next. Registering it as a singleton rebuilds it naturally.

### Decision — why TipTap instead of Summernote

Summernote was the original request. It belongs to the jQuery + Bootstrap era, and this project is
Vue 3 + Tailwind 4 with neither of those. Forcing it in would mean pulling in jQuery and a full
Bootstrap stylesheet (roughly +300 KB), letting Bootstrap's global reset fight Tailwind, and
letting jQuery manipulate the DOM directly in competition with Vue's virtual DOM — which tends to
leave stray nodes behind on unmount.

**TipTap** (built on ProseMirror) replaces it: first-class Vue 3 support, headless so the toolbar
is plain Tailwind, and clean HTML output that is straightforward to store and render. Every feature
that was actually wanted — bold, headings, lists, links, image upload, tables — is there.

A security reminder that applies to any WYSIWYG: its output is user-controlled HTML, and it
**must** be sanitised before it reaches the database, or the admin panel becomes the entry point
for stored XSS.
