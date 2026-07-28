# Migration taxonomy (Gate 5C)

Signed before production cutover.

## Source → destination

| Source | Destination module | Notes |
|--------|--------------------|-------|
| Shared Drive folders | Documents categories | Checksum per file |
| Email announcement archive | News posts | Sanitize HTML |
| Policy PDFs | Policy hub + mandatory ack | Version = 1 on import |
| Plane projects | Projects (source=plane) | external_ref stable |
| Governex CSV/API | Projects (source=governex) | Same adapter |

## Dry-run checklist (10% sample)

- [ ] Sample set listed and approved
- [ ] Checksums match after import
- [ ] Audience mapping reviewed by HR
- [ ] Rollback snapshot taken

**Taxonomy owner sign-off:** __________ Date: __________
