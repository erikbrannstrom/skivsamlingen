# Docker Development Environment

## Services

| Service | Purpose | Port |
|---------|---------|------|
| nginx | Web server | 8080 |
| php83 | PHP 8.3 | - |
| mysql | Database (MySQL 8.0) | 3306 |

## Quick Start

```bash
# Start all services
docker compose up -d

# View logs
docker compose logs -f

# Stop services
docker compose down
```

## First-Time Setup

1. **Import database (required - database is empty by default):**

   ```bash
   docker compose exec -T mysql mysql -uskivsamlingen -pskivsamlingen skivsamlingen_s < backup.sql
   ```

   Without a database import, you'll see "Table doesn't exist" errors.

2. **Access the site:** http://localhost:8080

## Running Commands

```bash
# Laravel artisan
docker compose exec php83 php /var/www/skivsamlingen.se/artisan migrate
docker compose exec php83 php /var/www/skivsamlingen.se/artisan cache:clear

# Composer
docker compose exec php83 composer install -d /var/www/skivsamlingen.se

# MySQL CLI
docker compose exec mysql mysql -uskivsamlingen -pskivsamlingen skivsamlingen_s
```

## Differences from Production

| Aspect | Docker | Production |
|--------|--------|------------|
| PHP-FPM | TCP port 9000 | Unix sockets |
| MySQL host | `mysql` | `localhost` |
| SSL | None | Let's Encrypt |
| Port | 8080 | 80/443 |
