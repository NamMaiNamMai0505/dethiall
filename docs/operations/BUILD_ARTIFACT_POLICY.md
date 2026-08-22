# Dependency and build artifact policy

**Effective from:** Sprint 14 — 2026-07-25

## Files that must be versioned

- `composer.json` and `composer.lock`
- `package.json` and `package-lock.json`
- Docker and CI configuration
- Reference templates that are explicitly used by application configuration or automated tests

Production and CI must install from lock files:

```text
composer install --no-dev --classmap-authoritative
npm ci
```

`composer update` and `npm update` are maintenance actions. They must not run inside a production image build.

## `public/build`

`public/build` remains versioned for installations that deploy PHP source without running Node on the target server.

Rules:

1. Never edit a generated asset manually.
2. Run `npm ci && npm run build`.
3. Commit the new manifest, new hashed assets and deletion of obsolete hashed assets together.
4. Docker still rebuilds the same assets from `package-lock.json`; the tracked copy is a deployment fallback, not the Docker build input.

## Reference Office/PDF files

- `mẫu xuất LHL.pdf` remains the LHL visual Single Source of Truth until Sprint 16 moves it together with all code/document references.
- Real-data samples containing operational information must not be added to Git.
- Sanitized automated-test files belong under `tests/Fixtures`.
- Application-owned templates belong under `resources/templates`.

## Local inspection files

Root scratch scripts such as `_db.php`, `_inspect_*.php` and `_sample_*.docx` are local artifacts and are ignored. They must not be copied into Docker images or releases.

## Release verification

Every release must pass:

```text
composer audit --locked --no-interaction
npm audit --omit=dev
php artisan view:cache
npm run build
php artisan test
```
