# CodeIgniter to Laravel Migration Plan: Skivsamlingen

## Migration Approach
- **Strategy**: Fresh Laravel 10.x app in new branch (`laravel-migration`)
- **Database**: Preserve existing schema (no renaming, no timestamps)
- **Authentication**: Replicate custom auth with persistent login tokens
- **Testing**: Add tests during migration for each feature
- **Deployment**: Single cutover (no parallel development)

## Project Context

**Application**: Skivsamlingen.se - Swedish vinyl record collection management site (created ~2007)
- Built on CodeIgniter 2.x with extensive custom extensions
- 6 controllers, 10 models, complex authentication system
- Database: 10 tables with ~15,000 lines of PHP code
- Features: User registration, record collection CRUD, XML import/export, search, news/RSS

**Key Technical Challenges**:
- Custom password hashing: `sha256(md5(username)[0:12] + password + global_salt)` with SHA1 legacy support
- Persistent login with token rotation and replay attack detection
- Case-sensitive record matching (COLLATE utf8_bin)
- "The" prefix stripping in artist sorting
- Swedish language throughout (field names, validation messages)
- Rate-limited XML import (1/hour) with two format support
- Complex statistics queries with subqueries

---

## Phase 0: Foundation & Setup (2-3 days)

### Objective
Set up Laravel and establish database connectivity without schema changes.

### Tasks
1. **Create Laravel App**
   - Branch: `git checkout -b laravel-migration`
   - Install: `composer create-project laravel/laravel . "10.*"`
   - Files: Standard Laravel structure

2. **Configure Database**
   - **File**: `/config/database.php`
     - Set `'strict' => false` to allow existing schema flexibility
   - **File**: `/.env`
     - Configure MySQL credentials
     - Add `AUTH_GLOBAL_SALT` (must match CodeIgniter config)

3. **Disable Timestamp Expectations**
   - **File**: `/app/Models/BaseModel.php`
     ```php
     abstract class BaseModel extends Model {
         public $timestamps = false; // Critical!
     }
     ```
   - All models will extend this base

4. **Install Dependencies**
   ```bash
   composer require --dev barryvdh/laravel-debugbar
   composer require --dev pestphp/pest pestphp/pest-plugin-laravel
   ```

### Verification
- [ ] Database connection successful
- [ ] Can query existing tables
- [ ] Laravel welcome page loads

---

## Phase 1: Core Infrastructure (1 week)

### Objective
Build Laravel equivalents of CodeIgniter's custom extensions.

### 1.1 Base Controller (Auto-View Loading)
**File**: `/app/Http/Controllers/BaseController.php`

Replicate MY_Controller's automatic view rendering and layout system:
```php
abstract class BaseController extends Controller {
    protected $data = ['page_title' => 'Skivsamlingen'];
    protected $view = null;
    protected $layout = 'layouts.application';
    protected $skipView = false;

    public function callAction($method, $parameters) {
        $response = parent::callAction($method, $parameters);
        if (!$this->skipView && is_null($response)) {
            return $this->renderView();
        }
        return $response;
    }

    protected function renderView() {
        $viewPath = $this->view ?? $this->getDefaultViewPath();
        return view($viewPath, $this->data);
    }

    protected function pass() { $this->skipView = true; }
    protected function isAjax() { return request()->ajax(); }
}
```

### 1.2 Base Model (Flexible Where Clauses)
**File**: `/app/Models/BaseModel.php`

Add CRUD helpers and flexible where handling:
```php
abstract class BaseModel extends Model {
    public $timestamps = false;

    public static function fetchOne($where) {
        return static::applyFlexibleWhere(static::query(), $where)->first();
    }

    protected static function applyFlexibleWhere($query, $where) {
        if (is_array($where)) {
            foreach ($where as $field => $value) {
                $query->where($field, $value);
            }
        } else {
            $query->where('id', $where);
        }
        return $query;
    }
}
```

### 1.3 Flash Message System (Notice)
**File**: `/app/Services/NoticeService.php`
```php
class NoticeService {
    public function success(string $message, string $key = 'flash') {
        session()->flash($key, ['type' => 'success', 'content' => $message]);
    }
    public function error(string $message, string $key = 'flash') {
        session()->flash($key, ['type' => 'error', 'content' => $message]);
    }
    public function info(string $message, string $key = 'flash') {
        session()->flash($key, ['type' => 'notice', 'content' => $message]);
    }
}
```

