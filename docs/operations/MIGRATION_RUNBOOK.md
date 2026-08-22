# Production migration runbook

## Before migration

1. Confirm the target image passed the CI quality gate.
2. Use an immutable image tag in the form `sha-<40-character commit SHA>`.
3. Create a database backup and record its ID/path.
4. Verify the backup can be listed/read from its destination.
5. Review migration `up()` and `down()` behavior and determine whether rollback is data-destructive.
6. Confirm the application version remains backward compatible while migration is running.

## Run

For a full release, manually run **Quality Gate, Build and Deploy** and enter the verified `backup_reference`. The workflow:

1. Re-runs the quality gate.
2. Builds/tags the exact commit image.
3. Mirrors the immutable image.
4. Runs the migration job with that image.
5. Only rolls out the deployment after migration succeeds.

The normal `push main` event only tests and builds an immutable image; it does not automatically mutate production.

Use the separate **Run DB Migration** workflow only when a migration must be retried without rolling out the application. Enter:

- `image_tag`: the exact immutable `sha-*` tag.
- `backup_reference`: the verified backup ID/path.

The workflow runs migrations, synchronizes permissions and clears stale caches using the exact release image.

## Verify

```text
php artisan migrate:status
php artisan about
php artisan permissions:sync
```

Then smoke-test:

- Dashboard login and menu.
- LMS course view.
- Grades book view.
- Template management index, preview and export.

For migration `2026_07_25_000002_add_global_special_schedule_subjects`, verify that:

- `subjects.is_special_activity` exists.
- VHTT, NPL, SHL, NL, NT and NH exist once.
- The six records are active and marked as special activities.

## Rollback

1. Stop rollout if smoke tests fail.
2. Read the migration's `down()` method before executing rollback.
3. Restore the verified backup when rollback would remove or transform business data.
4. Roll the Kubernetes deployment back to the previous immutable image:

```text
kubectl rollout undo deployment/tkb -n tkb
kubectl rollout status deployment/tkb -n tkb
```

5. Record the failed image tag, migration batch and error logs.

Never run a blind multi-step rollback in production without identifying the exact migration batch.
