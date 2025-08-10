# Troubleshooting

- Installer cannot find PHP CLI: ensure correct Plesk PHP binary and PATH.
- Database connection failed: verify host/port/user and grant privileges.
- Frontend build fails: ensure Node 18+ is available; if not, skip build and deploy API only.
- 500 errors: check `backend/storage/logs/laravel.log`.