### 1.4 Navigation History Stack
**Files**:
- `/app/Services/HistoryService.php` - LIFO stack (last 5 URLs)
- `/app/Http/Middleware/TrackHistory.php` - Auto-push middleware

### 1.5 Custom Validation Rules
**File**: `/app/Providers/ValidationServiceProvider.php` or boot() in AppServiceProvider

Register custom validators:
- `alpha_dash_dot` - Alphanumeric + underscores, dashes, dots
- `valid_date` - Date format validation
- `numeric_max`/`numeric_min` - Numeric ranges
- `in_list` - Value in comma-separated list
- `unique` (enhanced) - Database uniqueness check

### 1.6 Custom Pagination Renderer
**File**: `/resources/views/vendor/pagination/skivsamlingen.blade.php`

UL-based pagination matching CodeIgniter's MY_Pagination output.

### Tests
- [ ] Unit: NoticeService
- [ ] Unit: HistoryService
- [ ] Unit: All custom validation rules
- [ ] Integration: BaseController view rendering

---

## Phase 2: Models & Eloquent (1 week)

### Objective
Create all Eloquent models preserving exact database structure.

### Key Models

#### 2.1 User Model
**File**: `/app/Models/User.php`

```php
class User extends Authenticatable {
    public $timestamps = false;
    protected $table = 'users';
    protected $fillable = ['username', 'password', 'email', 'public_email',
                          'name', 'birth', 'about', 'sex', 'per_page'];
    protected $hidden = ['password'];
    protected $casts = [
        'registered' => 'datetime',
        'per_page' => 'integer',
        'public_email' => 'boolean',
    ];

    // Relationships
    public function records() {
        return $this->belongsToMany(Record::class, 'records_users')
                    ->withPivot('id', 'comment')
                    ->using(RecordUser::class);
    }

    // Business logic
    public function isSupporter(): bool {
        $oneYearAgo = now()->subYear();
        return $this->donations()
                    ->where('donated_at', '>=', $oneYearAgo)
                    ->where('amount', '>=', 100)
                    ->exists();
    }

    public function encryptPassword(string $password): string {
        $presalt = substr(md5($this->username), 0, 12);
        return hash('sha256', $presalt . $password . config('auth.global_salt'));
    }
}
```

#### 2.2 Artist Model
**File**: `/app/Models/Artist.php`

```php
class Artist extends BaseModel {
    protected $fillable = ['name'];

    public static function getOrCreateId(string $name): int {
        $artist = static::firstOrCreate(['name' => $name]);
        return $artist->id;
    }
}
```

#### 2.3 Record Model
**File**: `/app/Models/Record.php`

**Critical**: Case-sensitive title matching
```php
class Record extends BaseModel {
    protected $fillable = ['artist_id', 'title', 'year', 'format'];

    public static function getOrCreateId(int $artistId, string $title,
                                        ?int $year, ?string $format): int {
        $record = static::where('artist_id', $artistId)
                       ->whereRaw('title COLLATE utf8_bin = ?', [$title])
                       ->where('year', $year)
                       ->where('format', $format)
                       ->first();

        return $record ? $record->id : static::create([
            'artist_id' => $artistId,
            'title' => $title,
            'year' => $year,
            'format' => $format,
        ])->id;
    }
}
```

#### 2.4 Other Models
Create models for:
- `RecordUser` (pivot) - `/app/Models/RecordUser.php`
- `PersistentLogin` - `/app/Models/PersistentLogin.php`
- `PasswordRecovery` - `/app/Models/PasswordRecovery.php`
- `Donation` - `/app/Models/Donation.php`
- `Message` - `/app/Models/Message.php`
- `MessageUser` - `/app/Models/MessageUser.php`
- `News` - `/app/Models/News.php`

### Tests
- [ ] Unit: User::isSupporter()
- [ ] Unit: User::encryptPassword()
- [ ] Unit: Artist::getOrCreateId()
- [ ] Unit: Record::getOrCreateId() with case-sensitivity
- [ ] Test all Eloquent relationships

---

## Phase 3: Authentication System (1.5 weeks)

### Objective
Replicate custom Auth library with token rotation and legacy password support.

### 3.1 Authentication Service
**File**: `/app/Services/AuthService.php`

