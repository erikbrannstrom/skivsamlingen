# CodeIgniter to Laravel Migration Plan: Skivsamlingen

## Migration Approach
- **Strategy**: Incremental controller-by-controller migration with parallel deployment
- **Database**: Preserve existing schema (no renaming, no timestamps)
- **Authentication**: Shared session/cookie authentication between CodeIgniter and Laravel
- **Testing**: Add tests during migration for each feature
- **Deployment**: Deploy each controller independently, route traffic progressively

## Why Incremental Migration?

The incremental approach offers significant advantages over a single cutover:

1. **Reduced Risk**: Only one controller is at risk at any time
2. **Easy Rollback**: Revert routing for a single feature without affecting others
3. **Continuous Delivery**: Production value delivered early and often
4. **Parallel Development**: CodeIgniter can still be updated during migration
5. **Validation**: Real traffic validates each migrated feature before proceeding

---

## Project Context

**Application**: Skivsamlingen.se - Swedish vinyl record collection management site (created ~2007)
- Built on CodeIgniter 2.x with extensive custom extensions
- 6 controllers, 10 models, complex authentication system
- Database: 10 tables with ~15,000 lines of PHP code
- Features: User registration, record collection CRUD, XML export, search, news/RSS

**Key Technical Challenges**:
- **Dual PHP versions**: CodeIgniter runs on PHP 5.6, Laravel 10 requires PHP 8.1+
- Custom password hashing: `sha256(md5(username)[0:12] + password + global_salt)` with SHA1 legacy support
- Persistent login with token rotation and replay attack detection
- Case-sensitive record matching (COLLATE utf8_bin)
- "The" prefix stripping in artist sorting
- Swedish language throughout (field names, validation messages)
- Complex statistics queries with subqueries

---

## Migration Order

Controllers are migrated in order of increasing complexity and dependency:

| Order | Controller | Risk Level | Dependencies |
|-------|-----------|------------|--------------|
| 1 | NewsController | Low | None |
| 2 | HomeController | Low | Statistics queries |
| 3 | UsersController | Medium | User model, read-only |
| 4 | AccountController | High | Auth system |
| 5 | CollectionController | High | Auth + full CRUD |

---

## Phase 0: Foundation & Parallel Architecture

### Objective
Set up Laravel alongside CodeIgniter with shared database and session handling.

### 0.1 Directory Structure
```
/var/www/skivsamlingen.se/
├── application/          # Existing CodeIgniter app
├── system/
├── static/
├── index.php             # CodeIgniter entry point
└── laravel/              # New Laravel app (added)
    ├── app/
    ├── public/
    └── ...
```

**Note**: Laravel is installed as a subdirectory within the existing CodeIgniter structure. No existing files are moved or modified. nginx handles routing via location blocks in the server config.

### 0.2 Install PHP 8.1 (Dual PHP Setup)

CodeIgniter requires PHP 5.6, but Laravel 10 requires PHP 8.1+. Both versions must run simultaneously during migration.

**Install PHP 8.1 alongside PHP 5.6:**
```bash
# Add PHP repository (Ubuntu/Debian)
sudo add-apt-repository ppa:ondrej/php
sudo apt update

# Install PHP 8.1 with required extensions
sudo apt install php8.1-fpm php8.1-mysql php8.1-xml php8.1-mbstring php8.1-curl php8.1-zip

# Verify both PHP-FPM services are running
sudo systemctl status php5.6-fpm
sudo systemctl status php8.1-fpm
```

**PHP-FPM sockets:**
- PHP 5.6: `/var/run/php/php5.6-fpm.sock` (CodeIgniter)
- PHP 8.1: `/var/run/php/php8.1-fpm.sock` (Laravel)

### 0.3 Create Laravel App
```bash
cd /var/www/skivsamlingen.se
composer create-project laravel/laravel laravel "10.*"
```

### 0.4 Smart Router (nginx)
**File**: `/etc/nginx/sites-available/skivsamlingen.conf`

