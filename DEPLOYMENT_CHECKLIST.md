# CCT Wellness Portal � Deployment Checklist

## Manual Backup (On-Demand)

To trigger a backup manually at any time (during development or before risky changes):

```bash
# Full backup: MariaDB database dump + storage/app/public uploaded files
php artisan backup:run

# Database only (faster):
php artisan backup:run --only-db

# Files only:
php artisan backup:run --only-files
```

**Backup files are saved to:** `storage/app/backups/CCT Wellness Portal/`

---

## Before Going Live � Production Checklist

### ⚙️ Environment & Config
- [ ] Set `APP_ENV=production` and `APP_DEBUG=false` in `.env`
- [ ] Set a strong `APP_KEY` (generate with `php artisan key:generate`)
- [ ] Set `APP_URL` to the real domain (e.g. `https://wellness.cct.edu.ph`)
- [ ] Configure real SMTP credentials for email verification / notifications
- [ ] Set `SESSION_SECURE_COOKIE=true` (HTTPS required)

### 🚀 Production Cache & Optimization Steps
Run these commands in order during deployment:
```bash
# 1. Build frontend production assets via Vite
npm run build

# 2. Clear stale cache files if upgrading
php artisan optimize:clear

# 3. Cache configuration, routes, and Blade views
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 4. Equivalent single-command optimization (caches config & routes)
php artisan optimize
```
> ⚠️ **Note:** Whenever you change `.env`, config files, or routes in production, you MUST re-run `php artisan optimize:clear` followed by `php artisan optimize` (or individual cache commands).

### ⚡ Session, Cache & Queue Driver Production Recommendation
- [ ] **Migrate off the `database` driver for high-concurrency production deployments:**
  Currently, `.env` defaults to:
  ```env
  SESSION_DRIVER=database
  CACHE_STORE=database
  QUEUE_CONNECTION=database
  ```
  - **Why move off `database`?** During cohort-wide assessment windows, hundreds of students submit responses simultaneously. Using MariaDB/MySQL for sessions, cache, and queues creates severe lock contention on `sessions`, `cache`, and `jobs` tables, consuming database connection pool slots and elevating CPU/I/O latency.
  - **Recommended Production Stack:**
    - **Redis** (via `phpredis` extension):
      ```env
      SESSION_DRIVER=redis
      CACHE_STORE=redis
      QUEUE_CONNECTION=redis
      ```
      Redis provides sub-millisecond in-memory operations, cache tagging support, and eliminates relational database locking under heavy load.
    - If dedicated Redis is unavailable, keep `SESSION_DRIVER=file` or `database`, but ensure table indexes and scheduled pruning (`php artisan queue:prune-failed`) are active.

### 🗄️ Database
- [ ] Run `php artisan migrate --force` on the production database
- [ ] Seed the admin user: `php artisan db:seed --class=DatabaseSeeder --force`
- [ ] Confirm no SQLite connections are used (project uses MariaDB/MySQL)

### ? Storage
- [ ] Run `php artisan storage:link` to create the `public/storage` symlink
- [ ] Confirm `storage/` and `bootstrap/cache/` are writable by the web server

### ? Task Scheduler (Required for Automated Backups)
The daily backup is scheduled in `routes/console.php`. For it to run automatically, add this **single cron entry** on the Linux server:

```
* * * * * cd /var/www/html/cct-wellness-portal && php artisan schedule:run >> /dev/null 2>&1
```

> ?? **Local/XAMPP dev note:** The scheduler does NOT run automatically without this cron. Use `php artisan backup:run` manually during local development.

### ? Backups � CRITICAL: Move Off-Server

> ?? **TODO (HIGH PRIORITY):** The current backup configuration stores backup archives on the
> **local disk** (`storage/app/backups/`). A backup stored on the same server it protects
> provides zero protection if the server disk fails or the server is lost.

Once hosting is decided, update `config/backup.php` and `config/filesystems.php` to store
backups in off-server cloud storage. Options:

- **Amazon S3** � `s3` driver, ~$0.023/GB/month
- **Google Cloud Storage** � `gcs` driver (via spatie/flysystem-gcs), ~$0.020/GB/month
- **Cloudflare R2** � S3-compatible, free up to 10GB
- **Backblaze B2** � S3-compatible, free up to 10GB

Steps once cloud storage is chosen:
1. Install the appropriate Flysystem driver (e.g. `composer require league/flysystem-aws-s3-v3`)
2. Add credentials to `.env`
3. Define the cloud disk in `config/filesystems.php`
4. Change the `'disks'` value in `config/backup.php` from `'backup'` to your cloud disk name

### ? Security
- [ ] Run `php artisan backup:list` to confirm backups are generating correctly
- [ ] Test email delivery: register a student account and verify email arrives
- [ ] Confirm `.env` is **not** publicly accessible (check web server config)
- [ ] Ensure `vendor/`, `storage/`, and `.env` are outside the web root or blocked
- [ ] Review queue worker setup if using `QUEUE_CONNECTION=database`

### ? Final Smoke Test
- [ ] Register as a student ? email verification arrives ? login works
- [ ] Staff login works, dashboard loads with stats
- [ ] Academic year can be created and set active
- [ ] Schedules can be created and linked to the active academic year
- [ ] Students can access `/inventory` when a schedule is active
- [ ] PDF export of a submission works
- [ ] Run `php artisan backup:run` and confirm `storage/app/backups/` contains a `.zip` file

---

## Running a test tunnel

To expose the local development environment to external testers:
1. Ensure `.env` is updated with `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, `SESSION_SAME_SITE=lax`.
2. Clear config caches: `php artisan optimize:clear`.
3. Run the helper script `start-tunnel.bat` (Windows) in the root directory.
4. Share the generated `https://*.trycloudflare.com` URL with your testers.
