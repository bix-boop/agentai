# Deployment

- PHP-FPM and Nginx/Apache config: document root `backend/public`.
- Queues: set `QUEUE_CONNECTION=database` and run worker via supervisor/plesk.
- Scheduler: add cron `* * * * * php /path/to/backend/artisan schedule:run`.
- Caching: `php artisan config:cache && php artisan route:cache`.