Route specific URLs to Laravel (PHP 8.1) while keeping others on CodeIgniter (PHP 5.6):
```nginx
server {
    listen 80;
    server_name skivsamlingen.se;
    root /var/www/skivsamlingen.se;
    index index.php;

    # Static assets - serve directly
    location /static/ {
        expires 30d;
    }

    # Laravel routes (uncomment as controllers are deployed)
    # location /news {
    #     try_files $uri $uri/ @laravel;
    # }

    # Laravel handler - uses PHP 8.1
    location @laravel {
        rewrite ^(.*)$ /laravel/public/index.php?$query_string last;
    }

    # Laravel PHP processing (PHP 8.1)
    location ~ ^/laravel/public/index\.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME /var/www/skivsamlingen.se/laravel/public/index.php;
        include fastcgi_params;
    }

    # Default: CodeIgniter handles everything else
    location / {
        try_files $uri $uri/ /index.php?$uri&$args;
    }

    # CodeIgniter PHP processing (PHP 5.6)
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php5.6-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

**Important**: The order of location blocks matters. The Laravel-specific PHP location must come before the general `.php` location to ensure Laravel uses PHP 8.1 while CodeIgniter uses PHP 5.6.

### 0.5 Shared Session Authentication

Both apps must share authentication state by allowing Laravel middleware to read CodeIgniter's session cookie directly:

**File**: `/app/Http/Middleware/SharedAuth.php`
```php
class SharedAuth {
    public function handle($request, $next) {
        // Read CodeIgniter session from database
        $sessionId = $request->cookie('ci_session');
        if ($sessionId) {
            $session = DB::table('ci_sessions')
                        ->where('session_id', $sessionId)
                        ->first();
            if ($session) {
                $data = unserialize($session->user_data);
                if (isset($data['user_id'])) {
                    Auth::loginUsingId($data['user_id']);
                }
            }
        }
        return $next($request);
    }
}
```

### 0.6 Configure Database
**File**: Laravel `/config/database.php`
- Set `'strict' => false` for existing schema flexibility
- Same credentials as CodeIgniter

**File**: Laravel `/.env`
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=skivsamlingen
DB_USERNAME=***
DB_PASSWORD=***
AUTH_GLOBAL_SALT=*** (must match CodeIgniter!)
```

### 0.7 Base Model (No Timestamps)
**File**: `/app/Models/BaseModel.php`
```php
abstract class BaseModel extends Model {
    public $timestamps = false;
}
```

### Verification
- [ ] PHP 5.6-FPM and PHP 8.1-FPM both running
- [ ] Laravel app loads at test URL (verify PHP 8.1 via `phpinfo()`)
- [ ] CodeIgniter continues to work (verify PHP 5.6 via `phpinfo()`)
- [ ] Both apps can read the database
- [ ] Session/auth state shared between apps

---

## Phase 1: NewsController Migration

### Objective
Migrate the simplest controller first to validate the parallel architecture.

### 1.1 Create Laravel Components

**Model**: `/app/Models/News.php`
```php
class News extends BaseModel {
    protected $table = 'news';
    protected $fillable = ['title', 'content', 'created_at'];
    protected $casts = ['created_at' => 'datetime'];
}
```

**Controller**: `/app/Http/Controllers/NewsController.php`
```php
class NewsController extends Controller {
    public function index() {
        $news = News::orderBy('created_at', 'desc')
                    ->paginate(5);
        return view('news.index', compact('news'));
    }

    public function rss() {
        $news = News::orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get();
        return response()
            ->view('news.rss', compact('news'))
            ->header('Content-Type', 'application/rss+xml');
    }
}
```

**Views**:
- `/resources/views/news/index.blade.php`
- `/resources/views/news/rss.blade.php`
- `/resources/views/layouts/application.blade.php`

**Routes**: `/routes/web.php`
```php
Route::get('/news', [NewsController::class, 'index']);
Route::get('/news/rss', [NewsController::class, 'rss']);
```

