# Restore drill template (Gate 5D)

**RTO target:** 4 hours · **RPO target:** 24 hours

This template is filled during the ops drill. Evidence stays outside the repo (ticket / shared drive).

## Pre-conditions

- [ ] Daily DB backup job verified in last 24h
- [ ] PITR / binary logs enabled on staging or prod replica
- [ ] Secrets available to restore operator
- [ ] On-call alert channel tested (page fires)

## Drill steps

1. Record start time (UTC): __________
2. Snapshot / note current APP_KEY and `.env` location
3. Restore DB from last backup to staging
4. Restore `storage/app` object files (documents cache)
5. Run `php artisan migrate --force` if schema lag
6. Smoke: login, directory, document download, search, projects
7. Record end time (UTC): __________
8. Elapsed ≤ 4h? Yes / No

## Sign-off

| Role | Name | Date | Signature |
|------|------|------|-----------|
| IT Admin (Victor) | | | |
| Sponsor (Jon) | | | |

## Attachments

- Backup filename / snapshot ID: __________
- Incident ticket: __________
