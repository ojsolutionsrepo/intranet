# Migration rollback

1. Stop writers (maintenance mode)
2. Restore pre-migration DB snapshot
3. Restore `storage/app/documents` from pre-migration tarball
4. Redeploy previous app version
5. Verify document download checksums for 5 spot-check files
6. Open hypercare channel; notify champions

Rollback owner: __________ Date: __________