### 1.2 Testing
```bash
php artisan test --filter=NewsTest
```

Tests to write:
- [ ] News list pagination works
- [ ] RSS feed is valid XML
- [ ] Empty news state handled

### 1.3 Deploy NewsController

1. **Deploy Laravel code** to production server
2. **Update nginx config** to route `/news` to Laravel:
   ```nginx
   # Add before the default CodeIgniter location block
   location /news {
       try_files $uri $uri/ @laravel;
   }
   ```
3. **Reload nginx**: `sudo nginx -s reload`
4. **Verify** news pages work
5. **Monitor** for errors for 24-48 hours

### Rollback Plan
Comment out the news location block and reload nginx to instantly revert to CodeIgniter.

---

## Phase 2: HomeController Migration

### Objective
Migrate the homepage and statistics, which are read-only but involve complex queries.

### 2.1 Create Laravel Components

**Service**: `/app/Services/StatisticsService.php`
```php
class StatisticsService {
    public function getTopUsers(int $limit = 10): Collection {
        return DB::table('records_users')
            ->select('users.username', DB::raw('COUNT(*) as count'))
            ->join('users', 'users.id', '=', 'records_users.user_id')
            ->groupBy('users.id', 'users.username')
            ->orderByDesc('count')
            ->limit($limit)
            ->get();
    }

    public function getTopArtists(int $limit = 10): Collection {
        return DB::table('records')
            ->select('artists.name', DB::raw('COUNT(*) as count'))
            ->join('artists', 'artists.id', '=', 'records.artist_id')
            ->join('records_users', 'records.id', '=', 'records_users.record_id')
            ->whereNotIn('artists.name', ['Various', 'V/A', 'Div.'])
            ->groupBy('artists.id', 'artists.name')
            ->orderByDesc('count')
            ->limit($limit)
            ->get();
    }

    public function getMemberStats(): array {
        return Cache::remember('member_stats', 3600, function() {
            $now = now();
            return [
                'total' => User::count(),
                'this_week' => User::where('registered', '>=', $now->startOfWeek())->count(),
                'last_week' => User::whereBetween('registered', [
                    $now->copy()->subWeek()->startOfWeek(),
                    $now->copy()->subWeek()->endOfWeek()
                ])->count(),
            ];
        });
    }
}
```

**Controller**: `/app/Http/Controllers/HomeController.php`
```php
class HomeController extends Controller {
    public function __construct(private StatisticsService $stats) {}

    public function index() {
        return view('home.index', [
            'topUsers' => Cache::remember('top_users', 3600,
                fn() => $this->stats->getTopUsers()),
            'topArtists' => Cache::remember('top_artists', 3600,
                fn() => $this->stats->getTopArtists()),
            'memberStats' => $this->stats->getMemberStats(),
        ]);
    }

    public function about() {
        return view('home.about');
    }
}
```

**Views**:
- `/resources/views/home/index.blade.php`
- `/resources/views/home/about.blade.php`

**Routes**:
```php
Route::get('/', [HomeController::class, 'index']);
Route::get('/about', [HomeController::class, 'about']);
```

### 2.2 Testing
- [ ] Homepage loads with statistics
- [ ] Statistics are cached for 1 hour
- [ ] About page renders

### 2.3 Deploy HomeController

1. Deploy Laravel code
2. Update nginx config:
   ```nginx
   # Homepage - exact match takes priority
   location = / {
       try_files $uri @laravel;
   }

   location /about {
       try_files $uri $uri/ @laravel;
   }
   ```
3. Reload nginx: `sudo nginx -s reload`
4. Verify and monitor

---

## Phase 3: UsersController Migration

### Objective
Migrate user profiles, search, and export (read-only, but user-facing).

### 3.1 Create Laravel Components

**Models**:
- `/app/Models/User.php`
- `/app/Models/Artist.php`
- `/app/Models/Record.php`
- `/app/Models/RecordUser.php` (pivot)
- `/app/Models/Donation.php`

