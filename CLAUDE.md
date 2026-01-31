# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Skivsamlingen.se is a Swedish record collection management website created around 2007. It's a community-driven platform where users can catalog their vinyl records, browse other users' collections, and discover music statistics.

The application is built with **Laravel 12.x** and **PHP 8.3+**.

## Directory Structure

```
/skivsamlingen.se/
├── app/                  # Application code
├── config/               # Configuration
├── database/             # Migrations and schema
├── public/               # Web root
├── resources/            # Views and assets
├── routes/               # Route definitions
├── static/               # Static assets
└── tests/                # Feature and unit tests
```

## Development Environment

The project uses Docker for local development.

### Local Development Setup

1. Start the containers: `docker compose up -d`
2. Configure database credentials in `.env` (not tracked)
3. Install dependencies: `docker compose exec php83 composer install -d /var/www/skivsamlingen.se`
4. Access the site at `http://localhost:8080`

**Docker Services:**
- `nginx` - Web server (port 8080)
- `php83` - PHP 8.3
- `mysql` - MySQL 8.0 database (port 3306)

### Common Commands

All commands run through Docker.

**Docker:**
```bash
docker compose up -d      # Start all containers
docker compose down       # Stop all containers
docker compose logs -f    # Follow logs from all containers
docker compose ps         # List running containers
```

**Running Tests:**
```bash
docker compose exec php83 php /var/www/skivsamlingen.se/artisan test
docker compose exec php83 php /var/www/skivsamlingen.se/artisan test --filter=NewsTest
docker compose exec php83 php /var/www/skivsamlingen.se/artisan test --filter=test_news_index_displays_articles
```

**Dependencies:**
```bash
docker compose exec php83 composer install -d /var/www/skivsamlingen.se
docker compose exec php83 composer update -d /var/www/skivsamlingen.se
```

**Cache Management:**
```bash
docker compose exec php83 php /var/www/skivsamlingen.se/artisan cache:clear
docker compose exec php83 php /var/www/skivsamlingen.se/artisan config:clear
docker compose exec php83 php /var/www/skivsamlingen.se/artisan view:clear
docker compose exec php83 php /var/www/skivsamlingen.se/artisan optimize:clear
```

**Development Helpers:**
```bash
docker compose exec php83 php /var/www/skivsamlingen.se/artisan route:list
docker compose exec php83 php /var/www/skivsamlingen.se/artisan tinker
```

**Database:**
```bash
docker compose exec php83 php /var/www/skivsamlingen.se/artisan migrate
docker compose exec php83 php /var/www/skivsamlingen.se/artisan migrate:status
docker compose exec mysql mysql -uskivsamlingen -pskivsamlingen skivsamlingen_s
```

## Architecture

### Structure

**Controllers** (`app/Http/Controllers/`)
- `NewsController.php` - News/announcements
- `HomeController.php` - Home and about pages
- `UsersController.php` - User profiles
- `AccountController.php` - Login, registration, settings
- `CollectionController.php` - Record collection management

**Models** (`app/Models/`)
- `BaseModel.php` - Base model with `$timestamps = false` for existing schema
- `User.php` - User model (extends Authenticatable)
- `News.php` - News model
- `Record.php`, `Artist.php`, `RecordUser.php` - Collection models

**Views** (`resources/views/`)
- Blade templates organized by controller
- `layouts/application.blade.php` - Main layout

**Routes** (`routes/web.php`)

### Common Patterns

**Creating a new controller:**
1. Create model in `app/Models/` extending `BaseModel`
2. Create controller in `app/Http/Controllers/`
3. Add routes in `routes/web.php`
4. Create Blade views in `resources/views/{controller}/`
5. Write tests in `tests/Feature/`

**Querying the database:**

**Always prefer Eloquent models and relationships over `DB::table()` or raw SQL.** Eloquent provides better maintainability, type safety, and consistency across the codebase.

```php
// PREFERRED: Eloquent with relationships
$user = User::where('username', $username)->firstOrFail();
$records = $user->records()->with('artist')->paginate(25);
News::newest()->paginate(5);

// PREFERRED: Eloquent query builder for complex queries
User::select('username', User::raw('COUNT(*) as record_count'))
    ->join('records_users', 'users.id', '=', 'records_users.user_id')
    ->groupBy('users.id', 'users.username')
    ->orderByDesc('record_count')
    ->limit(10)
    ->get();

// AVOID: DB::table() - only use when Eloquent is genuinely impractical
// DB::table('records_users')->where('user_id', $id)->get();

// AVOID: Raw SQL - only use for complex queries that cannot be expressed otherwise
// DB::select('SELECT * FROM users WHERE ...');
```

**Why prefer Eloquent:**
- Relationships are defined once in models and reused everywhere
- Eager loading prevents N+1 query problems
- Model events, accessors, and mutators work automatically
- Better IDE support and type hinting
- Consistent patterns across the codebase

---

## Database Schema

Key tables:
- `users` - User accounts (username, password, email, birth, sex, about, etc.)
- `artists` - Artist names
- `records` - Records (title, year, format, artist_id)
- `records_users` - Junction table linking users to their record collections
- `donations` - Tracks supporter status
- `news` - Site announcements

**Important:** The existing schema is preserved. Laravel models use `$timestamps = false`.

---

## Authentication & Authorization

Custom password hashing:
- Algorithm: `sha256(md5(username)[0:12] + password + global_salt)`
- Old passwords use `sha1()` and are automatically upgraded on login
- `AUTH_GLOBAL_SALT` configured in `.env`

Session and cookies:
- Laravel session-based authentication
- Persistent login available

---

## Key Conventions

1. **Swedish language**: Database fields, validation messages, and UI text are in Swedish
   - `användarnamn` = username, `lösenord` = password, `e-post` = email
   - `kön` = gender (f/m/x), `namn` = name, `om mig` = about me

2. **No timestamps**: Existing tables don't use Laravel's `created_at`/`updated_at` convention

3. **Case-sensitive matching**: Record titles use `COLLATE utf8_bin` for case-sensitivity

4. **"The" prefix sorting**: Artist names strip "The " prefix when sorting

5. **Main branch**: `master` (not `main`)

---

## Important Notes

- **Security**: Uses custom password hashing (not bcrypt)
- **Database credentials**: Not tracked in git - configure in `.env`
