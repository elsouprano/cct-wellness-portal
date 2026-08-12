# Production Deployment Security Checklist

Before deploying the CCT Wellness Portal to a live production environment, the following configuration changes and checks **MUST** be performed to ensure application security and proper functionality.

## 1. Environment Variables (`.env`)
The production `.env` file must be created on the production server and heavily restricted (chmod 600). Do not ever commit it to version control.

- [ ] **`APP_ENV=production`**: Set this to `production`. This disables certain development features and optimizes the framework for security and performance.
- [ ] **`APP_DEBUG=false`**: **CRITICAL!** Must be strictly set to `false`. If left as true in production, detailed stack traces containing sensitive database credentials, API keys, and environment variables will be exposed to any user encountering an error.
- [ ] **`APP_URL=https://your-production-domain.edu.ph`**: Set to the actual production URL, strictly using `https://`.

## 2. HTTPS and Session Security
- [ ] **Enforce HTTPS**: Configure your web server (Nginx/Apache) to redirect all `http://` traffic to `https://`.
- [ ] **`SESSION_SECURE_COOKIE=true`**: Set this in your `.env`. This ensures session cookies are only transmitted over secure HTTPS connections, preventing session hijacking over local networks.

## 3. Mail Configuration
- [ ] **SMTP Setup**: Configure a real, secure SMTP provider (e.g., SendGrid, Mailgun, or institutional Exchange server) rather than a testing driver like Mailtrap or Log.
  - `MAIL_MAILER=smtp`
  - `MAIL_HOST=...`
  - `MAIL_PORT=587` (typically)
  - `MAIL_USERNAME=...`
  - `MAIL_PASSWORD=...`
  - `MAIL_ENCRYPTION=tls`

## 4. File Permissions
- [ ] Ensure the web server user (e.g., `www-data` or `nginx`) only has **write** access to the following specific directories:
  - `storage/`
  - `bootstrap/cache/`
- [ ] The rest of the application files should be read-only for the web server to prevent malicious modification if the server is breached.

## 5. Performance and Caching
Run these commands during your deployment script to optimize performance and prevent configuration parsing errors:
- [ ] `php artisan config:cache` (Combines config into a single file)
- [ ] `php artisan route:cache` (Speeds up route registration)
- [ ] `php artisan view:cache` (Pre-compiles Blade templates)
- [ ] `php artisan event:cache` (Caches event listeners)