**User Model** (key parts):
```php
class User extends Authenticatable {
    use HasFactory;
    public $timestamps = false;
    protected $fillable = ['username', 'password', 'email', ...];
    protected $hidden = ['password'];

    public function records() {
        return $this->belongsToMany(Record::class, 'records_users')
                    ->withPivot('id', 'comment')
                    ->using(RecordUser::class);
    }

    public function isSupporter(): bool {
        return $this->donations()
            ->where('donated_at', '>=', now()->subYear())
            ->where('amount', '>=', 100)
            ->exists();
    }
}
```

**Controller**: `/app/Http/Controllers/UsersController.php`
```php
class UsersController extends Controller {
    public function profile(string $username) {
        $user = User::where('username', $username)->firstOrFail();

        $records = $user->records()
            ->join('artists', 'records.artist_id', '=', 'artists.id')
            ->select('records.*', 'artists.name as artist_name', 'records_users.comment')
            ->orderByRaw("TRIM(LEADING 'The ' FROM artists.name)")
            ->paginate($user->per_page ?: 25);

        return view('users.profile', compact('user', 'records'));
    }

    public function search(Request $request) {
        $query = $request->input('q');
        $users = User::where('username', 'LIKE', "%{$query}%")
                     ->limit($request->ajax() ? 6 : 20)
                     ->get();

        if ($request->ajax()) {
            return response()->json(
                $users->map(fn($u) => ['label' => $u->username, 'type' => 'user'])
            );
        }

        return view('users.search', compact('users', 'query'));
    }

    public function export(string $username) {
        $user = User::where('username', $username)->firstOrFail();
        $records = $user->records()->with('artist')->get();

        return response()
            ->view('users.export', compact('records'))
            ->header('Content-Type', 'application/xml')
            ->header('Content-Disposition',
                'attachment; filename="skivsamling-' . date('Ymd') . '.xml"');
    }

    public function printview(string $username) {
        $user = User::where('username', $username)->firstOrFail();
        $records = $user->records()->with('artist')->get();

        return view('users.print', compact('user', 'records'));
    }
}
```

**Routes**:
```php
Route::get('/users/search', [UsersController::class, 'search']);
Route::get('/users/{username}', [UsersController::class, 'profile']);
Route::get('/users/{username}/export', [UsersController::class, 'export']);
Route::get('/users/{username}/print', [UsersController::class, 'printview']);
```

### 3.2 Testing
- [ ] User profile pagination works
- [ ] "The" prefix stripping in sort order
- [ ] AJAX search returns JSON
- [ ] HTML search returns view
- [ ] XML export is valid
- [ ] Print view renders
- [ ] Supporter badge displays correctly
- [ ] 404 for non-existent users

### 3.3 Deploy UsersController

1. Deploy Laravel code
2. Update nginx config:
   ```nginx
   location /users {
       try_files $uri $uri/ @laravel;
   }
   ```
3. Reload nginx: `sudo nginx -s reload`
4. Verify and monitor

---

## Phase 4: AccountController Migration (Authentication)

### Objective
Migrate registration, login, logout, settings, and password recovery.

**This is the highest-risk phase** - authentication affects all users.

### 4.1 Authentication Service

