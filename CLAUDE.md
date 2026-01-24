# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Skivsamlingen.se is a Swedish record collection management website created around 2007. It's a community-driven platform where users can catalog their vinyl records, browse other users' collections, and discover music statistics.

**The codebase is currently being migrated from CodeIgniter 2.x to Laravel 12.x.** See `LARAVEL_MIGRATION_PLAN.md` for the complete migration strategy and timeline.

### Current Migration Status

The project uses an incremental controller-by-controller migration approach with parallel deployment:

| Phase | Controller | Status |
|-------|-----------|--------|
| 0 | Foundation & Parallel Architecture | Complete |
| 1 | NewsController | Complete |
| 2 | HomeController | Complete |
| 3 | UsersController | Not started |
| 4 | AccountController | Not started |
| 5 | CollectionController | Not started |

**Active Routes:**
- `/`, `/about` - Served by Laravel (HomeController)
- `/news`, `/news/rss` - Served by Laravel (NewsController)
- All other routes - Served by CodeIgniter

## Directory Structure

```
/skivsamlingen.se/
├── application/          # CodeIgniter app (legacy)
├── system/               # CodeIgniter framework
├── static/               # Static assets (shared)
├── index.php             # CodeIgniter entry point
└── laravel/              # Laravel app (new)
    ├── app/
    ├── public/
    ├── resources/
    ├── routes/
    └── tests/
```

## Development Environment

This is a dual-framework setup during migration:

**CodeIgniter (Legacy):**
- PHP 5.6 compatible
- MySQL database
- nginx with rewrite rules

**Laravel (New):**
- PHP 8.3+ required
- Same MySQL database (shared)
- Composer for dependencies

### Local Development Setup

The project uses Docker for local development with dual PHP versions:

1. Start the containers: `docker compose up -d`
2. Configure database credentials:
   - CodeIgniter: `application/config/database.php` (not tracked)
   - Laravel: `laravel/.env` (not tracked)
3. Install Laravel dependencies: `docker compose exec php83 composer install -d /var/www/skivsamlingen.se/laravel`
4. Access the site at `http://localhost:8080`

**Docker Services:**
- `nginx` - Web server (port 8080)
- `php56` - PHP 5.6 for CodeIgniter
- `php83` - PHP 8.3 for Laravel
- `mysql` - MySQL 8.0 database (port 3306)

### Common Commands

All commands run through Docker. Laravel commands use the `php83` service.

**Docker:**
```bash
docker compose up -d      # Start all containers
docker compose down       # Stop all containers
docker compose logs -f    # Follow logs from all containers
docker compose ps         # List running containers
```

**Running Tests:**
```bash
docker compose exec php83 php /var/www/skivsamlingen.se/laravel/artisan test
docker compose exec php83 php /var/www/skivsamlingen.se/laravel/artisan test --filter=NewsTest
docker compose exec php83 php /var/www/skivsamlingen.se/laravel/artisan test --filter=test_news_index_displays_articles
```

**Dependencies:**
```bash
docker compose exec php83 composer install -d /var/www/skivsamlingen.se/laravel
docker compose exec php83 composer update -d /var/www/skivsamlingen.se/laravel
```

**Cache Management:**
```bash
docker compose exec php83 php /var/www/skivsamlingen.se/laravel/artisan cache:clear
docker compose exec php83 php /var/www/skivsamlingen.se/laravel/artisan config:clear
docker compose exec php83 php /var/www/skivsamlingen.se/laravel/artisan view:clear
docker compose exec php83 php /var/www/skivsamlingen.se/laravel/artisan optimize:clear
```

**Development Helpers:**
```bash
docker compose exec php83 php /var/www/skivsamlingen.se/laravel/artisan route:list
docker compose exec php83 php /var/www/skivsamlingen.se/laravel/artisan tinker
```

**Database:**
```bash
docker compose exec php83 php /var/www/skivsamlingen.se/laravel/artisan migrate
docker compose exec php83 php /var/www/skivsamlingen.se/laravel/artisan migrate:status
docker compose exec mysql mysql -uskivsamlingen -pskivsamlingen skivsamlingen_s
```

---

## CodeIgniter Architecture (Legacy)

### Framework: CodeIgniter 2.x MVC Pattern

**Controllers** (`application/controllers/`)
- All controllers extend `MY_Controller` (defined in `application/core/`)
- Controller naming convention: `{name}_controller.php` with class name `{Name}_Controller`
- Automatic view loading via `MY_Controller::_remap()` - views are loaded automatically based on controller/method names
- Main controllers:
  - `home_controller.php` - Front page, statistics, about page
  - `users_controller.php` - User profiles, search, collection display
  - `collection_controller.php` - Add/edit/delete records (requires authentication)
  - `account_controller.php` - Registration, login, account settings
  - `news_controller.php` - News/announcements (migrated to Laravel)

