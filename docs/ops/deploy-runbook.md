# Deploy runbook

## Local (XAMPP)

1. `git pull`
2. `composer install --no-dev` (or with dev for staging)
3. `php artisan migrate --force`
4. `php artisan db:seed --class=RoleSeeder` only on empty envs
5. `npm ci && npm run build` if assets changed
6. `php artisan optimize`
7. Smoke `/login` and `/up`

## Staging / production

1. Put app in maintenance: `php artisan down`
2. Deploy release artefact
3. `composer install --no-dev --optimize-autoloader`
4. `php artisan migrate --force`
5. `php artisan config:cache && php artisan route:cache && php artisan view:cache`
6. Restart queue workers / scheduler
7. `php artisan up`
8. Verify Gate smoke checklist (login, search, download, admin)

## Rollback

1. `php artisan down`
2. Redeploy previous release tag
3. Restore DB from pre-deploy snapshot if migrations are non-backward-compatible
4. See `docs/migration/rollback.md`
5. `php artisan up`
