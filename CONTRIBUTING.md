# Contributing

Thanks for taking a look. Issues and pull requests are both welcome.

## Getting set up

Everything runs in containers, so the setup in the [README](README.md#getting-started) is the whole
of it. Once `docker compose up -d` is running you have a working copy with seeded demo data.

## Before opening a pull request

```bash
docker compose exec php php artisan test      # must be green
docker compose exec php php vendor/bin/pint   # formats PHP; commit what it changes
docker compose exec node npm run build        # must succeed
```

`public/build/` is generated and gitignored — do not commit it.

## What a useful pull request looks like here

**Cover the boundary, not just the happy path.** Most of the interesting tests in this project
assert what is *refused*: a manager cannot promote themselves, an admin cannot delete their own
account, a draft does not leak past a filter, a password does not reach the activity log. If your
change touches authorisation, sanitisation or anything a user could send, the test that matters is
the one that proves the wrong input is rejected.

**Open the browser too.** Two real bugs here shipped past a green test suite and were caught only
by using the application: a login response that omitted an eager-load, and an audit log that
recorded a change nobody made. Tests are necessary and they are not sufficient.

**Say why in the commit message, not what.** The diff already says what changed. What is worth
writing down is the reason, the alternative you rejected, and anything that surprised you. The
[roadmap](docs/ROADMAP.md) is written the same way and is the project's real documentation.

**Keep comments about the reason.** A comment restating the code is noise. A comment explaining a
constraint, a trade-off or a trap is what makes the next change safe.

**Code and code comments are English.** The interface is bilingual (English and Traditional
Chinese) and both locale files must be updated together; a string added to one and not the other
is a bug. The roadmap exists in both languages, `docs/ROADMAP.md` being the primary version.

## Things that need a conversation first

Open an issue before you build these — the design constraints matter more than the code:

- **Raw HTML editing.** Sketched in the roadmap. The rule is that both allow-lists live on the
  server; a front-end mode switch must never be what grants relaxed sanitisation.
- **New upload types.** The allow-list is deliberate and SVG is deliberately absent. Adding a
  format means arguing that the browser cannot be made to execute it.
- **Anything that writes to the activity log over HTTP.** The log has no write route on purpose.

## Reporting a security issue

Please do not open a public issue for a vulnerability. Open a private security advisory on the
repository instead.