**File**: `/app/Services/AuthService.php`
```php
class AuthService {
    public function login(string $username, string $password): bool {
        $user = User::where('username', $username)->first();
        if (!$user) return false;

        // Check legacy SHA1 and upgrade
        if ($user->password === sha1($password)) {
            $user->password = $this->encryptPassword($user->username, $password);
            $user->save();
            Auth::login($user);
            return true;
        }

        // Check SHA256
        if ($user->password === $this->encryptPassword($user->username, $password)) {
            Auth::login($user);
            return true;
        }

        return false;
    }

    public function encryptPassword(string $username, string $password): string {
        $presalt = substr(md5($username), 0, 12);
        return hash('sha256', $presalt . $password . config('auth.global_salt'));
    }

    public function remember(User $user): void {
        $series = sha1(mt_rand());
        $token = mt_rand() + mt_rand();

        PersistentLogin::create([
            'user_id' => $user->id,
            'series' => $series,
            'token' => $token,
        ]);

        Cookie::queue('skiv_remember',
            "{$user->id};{$series};{$token}",
            60 * 24 * 30);
    }

    public function validateRememberCookie(): bool {
        $cookie = Cookie::get('skiv_remember');
        if (!$cookie) return false;

        [$userId, $series, $token] = explode(';', $cookie);
        $persist = PersistentLogin::where('user_id', $userId)
                                  ->where('series', $series)
                                  ->first();
        if (!$persist) return false;

        // Token mismatch = potential attack
        if ($persist->token != $token) {
            app(NoticeService::class)->error(
                'Ett försök att hacka ditt konto har upptäckts. ' .
                'Alla sparade inloggningar har raderats.'
            );
            PersistentLogin::where('user_id', $userId)->delete();
            Cookie::queue(Cookie::forget('skiv_remember'));
            return false;
        }

        // Valid - rotate token
        $user = User::find($userId);
        Auth::login($user);

        $newToken = mt_rand() + mt_rand();
        $persist->update(['token' => $newToken]);
        Cookie::queue('skiv_remember',
            "{$userId};{$series};{$newToken}",
            60 * 24 * 30);

        return true;
    }
}
```

**Controller**: `/app/Http/Controllers/AccountController.php`
```php
class AccountController extends Controller {
    public function __construct(private AuthService $auth) {}

    public function showLogin() {
        return view('account.login');
    }

    public function login(Request $request) {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        if ($this->auth->login($request->username, $request->password)) {
            if ($request->remember) {
                $this->auth->remember(Auth::user());
            }
            return redirect()->intended('/');
        }

        return back()->withErrors(['login' => 'Felaktigt användarnamn eller lösenord.']);
    }

    public function logout() {
        PersistentLogin::where('user_id', Auth::id())->delete();
        Cookie::queue(Cookie::forget('skiv_remember'));
        Auth::logout();

        return redirect('/');
    }

    public function showRegister() {
        return view('account.register');
    }

    public function register(RegisterRequest $request) {
        $user = User::create([
            'username' => $request->username,
            'password' => $this->auth->encryptPassword(
                $request->username,
                $request->password
            ),
            'email' => $request->email,
            'registered' => now(),
        ]);

        Auth::login($user);
        return redirect('/')->with('success', 'Välkommen till Skivsamlingen!');
    }

    public function settings() {
        return view('account.settings', ['user' => Auth::user()]);
    }

    public function updateSettings(SettingsRequest $request) {
        $user = Auth::user();
        $user->fill($request->validated());

        if ($request->filled('new_password')) {
            $user->password = $this->auth->encryptPassword(
                $user->username,
                $request->new_password
            );
        }

        $user->save();
        return back()->with('success', 'Inställningarna har sparats.');
    }

    // Password recovery methods...
}
```

### 4.2 Session Transition Strategy

When AccountController goes live, both apps must handle auth consistently:

1. **Before deploy**: Ensure shared session mechanism works
2. **Deploy**: Route `/account/*` to Laravel
3. **After deploy**: Users logging in via Laravel can still use CodeIgniter pages

**Critical**: Test login/logout flow across both apps extensively.

### 4.3 Testing
- [ ] Login with SHA256 password
- [ ] Login with SHA1 password (auto-upgrade)
- [ ] Remember me creates persistent login
- [ ] Token rotation on each request
- [ ] Replay attack detection
- [ ] Registration creates user
- [ ] Password validation (matches username rules)
- [ ] Settings update
- [ ] Password change
- [ ] Logout clears all sessions
- [ ] Password recovery flow

### 4.4 Deploy AccountController

**Pre-deployment checklist**:
- [ ] Shared session mechanism tested
- [ ] All auth tests passing
- [ ] AUTH_GLOBAL_SALT matches CodeIgniter exactly
- [ ] Rollback plan ready