**Critical Features**:
1. **Login** with legacy SHA1 auto-upgrade:
```php
public function login(string $username, string $password): bool {
    $user = User::where('username', $username)->first();
    if (!$user) return false;

    // Check legacy SHA1 and upgrade
    if ($user->password === sha1($password)) {
        $user->password = $user->encryptPassword($password);
        $user->save();
        Auth::login($user);
        return true;
    }

    // Check SHA256
    if ($user->password === $user->encryptPassword($password)) {
        Auth::login($user);
        return true;
    }

    return false;
}
```

2. **Persistent Login** with token rotation:
```php
public function remember(): void {
    $token = mt_rand() + mt_rand();
    $persist = [
        'user_id' => Auth::id(),
        'series' => sha1(mt_rand()),
        'token' => $token,
    ];

    PersistentLogin::create($persist);
    Cookie::queue('skiv_remember', implode(';', $persist), 60*24*30);
}
```

3. **Replay Attack Detection**:
```php
public function validateCookie(): bool {
    [$userId, $series, $token] = explode(';', Cookie::get('skiv_remember'));
    $persist = PersistentLogin::where('user_id', $userId)
                              ->where('series', $series)->first();

    if (!$persist) return false;

    // Token mismatch = attack!
    if ($persist->token != $token) {
        app(NoticeService::class)->error('Ett försök att hacka...');
        PersistentLogin::where('user_id', $userId)->delete();
        return false;
    }

    // Valid - rotate token
    Auth::login(User::find($userId));
    $newToken = mt_rand() + mt_rand();
    $persist->update(['token' => $newToken]);
    Cookie::queue('skiv_remember', "$userId;$series;$newToken", 60*24*30);

    return true;
}
```

### 3.2 Persistent Login Middleware
**File**: `/app/Http/Middleware/PersistentLogin.php`

Validate cookie before each request.

### 3.3 Password Recovery Service
**File**: `/app/Services/PasswordRecoveryService.php`

- Generate SHA1 hash, store in DB with timestamp
- Send recovery email
- 48-hour expiration
- Only one active recovery per user

### 3.4 Configuration
**File**: `/config/auth.php`
```php
'global_salt' => env('AUTH_GLOBAL_SALT', ''),
```

### Tests
- [ ] Unit: Legacy SHA1 password upgrade
- [ ] Unit: SHA256 password verification
- [ ] Unit: Persistent login token creation
- [ ] Unit: Token rotation
- [ ] Unit: Replay attack detection
- [ ] Integration: Full login flow
- [ ] Integration: Password recovery flow

---

## Phase 4: Public Features (2 weeks)

### Controllers to Build

#### 4.1 HomeController
**File**: `/app/Http/Controllers/HomeController.php`
- `index()` - Homepage with cached statistics (1 hour TTL)
- `about()` - About page
- `unregistered()` - Account deletion notification

**Critical**: Use `/app/Services/StatisticsService.php` for complex queries:
- Top users by collection size
- Member statistics (this week, last week, total)
- Top artists (exclude 'Various', 'V/A')
- Popular albums
- Latest records

#### 4.2 UsersController
**File**: `/app/Http/Controllers/UsersController.php`

**Critical Methods**:
1. `profile($username)` - User collection with pagination/sorting
   - Sort by artist/year/format
   - Strip "The" prefix: `ORDER BY TRIM(LEADING 'The ' FROM artists.name)`
   - Subquery for artist record count per user
   - Show supporter badge

2. `search($query)` - AJAX (JSON) or HTML response
   - AJAX: Return `[{label, type}]` for autocomplete
   - Limit to 6 results in AJAX, 20 in HTML

3. `export($username)` - XML download
   - Filename: `skivsamling-YYYYMMDD.xml`
   - Include artist, title, year, format

4. `printview($username)` - Print-friendly view

#### 4.3 NewsController
**File**: `/app/Http/Controllers/NewsController.php`
- `index()` - Paginated news (5 per page)
- `rss()` - RSS 2.0 feed (5 latest)

### Views
Create Blade templates for all controllers in:
- `/resources/views/home/`
- `/resources/views/users/`
- `/resources/views/news/`
- `/resources/views/layouts/application.blade.php`

### Tests
- [ ] Feature: Homepage loads with statistics
- [ ] Feature: User profile pagination/sorting
- [ ] Feature: "The" prefix stripping works
- [ ] Feature: AJAX search returns JSON
- [ ] Feature: XML export valid
- [ ] Feature: News pagination
- [ ] Feature: RSS feed valid

---

## Phase 5: Collection Management (1.5 weeks)

### 5.1 Collection Controller
**File**: `/app/Http/Controllers/CollectionController.php`

All routes require authentication.

