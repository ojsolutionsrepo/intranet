# Security hardening (Gate 5A)

## Implemented in code

| Control | Location |
|---------|----------|
| Security headers | `app/Http/Middleware/SecureHeaders.php` |
| Login / 2FA rate limits | `FortifyServiceProvider` (5/min) |
| Download rate limits | `throttle:downloads` on document downloads (60/min) |
| Search rate limit | `RateLimiter::for('search')` registered |
| Policies | Document, Post, Project, CalendarEvent |
| IDOR suite | `tests/Feature/Phase5HardeningTest.php` |
| Virus scan adapter | `VirusScanner` + Null / ClamAV drivers |

## Staging / ops still required

- [ ] OWASP ZAP (or equivalent) scan — zero critical/high
- [ ] Enable `VIRUS_SCANNER=clamav` with reachable clamd
- [ ] Confirm HSTS behind HTTPS terminator
- [ ] Review CSP if adding third-party analytics

## Enable ClamAV

```
VIRUS_SCANNER=clamav
CLAMAV_HOST=127.0.0.1
CLAMAV_PORT=3310
```
