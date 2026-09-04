# Deploy runbook

Operational steps for OJ Solutions Intranet (local XAMPP and hosted production).

---

## Local (XAMPP)

1. `git pull`
2. `composer install --no-dev` (or with dev for staging)
3. `php artisan migrate --force`
4. `php artisan db:seed --class=RoleSeeder` only on empty envs
5. `npm ci && npm run build` if assets changed
6. `php artisan optimize`
7. Smoke `/login` and `/up`

---

## Staging / production

1. Put app in maintenance: `php artisan down`
2. Deploy release artefact (or `git pull` — see [Git pull on the server](#git-pull-on-the-server))
3. `composer install --no-dev --optimize-autoloader`
4. `php artisan migrate --force`
5. `php artisan config:cache && php artisan route:cache && php artisan view:cache`
6. Restart queue workers / scheduler
7. `php artisan up`
8. Verify Gate smoke checklist (login, search, download, admin)

---

## Rollback

1. `php artisan down`
2. Redeploy previous release tag
3. Restore DB from pre-deploy snapshot if migrations are non-backward-compatible
4. See `docs/migration/rollback.md`
5. `php artisan up`

---

## SSH into the server

Use PowerShell or Windows Terminal on your PC.

1. In the hosting control panel, open **SSH access** and note:
   - Host (hostname or IP)
   - Port (usually `22`, sometimes `2222`)
   - Username
   - Password or SSH key
2. Connect:

```powershell
ssh USERNAME@HOST
```

If the port is not 22:

```powershell
ssh -p 2222 USERNAME@HOST
```

3. Go to the app directory (path varies by host):

```bash
cd ~/ojsolutionsintranet/public_html
# or: cd /home/<user>/ojsolutionsintranet/public_html
pwd
ls
```

First connection: type `yes` if asked to trust the host key. Prefer SSH keys over passwords when the panel supports them.

---

## Git pull on the server

If pull fails with **“The working directory is not clean”**, the server checkout has local changes.

### 1. Inspect

```bash
cd ~/ojsolutionsintranet/public_html   # adjust to real path
git status
git diff --stat
```

### 2. Clean, then pull (typical for deploy hosts)

```bash
# Optional backup of local edits
git stash push -u -m "server-local-before-pull"

git fetch origin
git reset --hard origin/main   # use your real branch: main / master / production
git clean -fd                  # removes untracked files — see caution below
git pull
```

Gentler option if you only need to stash and pull:

```bash
git stash push -u -m "server-local-before-pull"
git pull
```

### 3. Do not wipe

- `.env` (gitignored; keep on the server)
- `storage/` uploads (logos, favicons, documents)
- `public/storage` symlink

If `git clean -fd` would delete uploads, stop and review `git status` first.

After pull:

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan up
```

---

## Google Drive broker (UR-INT-02)

The Drive broker uses **Google Drive OAuth** only. **Microsoft OneDrive is not supported** yet (would need a Microsoft Graph adapter).

Without Drive credentials, the app uses the local Drive broker (files stay on the server).

### Env keys

```env
DRIVE_BROKER_ENABLED=true
GOOGLE_DRIVE_CLIENT_ID=xxxxx.apps.googleusercontent.com
GOOGLE_DRIVE_CLIENT_SECRET=GOCSPX-…
GOOGLE_DRIVE_FOLDER_ID=…          # optional Shared Drive / folder id
```

These can also be set in **Admin → Integrations** (Drive credentials form), which writes `.env` and applies immediately.

### A. Create OAuth client in Google Cloud

1. Open [Google Cloud Console](https://console.cloud.google.com/) and sign in with the Workspace/admin account that will own Drive files.
2. Create or select a project (e.g. **OJ Intranet**).
3. **APIs & Services → Library** → enable **Google Drive API**.
4. **APIs & Services → OAuth consent screen**:
   - Prefer **Internal** if all users are in your Google Workspace (no public verification).
   - Or **External** + **Testing** for early setup (testers only — see below).
   - App name: e.g. `OJ Intranet`; set a support email; save.
5. **APIs & Services → Credentials → Create credentials → OAuth client ID**.
6. Application type: **Web application**.
7. Under **Authorized redirect URIs**, paste exactly what Admin → Integrations shows, for example:
   - Production: `https://YOUR-DOMAIN/drive/oauth/callback`
   - Local XAMPP: `http://localhost/intranet/drive/oauth/callback`
8. Create and copy **Client ID** and **Client secret**.

Treat the client secret like a password.

### B. Scopes

The intranet requests these full scope URLs at connect time (do **not** use shortened `.../auth/...` forms):

```
https://www.googleapis.com/auth/drive
https://www.googleapis.com/auth/drive.file
https://www.googleapis.com/auth/userinfo.email
```

You can skip adding scopes on the consent screen — Connect Drive requests them automatically.

If you add them under **Data Access / Scopes → Add or remove scopes**, search and tick Drive + email scopes, or paste the **full** URLs above. Pasting `.../auth/drive` will fail with “invalid scopes”.

### C. Folder ID (optional)

1. Open the Shared Drive / folder in [Google Drive](https://drive.google.com/).
2. From the URL, copy the ID after `/folders/`:

`https://drive.google.com/drive/folders/`**`1AbC…xyz`**

That value is `GOOGLE_DRIVE_FOLDER_ID`.

### D. Connect in the intranet

1. Sign in as Admin → **Integrations**.
2. Paste Client ID, Client secret, and optional Folder ID.
3. Enable **Drive broker** → **Save to .env**.
4. Click **Connect Google Drive** and approve with the Google account that can access the folder.
5. Confirm status shows connected.

### E. Error: `403: access_denied` / “has not completed Google verification”

The OAuth app is in **Testing**. Only approved test users can connect.

1. Google Cloud → **OAuth consent screen** → **Audience** / **Test users**.
2. **Add users** → add the exact Google account you use to Connect Drive.
3. Save, then retry Connect in an Incognito window (or after signing out of Google).

For a company intranet on Google Workspace, set the consent screen to **Internal** so domain users can connect without public verification.

Do **not** publish an External app unless you are ready for Google’s verification of sensitive Drive scopes.

### F. Connect checklist

- [ ] Google Drive API enabled
- [ ] Redirect URI matches Admin → Integrations character-for-character (`http` vs `https`, path, no trailing-slash mismatch)
- [ ] Client ID + secret saved; Drive broker enabled
- [ ] Consent screen Internal, or External Testing with your account on the tester list
- [ ] Connecting with an account that can access the target folder / Shared Drive

---

## Branding uploads (logo / favicon) on restricted hosts

Some shared hosts disable PHP `tmpfile()` and/or `fileinfo` (`finfo_open`). Livewire’s upload pipeline needs those and will 500.

**Logo / favicon** on **Admin → Site settings** and **document upload** on **Documents → Upload** use **classic multipart forms** (not Livewire temp uploads), so they work on those hosts.

If other Livewire file fields still fail with `tmpfile()` / `finfo_open` undefined, ask the host to:

- Enable the **fileinfo** extension
- Remove `tmpfile` from `disable_functions` (if policy allows)

---

## Smoke after deploy

- [ ] `/up` healthy
- [ ] Login works
- [ ] Admin → Site settings (name/accent; logo & favicon upload)
- [ ] Admin → Integrations → Drive connect (if enabled)
- [ ] Document download / search still works with Drive down (degrade, never fail)