1. Deploy Laravel code
2. Update nginx config:
   ```nginx
   location /account {
       try_files $uri $uri/ @laravel;
   }
   ```
3. Reload nginx: `sudo nginx -s reload`
4. **Immediately test**:
   - [ ] Login works
   - [ ] Logout works
   - [ ] Remember me works
   - [ ] Can access CodeIgniter pages after Laravel login
5. Monitor closely for 48 hours

---

## Phase 5: CollectionController Migration

### Objective
Migrate record collection CRUD operations.

### 5.1 Create Laravel Components

**Controller**: `/app/Http/Controllers/CollectionController.php`
```php
class CollectionController extends Controller {
    public function __construct() {
        $this->middleware('auth');
    }

    public function record(Request $request, int $id = 0) {
        if ($request->isMethod('post')) {
            $request->validate([
                'artist' => 'required|max:64',
                'title' => 'required|max:150',
                'year' => 'nullable|integer|digits:4|min:1',
                'format' => 'nullable|max:30',
                'comment' => 'nullable|max:255',
            ]);

            $artistId = Artist::getOrCreateId($request->artist);
            $recordId = Record::getOrCreateId(
                $artistId,
                $request->title,
                $request->year,
                $request->format
            );

            // If editing, delete old entry
            if ($id > 0) {
                DB::table('records_users')
                    ->where('id', $id)
                    ->where('user_id', Auth::id())
                    ->delete();
            }

            DB::table('records_users')->insert([
                'user_id' => Auth::id(),
                'record_id' => $recordId,
                'comment' => $request->comment,
            ]);

            return redirect('/users/' . Auth::user()->username)
                   ->with('success', 'Skivan har sparats.');
        }

        $record = $id > 0
            ? DB::table('records_users')
                ->join('records', 'records.id', '=', 'records_users.record_id')
                ->join('artists', 'artists.id', '=', 'records.artist_id')
                ->where('records_users.id', $id)
                ->where('records_users.user_id', Auth::id())
                ->first()
            : null;

        return view('collection.record', compact('record'));
    }

    public function delete(int $id) {
        $entry = DB::table('records_users')
            ->where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$entry) abort(404);

        if (request()->isMethod('post')) {
            DB::table('records_users')->where('id', $id)->delete();
            return redirect('/users/' . Auth::user()->username)
                   ->with('success', 'Skivan har tagits bort.');
        }

        return view('collection.delete', compact('entry'));
    }
}
```

### 5.2 Testing
- [ ] Add new record
- [ ] Edit existing record
- [ ] Delete record (with confirmation)
- [ ] Case-sensitive title matching
- [ ] Artist auto-creation
- [ ] Comment CRUD
- [ ] Field truncation (artist 64, title 150, format 30)
- [ ] Auth required for all actions

### 5.3 Deploy CollectionController

1. Deploy Laravel code
2. Update nginx config:
   ```nginx
   location /collection {
       try_files $uri $uri/ @laravel;
   }
   ```
3. Reload nginx: `sudo nginx -s reload`
4. Verify CRUD operations work
5. Monitor for 48 hours

---

## Phase 6: Final Migration & Cleanup

### 6.1 Remove CodeIgniter

Once all controllers are migrated and stable:

1. Update nginx config to route all traffic to Laravel (PHP 8.1 only):
   ```nginx
   server {
       listen 80;
       server_name skivsamlingen.se;
       root /var/www/skivsamlingen.se/laravel/public;
       index index.php;

       # Static assets (still served from original location)
       location /static/ {
           alias /var/www/skivsamlingen.se/static/;
           expires 30d;
       }

       # All requests to Laravel
       location / {
           try_files $uri $uri/ /index.php?$query_string;
       }

       # PHP 8.1 only (CodeIgniter removed)
       location ~ \.php$ {
           fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
           fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
           include fastcgi_params;
       }
   }
   ```

2. Reload nginx: `sudo nginx -s reload`