**Models** (`application/models/`)
- All models extend `MY_Model` (defined in `application/core/`)
- `MY_Model` provides CRUD operations and validation helpers
- Models define `$table`, `$primary_key`, and `$fields` (with validation rules)
- Key models:
  - `user.php` - User management, collection statistics, top lists
  - `record.php` - Record lookup and creation
  - `collection.php` - User's record collection (junction table `records_users`)
  - `artist.php` - Artist records
  - `Comment.php`, `Message.php`, `News.php` - Social features

**Views** (`application/views/`)
- Organized by controller: `{controller}/{method}.php`
- Layout system via `MY_Controller` with `application/views/layouts/`
- Layouts use `$yield` variable for view content

**Custom Libraries** (`application/libraries/`)
- `Auth.php` - Authentication with session and persistent login
- `MP_Cache.php` - Caching layer
- `History.php` - Navigation history tracking
- `Notice.php` / `Notification.php` - Flash messages

### CodeIgniter Routing

Routes defined in `application/config/routes.php`:
- Default controller: `home_controller`
- Pattern `$route['([^/]*)(.*)'] = "$1_controller$2"` automatically maps URLs to controllers
- nginx rewrite rules remove `index.php` from URLs

### CodeIgniter Common Patterns

**Adding a new feature to user collection:**
1. Add route in `application/config/routes.php` if needed
2. Create method in appropriate controller (e.g., `collection_controller.php`)
3. Check authentication: `if ($this->auth->isGuest()) redirect();`
4. Load models: `$this->load->model('ModelName');`
5. View auto-loads from `application/views/{controller}/{method}.php`

**Querying the database:**
```php
// Via model
$this->User->fetchOne(array('username' => $username));
$this->User->read(array('id' => 123));

// Direct query builder
$this->db->select('*')->from('users')->where('id', $uid)->get()->result();
```

---

## Laravel Architecture (New)

### Structure

**Controllers** (`laravel/app/Http/Controllers/`)
- `NewsController.php` - Migrated news functionality

**Models** (`laravel/app/Models/`)
- `BaseModel.php` - Base model with `$timestamps = false` for existing schema
- `User.php` - User model (extends Authenticatable)
- `News.php` - News model

**Middleware** (`laravel/app/Http/Middleware/`)
- `SharedAuth.php` - Reads CodeIgniter session/cookie to share authentication state

**Views** (`laravel/resources/views/`)
- Blade templates organized by controller
- `layouts/application.blade.php` - Main layout

**Routes** (`laravel/routes/web.php`)
- `/news` and `/news/rss` - News routes

### Shared Authentication

During migration, both apps share authentication via the `SharedAuth` middleware:
- Reads CodeIgniter's `ci_session` cookie (supports both cookie and database sessions)
- Reads persistent login cookie `skiv_remember`
- Logs users into Laravel based on CodeIgniter session state

### Laravel Common Patterns

**Creating a new migrated controller:**
1. Create model in `laravel/app/Models/` extending `BaseModel`
2. Create controller in `laravel/app/Http/Controllers/`
3. Add routes in `laravel/routes/web.php`
4. Create Blade views in `laravel/resources/views/{controller}/`
5. Write tests in `laravel/tests/Feature/`

**Querying the database:**
```php
// Via Eloquent
News::newest()->paginate(5);
User::where('username', $username)->firstOrFail();

// Via Query Builder
DB::table('records_users')->where('user_id', $id)->get();
```

---

## Database Schema

The database is shared between both frameworks. Key tables:
- `users` - User accounts (username, password, email, birth, sex, about, etc.)
- `artists` - Artist names
- `records` - Records (title, year, format, artist_id)
- `records_users` - Junction table linking users to their record collections
- `persistent_logins` - "Remember me" authentication tokens
- `ci_sessions` - CodeIgniter database sessions
- `donations` - Tracks supporter status
- `news` - Site announcements
- `comments`, `messages` - Social features

**Important:** The existing schema is preserved. Laravel models use `$timestamps = false`.

---

## Authentication & Authorization

Custom password hashing (shared between both apps):
- Algorithm: `sha256(md5(username)[0:12] + password + global_salt)`
- Old passwords use `sha1()` and are automatically upgraded on login
- `AUTH_GLOBAL_SALT` must match in both `application/config/config.php` and `laravel/.env`

Session and cookies:
- Session: `ci_session` cookie (CodeIgniter manages sessions)
- Persistent login: `skiv_remember` cookie (30-day expiry)
- Both stored in database tables: `ci_sessions`, `persistent_logins`

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

- **Legacy codebase**: Code quality varies (written ~2007, mixed with newer code)
- **No dependency management in CI**: Libraries (jQuery 1.4, jQuery UI 1.8) committed directly
- **Security**: Uses custom password hashing (not bcrypt). Will be upgraded post-migration.
- **Database credentials**: Not tracked in git - create locally for both apps
- **Dual PHP versions**: Docker handles this locally; production uses nginx with different PHP-FPM sockets

---

## Migration Reference

For detailed migration instructions, phases, and deployment checklists, see:
**`LARAVEL_MIGRATION_PLAN.md`**

Key migration principles:
- One controller at a time
- Preserve existing database schema
- Share authentication between apps
- Add tests for each migrated feature
- Easy rollback via nginx routing
