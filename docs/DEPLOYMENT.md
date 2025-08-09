# Deployment (Plesk / Shared Hosting)

Prerequisites
- PHP 8.1+ with extensions: pdo_mysql, mbstring, curl, json, openssl, gd
- MySQL/MariaDB database + user with full privileges

Steps
1) In Plesk, create a new domain/subdomain
2) Set Document Root to `httpdocs/public` (or ensure `public/` is the web root)
3) Upload ZIP of Phoenix AI to domain root and extract
4) Ensure `storage/` directories are writable by the web server
5) Visit your domain (e.g., `https://yourdomain.com/`) to launch the installer
6) Complete the wizard; the installer will create `.env`, import schema, and lock itself

Post-Install
- Delete or restrict access to `/install` (installer creates `install/installed.lock`)
- Configure SMTP, Stripe/PayPal keys in Admin -> Settings
- Set up CRON (optional) to hit `scripts/cron.php`

Upgrades
- Backup DB and `.env`
- Replace updated app files, keeping `storage/` and `.env`
- Run any new installer migration step if provided

Troubleshooting
- Check `storage/logs/app.log` for errors
- Ensure `.htaccess` rewrite works in `public/`
- Verify PHP extensions and permissions