3. Update Laravel `.env`:
   ```env
   APP_URL=https://skivsamlingen.se
   ```

4. **(Optional)** Clean up old CodeIgniter files once stable:
   ```bash
   # Back up first!
   tar -czf ~/codeigniter-backup.tar.gz /var/www/skivsamlingen.se/application /var/www/skivsamlingen.se/system

   # Remove CodeIgniter directories
   rm -rf /var/www/skivsamlingen.se/application
   rm -rf /var/www/skivsamlingen.se/system
   rm /var/www/skivsamlingen.se/index.php
   ```

5. **(Optional)** Remove PHP 5.6 if no longer needed:
   ```bash
   sudo systemctl stop php5.6-fpm
   sudo systemctl disable php5.6-fpm
   sudo apt remove php5.6-fpm
   ```

### 6.2 Remove Shared Session Code

After CodeIgniter is removed:
1. Switch to native Laravel sessions
2. Remove SharedAuth middleware
3. Remove CodeIgniter session table compatibility code

### 6.3 Post-Migration Improvements

Optional improvements now safe to make:
- [ ] Upgrade password hashing to bcrypt (with migration path)
- [ ] Add CSRF protection
- [ ] Implement Laravel's built-in rate limiting
- [ ] Modernize frontend (if desired)
- [ ] Add proper logging

---

## Deployment Checklist Template

Use this for each controller deployment:

### Pre-Deployment
- [ ] All tests passing
- [ ] Code reviewed
- [ ] nginx config changes prepared and tested locally
- [ ] Rollback plan documented

### Deployment
- [ ] Deploy code to production
- [ ] Update nginx config with new location block
- [ ] Test nginx config: `sudo nginx -t`
- [ ] Reload nginx: `sudo nginx -s reload`
- [ ] Clear Laravel caches: `php artisan cache:clear`
- [ ] Verify basic functionality

### Post-Deployment
- [ ] Monitor error logs for 1 hour
- [ ] Verify all routes work
- [ ] Check for session/auth issues
- [ ] Monitor for 24-48 hours before next migration

### Rollback (if needed)
1. Comment out the new location block in nginx config
2. Reload nginx: `sudo nginx -s reload`
3. Document what failed
4. Fix and re-test before retry

---

## Timeline: 10-14 weeks

| Week | Phase | Deliverable |
|------|-------|-------------|
| 1-2 | Phase 0 | Foundation + parallel architecture |
| 3 | Phase 1 | NewsController deployed |
| 4-5 | Phase 2 | HomeController deployed |
| 6-7 | Phase 3 | UsersController deployed |
| 8-10 | Phase 4 | AccountController deployed |
| 11-12 | Phase 5 | CollectionController deployed |
| 13-14 | Phase 6 | Cleanup + CodeIgniter removal |

**Note**: Timeline includes stabilization periods between deployments.

---

## High-Risk Areas

### 1. Dual PHP Version Setup (Phase 0)
- PHP 5.6 (CodeIgniter) and PHP 8.1 (Laravel) must run simultaneously
- nginx must route to correct PHP-FPM socket based on request path
- **Risk**: Wrong PHP version causes 500 errors or silent failures

### 2. Shared Session Authentication (Phase 0)
- Both apps must recognize the same logged-in user
- Test thoroughly before proceeding
- **Risk**: Users randomly logged out

### 3. Password Hashing (Phase 4)
- Must preserve exact algorithm
- AUTH_GLOBAL_SALT must match exactly
- **Risk**: All users locked out

### 4. Case-Sensitive Title Matching (Phase 5)
- MySQL collation behavior must match
- **Risk**: Duplicate records

---

## Success Criteria

Migration is complete when:
- [ ] All controllers migrated and stable
- [ ] CodeIgniter completely removed
- [ ] All tests passing (100+ tests)
- [ ] No critical bugs for 1 week after final deployment
- [ ] Performance within 20% of CodeIgniter

---

*This plan was updated on 2026-01-18*
*Strategy: Incremental controller-by-controller migration*
