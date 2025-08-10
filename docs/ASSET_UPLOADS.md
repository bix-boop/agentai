# Optional Asset Uploads (For Restricted Hosting)

These steps are only needed if your Plesk server cannot run Node or Composer or has no outbound network access. They let you deploy Phoenix AI without SSH or system Composer/Node.

- Frontend build (no Node on server)
  - On your local machine:
    - Run `cd frontend && npm install && npm run build`
    - Zip the `build/` folder as `frontend_build.zip`
  - On server (Plesk File Manager):
    - Upload `frontend_build.zip` to `frontend/`
    - Extract to `frontend/build/`
    - The installer will copy `frontend/build/` to `backend/public/app/`

- Composer fallback
  - Fast path: Upload `backend/composer.phar` to skip the web download in installer.
  - No network access/vendor prebuilt:
    - On your local machine (matching PHP major/minor version as server):
      - `cd backend && composer install --no-dev --optimize-autoloader`
      - Zip the resulting `vendor/` folder as `vendor.zip`
    - On server:
      - Upload `vendor.zip` to `backend/`
      - Extract to `backend/vendor/`
      - Visit `/setup_vendor.php` to verify and then `/installer`

- Environment configuration
  - You may pre-upload `backend/.env` with your settings (DB, SMTP, Stripe/PayPal, OPENAI_API_KEY). The installer can also generate this for you.

- Storage symlink
  - The installer runs `php artisan storage:link`. If your host blocks symlinks, you can upload static assets into `backend/public/storage/` as needed.

With these uploaded assets, Phoenix AI installs via `/installer` without requiring Node or system Composer on the server.