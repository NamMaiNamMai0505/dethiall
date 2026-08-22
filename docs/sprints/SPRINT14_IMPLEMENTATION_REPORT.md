# Sprint 14 implementation report

**Completed locally:** 2026-07-25
**Production rollout:** not executed by this implementation session

## Dependency security

- Removed the stale Composer audit ignore.
- Updated dependencies within existing supported major constraints.
- Laravel Framework: `12.38.1` → `12.64.0`.
- PhpSpreadsheet: upgraded to the patched `1.30.6`.
- PHPUnit: `11.5.44` → `11.5.56`.
- `composer.lock` remains versioned and is consistent with `composer.json`.
- `composer audit --locked`: no advisories.
- `npm audit --omit=dev`: 0 vulnerabilities.

No Laravel 13 or other planned major application-framework upgrade was introduced.

## CI and deployment safety

- Added a MariaDB-backed quality gate for pull requests, main pushes and manual releases.
- The gate installs from lock files, audits dependencies, compiles Blade/Vite and runs PHPUnit.
- Pull requests only test.
- A normal push to `main` tests and builds an immutable `sha-<commit>` image; it does not mutate production.
- Production deployment is a manual `workflow_dispatch` protected by the `production` environment and requires a verified backup reference.
- The release runs migrations with the immutable image before updating the Kubernetes deployment.
- The standalone migration workflow also requires an immutable SHA tag and backup reference.

## Reproducible Docker build

- PHP image pinned to `php:8.3.24-fpm-alpine3.22`.
- Node image pinned to `node:22.23.1-alpine3.24`.
- Composer image pinned to `composer:2.10.2`.
- Docker uses `composer install` from `composer.lock` and `npm ci` from `package-lock.json`.
- Production images are tagged with the full commit SHA.

The three pinned tags were verified against their registries. A complete local image build could not run because the Docker Desktop Linux daemon was unavailable; the CI image-build job is the authoritative build verification on the first push.

## Migration verification

Migration `2026_07_25_000002_add_global_special_schedule_subjects`:

- Applied successfully to the local development database.
- Verified VHTT, NPL, SHL, NL, NT and NH exactly once as active special activities.
- Rolled back and re-applied successfully on the dedicated test database.
- Migration and rollback procedures are documented in `docs/operations/MIGRATION_RUNBOOK.md`.

## Artifact policy

- Removed the misleading `composer.lock` ignore rule.
- Documented the lock-file and `public/build` policy.
- Root inspection scripts and scratch DOCX files are excluded from releases.
- Root Office/PDF design references are excluded from Docker runtime images.
- The LHL PDF Single Source of Truth remains in the project root until Sprint 16.

## Verification results

```text
composer install --dry-run: consistent, nothing to change
composer audit --locked: no advisories
npm audit --omit=dev: 0 vulnerabilities
php artisan view:cache: pass
npm run build: pass
php artisan test: 128 passed, 509 assertions
native dialog scan: no bare alert/confirm/prompt
workflow YAML parse: pass
migration down/up on test DB: pass
```