#### Add/Edit Record
**Method**: `record($id = 0)`
**Request**: `/app/Http/Requests/RecordRequest.php`

Validation rules:
```php
'artist' => 'required|max:64',
'title' => 'required|max:150',
'year' => 'nullable|integer|digits:4|min:1',
'format' => 'nullable|max:30',
'comment' => 'nullable|max:255',
```

**Logic**:
1. Get or create artist via `Artist::getOrCreateId()`
2. Get or create record via `Record::getOrCreateId()`
3. Add to `records_users` with comment
4. If editing (id > 0), delete old entry first

#### Delete Record
**Method**: `delete($recordId)`
- Show confirmation page (GET)
- Delete from `records_users` (POST)

#### Comment Management
**Method**: `comment($recordId)`
- Edit or delete comment on collection item
- Update `records_users.comment` field

### 5.2 XML Import Service
**File**: `/app/Services/XmlImportService.php`

**Critical Features**:
1. **Rate Limiting**: Max 1 import per hour (check `users.last_import`)
2. **Two Format Support**:
   - Skivsamlingen: `<collection><record><artist>...`
   - pop.nu: `<recordcollection><record><year_release>...`
3. **Validation**:
   - Truncate artist (64), title (150), format (30)
   - Validate year (4 digits, numeric)
   - XSS clean with `strip_tags()`
4. **Process**:
   - Delete user's existing collection first
   - Parse XML with XMLReader
   - Create artist/record/collection entries
   - Update `users.last_import`

**Method**: `import(User $user, string $xmlPath): array`

### Tests
- [ ] Feature: Add new record
- [ ] Feature: Edit record
- [ ] Feature: Delete record
- [ ] Feature: Comment CRUD
- [ ] Unit: XML import (Skivsamlingen format)
- [ ] Unit: XML import (pop.nu format)
- [ ] Unit: Import rate limiting
- [ ] Unit: Field truncation
- [ ] Feature: Full import flow

---

## Phase 6: Social Features (3 days)

### Notification System
**File**: `/app/Services/NotificationService.php`

Database notifications for admin messages to users.

**Methods**:
```php
public function createMessage(string $message, ?string $whereClause): int {
    $messageId = Message::create(['message' => $message])->id;

    // Insert for filtered users
    $query = "INSERT INTO messages_users (message_id, user_id)
              SELECT $messageId, id FROM users";
    if ($whereClause) {
        $query .= " WHERE $whereClause";
    }
    DB::statement($query);

    return $messageId;
}
```

**Example Usage**:
```php
// Notify users without email
$notification->createMessage(
    'Det är starkt rekommenderat att ange sin e-postadress...',
    'email IS NULL'
);
```

### Middleware
**File**: `/app/Http/Middleware/LoadNotifications.php`

Load user notifications and share with all views.

### Tests
- [ ] Unit: createMessage with/without where clause
- [ ] Unit: markAsRead
- [ ] Feature: Notifications display

---

## Phase 7: Testing & Validation (2 weeks, parallel)

### Test Infrastructure

#### Factories
**Files**: `/database/factories/*Factory.php`

Create factories for all models:
- `UserFactory` - Include `supporter()` state
- `ArtistFactory`
- `RecordFactory`
- `NewsFactory`
- `DonationFactory`

#### Feature Tests (50+ tests)
- Authentication: Login, registration, persistent login, password recovery
- Collection: Add/edit/delete, comments, import
- Users: Profile, search, export
- News: List, RSS

#### Unit Tests (30+ tests)
- Services: AuthService, StatisticsService, XmlImportService, NotificationService
- Models: Business logic methods
- Validation: All custom rules

#### Coverage Goals
- Overall: 80%+
- Critical paths (auth, collection): 95%+

### Test Commands
```bash
php artisan test
php artisan test --coverage
php artisan test --coverage-html coverage
```

---

## Phase 8: Deployment (1 week)

### 8.1 Configuration

