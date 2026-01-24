# Docker Development Environment

This Docker setup mirrors the production environment with dual PHP versions for the incremental Laravel migration.

## Services

| Service | Purpose | Port |
|---------|---------|------|
| nginx | Web server, routes to PHP 5.6 or 8.3 | 8080 |
| php56 | CodeIgniter (legacy) | - |
| php83 | Laravel (new) | - |
| mysql | Database (MySQL 8.0) | 3306 |

## Quick Start

```bash
# Start all services
docker-compose up -d

# View logs
docker-compose logs -f

# Stop services
docker-compose down
```

## First-Time Setup

1. **Create CodeIgniter config files:**

   ```bash
   cp application/config/database.php.docker application/config/database.php
   cp application/config/config.php.docker application/config/config.php
   ```

   The Docker configs are pre-configured with correct settings.

2. **Import database (required - database is empty by default):**

   ```bash
   docker-compose exec -T mysql mysql -uskivsamlingen -pskivsamlingen skivsamlingen_s < backup.sql
   ```

   Without a database import, you'll see "Table doesn't exist" errors.

3. **Access the site:**

   - CodeIgniter: http://localhost:8080
   - Laravel (when installed): http://localhost:8080/laravel/public

## Notes

- **PHP 5.6 compatibility**: Uses `index.docker.php` which sets `ENVIRONMENT='testing'` to suppress legacy PHP notices from CodeIgniter 2.x
- **MySQL charset**: Configured with `utf8mb4_general_ci` for PHP 5.6 compatibility (MySQL 8.0's default `utf8mb4_0900_ai_ci` isn't supported by old PHP mysql clients)

## Installing Laravel

When ready to start migration:

```bash
# Enter the PHP 8.3 container
docker-compose exec php83 bash

# Install Laravel (from /var/www/skivsamlingen.se)
cd /var/www/skivsamlingen.se
composer create-project laravel/laravel laravel "12.*"

# Configure Laravel
cd laravel
cp .env.example .env
php artisan key:generate
```

Edit `laravel/.env`:
```env
DB_CONNECTION=mysql
DB_HOST=mysql
DB_DATABASE=skivsamlingen_s
DB_USERNAME=skivsamlingen
DB_PASSWORD=skivsamlingen
```

## Enabling Laravel Routes

As you migrate controllers, uncomment the corresponding location blocks in `docker/nginx/default.conf`:

```nginx
# Example: Enable news routes
location /news {
    try_files $uri $uri/ @laravel;
}
```

Then reload nginx:
```bash
docker-compose exec nginx nginx -s reload
```

## Running Commands

```bash
# CodeIgniter (PHP 5.6)
docker-compose exec php56 php index.php controller/method

# Laravel artisan (PHP 8.3)
docker-compose exec php83 php artisan migrate
docker-compose exec php83 php artisan cache:clear

# Composer (in Laravel directory)
docker-compose exec php83 composer install

# MySQL CLI
docker-compose exec mysql mysql -uskivsamlingen -pskivsamlingen skivsamlingen_s
```

## Differences from Production

| Aspect | Docker | Production |
|--------|--------|------------|
| PHP-FPM | TCP port 9000 | Unix sockets |
| MySQL host | `mysql` | `localhost` |
| SSL | None | Let's Encrypt |
| Port | 8080 | 80/443 |
