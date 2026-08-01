# laravel-admin-starter

A professional open-source Laravel + Vue 3 admin starter template with RBAC, i18n (EN/繁中), and CMS module.

> 🚧 **Work in progress.** See [docs/ROADMAP.zh-TW.md](docs/ROADMAP.zh-TW.md) for the feature
> tracker. This README will be rewritten (with a `README.zh-TW.md` companion) once the
> feature set is complete.

## Stack

| Layer | Technology |
| --- | --- |
| Backend | Laravel 13, PHP 8.4 |
| Auth | Laravel Sanctum (SPA / cookie mode) |
| Frontend | Vue 3, Vue Router, Pinia, Vite 8, Tailwind CSS 4 |
| Database | MySQL 8.4 |
| Cache / Queue | Redis 7 |
| Web server | Nginx |
| Containers | Docker Compose |

## Getting started

Requires [Docker Desktop](https://www.docker.com/products/docker-desktop/). Nothing else has
to be installed on the host — PHP, Node, MySQL and Redis all run in containers.

```bash
git clone https://github.com/<your-account>/laravel-admin-starter.git
cd laravel-admin-starter

cp .env.example .env

docker compose up -d --build

docker compose exec php php artisan key:generate
docker compose exec php php artisan migrate --seed
```

### Demo accounts

Seeded by `php artisan db:seed`. All three use the password `password`.

| Email | Role | Can do |
| --- | --- | --- |
| `admin@example.com` | Administrator | Everything (bypasses all gates) |
| `manager@example.com` | Manager | View, create and update users |
| `user@example.com` | User | Only their own profile |

| Service | URL |
| --- | --- |
| Application | <http://localhost:8080> |
| Vite dev server | <http://localhost:5173> |
| MySQL | `localhost:3306` (`laravel` / `secret`) |
| Redis | `localhost:6379` |

Change the published ports with `APP_PORT`, `VITE_PORT`, `FORWARD_DB_PORT` and
`FORWARD_REDIS_PORT` in `.env` if any of them are already taken.

### Common commands

```bash
docker compose exec php php artisan migrate:fresh --seed   # reset the database
docker compose exec php php artisan test                   # run the test suite
docker compose exec php php vendor/bin/pint                # format PHP
docker compose logs -f node                                # follow the Vite dev server
docker compose down -v                                     # stop and drop volumes
```

## License

[MIT](LICENSE)