**Production .env**:
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://skivsamlingen.se
AUTH_GLOBAL_SALT=*** (must match CodeIgniter!)
```

### 8.2 Routes
**File**: `/routes/web.php`

Define all routes with proper naming:
- Home: `/`, `/about`
- Account: `/account/register`, `/account/login`, etc.
- Collection: `/collection/record`, `/collection/delete`, etc.
- Users: `/users/{username}`, `/users/search`, etc.
- News: `/news/{page}`, `/news/rss`

### 8.3 Optimization
**Deployment Script**: `/deploy.sh`
```bash
composer install --optimize-autoloader --no-dev
php artisan config:cache
php artisan route:cache
php artisan view:cache
chmod -R 775 storage bootstrap/cache
```

### 8.4 Cutover Plan
**File**: `/CUTOVER_PLAN.md`

**Steps** (30 minutes estimated):
1. Enable maintenance mode (2 min)
2. Backup database (5 min)
3. Deploy Laravel (10 min)
4. Update web server config to point to `/public` (5 min)
5. Verification smoke tests (5 min)
6. Go live (3 min)

**Rollback**: Restore DB backup + revert to CodeIgniter (~10 min)

### 8.5 Verification Checklist
- [ ] Homepage loads
- [ ] Login works
- [ ] Add record works
- [ ] User profile loads
- [ ] Search works
- [ ] XML export works
- [ ] News/RSS works

---

## Critical Files Summary

These 10 files are the foundation of the migration:

1. `/app/Models/User.php` - Core user model with auth logic
2. `/app/Services/AuthService.php` - Custom auth with persistent login
3. `/app/Http/Controllers/BaseController.php` - Auto-view loading pattern
4. `/app/Http/Controllers/CollectionController.php` - Collection CRUD + import
5. `/app/Services/XmlImportService.php` - Complex import logic
6. `/app/Models/Record.php` - Case-sensitive matching
7. `/app/Services/StatisticsService.php` - Complex queries
8. `/config/database.php` - Must have `strict => false`
9. `/routes/web.php` - All route definitions
10. `/app/Models/BaseModel.php` - `timestamps = false` for all models

---

## Success Criteria

Migration is complete when:
- [ ] All 100+ tests passing
- [ ] All features from CodeIgniter replicated
- [ ] Database schema unchanged
- [ ] Authentication works (including persistent login)
- [ ] Performance within 20% of CodeIgniter
- [ ] Cutover successful with <1 hour downtime
- [ ] No critical bugs in first 48 hours

---

## Timeline: 8-12 weeks (full-time)

**Week 1-2**: Phase 0-1 (Foundation + Infrastructure)
**Week 3-4**: Phase 2-3 (Models + Auth)
**Week 5-6**: Phase 4 (Public Features)
**Week 7**: Phase 5 (Collection)
**Week 8**: Phase 6 (Social)
**Week 9-10**: Phase 7 (Testing) - parallel with above
**Week 11-12**: Phase 8 (Deployment prep + cutover)

Start testing early and continuously!

---

## High-Risk Areas

### 1. Custom Password Hashing
- Must preserve exact algorithm
- Must support legacy SHA1 upgrade
- AUTH_GLOBAL_SALT must match exactly
- **Risk**: If wrong, all users locked out

### 2. Case-Sensitive Title Matching
- MySQL collation behavior must match
- Affects record deduplication
- **Risk**: Duplicate records or data loss

### 3. XML Import
- Two format support
- Rate limiting (1/hour)
- Clears existing collection before import
- **Risk**: Data loss if import fails mid-process

### 4. Persistent Login Token Rotation
- Token rotation on each use
- Replay attack detection
- **Risk**: Security vulnerability if implemented incorrectly

---

## Notes for Implementation

### Database Configuration
The CodeIgniter config uses a global salt that must be preserved. Find it in:
- CodeIgniter: Look for global salt in config files
- Add to Laravel .env: `AUTH_GLOBAL_SALT=<exact_value>`

### Views Migration
The plan doesn't detail view migration. Current frontend uses:
- jQuery 1.4.2
- jQuery UI 1.8.1
- Custom CSS

Decision needed: Port as-is or modernize?

### Account Controller
Missing from detailed phases. Should be added as Phase 3.5:
- Registration with CAPTCHA
- Login/logout
- Password recovery
- Profile settings
- Account deletion

### Static Assets
Plan should include migration of:
- `/static/images/`
- `/static/scripts/`
- `/static/styles/`

To Laravel's `/public/` directory.

---

## Questions to Answer Before Starting

1. **Where is the CodeIgniter global salt stored?**
2. **Do you have a staging/dev environment for testing?**
3. **Can you export an anonymized database for development?**
4. **What's your timeline/availability? (affects whether 8-12 weeks is realistic)**
5. **Should we modernize jQuery or keep as-is?**
6. **Do you want a gradual rollout or single cutover?**

---

*This plan was generated by Claude Code on 2026-01-04*
*Based on analysis of the Skivsamlingen CodeIgniter codebase*
