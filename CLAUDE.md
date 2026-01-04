# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Skivsamlingen.se is a Swedish record collection management website created around 2007. It's a community-driven platform where users can catalog their vinyl records, browse other users' collections, and discover music statistics.

The codebase is built with **CodeIgniter 2.x** (legacy PHP framework) and uses Swedish for user-facing content and database fields.

## Development Environment

This is a traditional PHP application without modern build tools. Development requires:
- PHP (version compatible with CodeIgniter 2.x)
- MySQL database
- Apache web server with mod_rewrite enabled

**No build step, test runner, or package manager is configured.**

To run the application locally:
1. Point your web server to the repository root
2. Configure database credentials in `application/config/database.php` (not tracked in git)
3. Ensure `.htaccess` mod_rewrite rules are active
4. Set `ENVIRONMENT` in `index.php` (currently set to 'development')

## Architecture & Code Structure

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
  - `news_controller.php` - News/announcements

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
- Can use `$asides` for sidebar/footer partials

**Custom Libraries** (`application/libraries/`)
- `Auth.php` - Authentication with session and persistent login (cookie-based "remember me")
- `MP_Cache.php` - Caching layer
- `History.php` - Navigation history tracking
- `Notice.php` / `Notification.php` - Flash messages
- `MY_Form_Validation.php`, `MY_Pagination.php` - Framework extensions

### Database Schema

The database has these key tables (structure inferred from code):
- `users` - User accounts (username, password, email, birth, sex, about, etc.)
- `artists` - Artist names
- `records` - Records (title, year, format, artist_id)
- `records_users` - Junction table linking users to their record collections (includes comment field)
- `persistent_logins` - "Remember me" authentication tokens
- `donations` - Tracks supporter status (users who donated ≥100 within past year)
- `news` - Site announcements
- `comments`, `messages` - Social features

### Routing

Routes are defined in `application/config/routes.php`:
- Default controller: `home_controller`
- Pattern `$route['([^/]*)(.*)'] = "$1_controller$2"` automatically maps URLs to controllers
- Custom routes for users: `/users/{username}`, `/users/{username}/print`, `/users/{username}/export`
- Apache `.htaccess` removes `index.php` from URLs via mod_rewrite

### Authentication & Authorization

The `Auth` library handles authentication:
- Session-based login stored in `$_SESSION['username']` and `$_SESSION['user_id']`
- Persistent login via cookie `skiv_remember` (30-day expiry) stored in `persistent_logins` table
- Password encryption: `sha256(md5(username)[0:12] + password + global_salt)`
  - Old passwords use `sha1()` and are automatically upgraded on login
- Access control: Controllers check `$this->auth->isUser()` / `$this->auth->isGuest()`
- Supporter status: Users who donated ≥100 SEK in the past year (`User::isSupporter()`)

### Key Conventions

1. **Swedish language**: Database fields, validation messages, and UI text are in Swedish
   - `användarnamn` = username, `lösenord` = password, `e-post` = email
   - `kön` = gender (f/m/x), `namn` = name, `om mig` = about me

2. **Validation**: Models define validation in `$fields` array:
   ```php
   array('field_name', 'Human Label', 'validation_rules')
   ```

3. **Flash messages**: Use `$this->notice->success()` / `$this->notice->error()` for user feedback

4. **History tracking**: `$this->history->exclude()` prevents pages from being added to navigation history

5. **Caching**: Statistics on homepage are cached for 1 hour via `MP_Cache`

## Important Notes

- **Legacy codebase**: Code quality varies significantly (written ~2007, mixed with newer code)
- **No tests**: There is no test suite
- **No dependency management**: Libraries (jQuery 1.4, jQuery UI 1.8) are committed directly
- **Security considerations**:
  - Uses custom password hashing (not bcrypt/modern standards)
  - SQL injection protected by CodeIgniter's query builder
  - XSS protection via validation rules (`xss_clean`, `strip_tags`)
- **Database credentials**: `application/config/database.php` is not tracked in git (create locally)
- **Main branch**: `master` (not `main`)

## Common Patterns

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

**Validation:**
```php
$this->Model->validate();  // Validates all fields from POST
$this->Model->validateData($data);  // Validates only provided fields
```
