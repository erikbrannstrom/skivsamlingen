<?php

namespace App\Http\Controllers;

use App\Http\Requests\PasswordRequest;
use App\Http\Requests\RecoverPasswordRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\SettingsRequest;
use App\Http\Requests\UnregisterRequest;
use App\Mail\PasswordResetMail;
use App\Models\PasswordRecovery;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

/**
 * Controller for account management (login, register, settings, etc.)
 */
class AccountController extends Controller
{
    /**
     * Swedish number words for captcha generation.
     */
    private const CAPTCHA_WORDS = ['noll', 'ett', 'två', 'tre', 'fyra', 'fem', 'sex', 'sju', 'åtta', 'nio', 'tio', 'elva'];

    public function __construct(
        private AuthService $authService
    ) {}

    /**
     * Show the login form.
     */
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect('/users/' . Auth::user()->username);
        }

        return view('account.login');
    }

    /**
     * Handle login form submission.
     */
    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ], [
            'username.required' => 'Användarnamn måste anges.',
            'password.required' => 'Lösenord måste anges.',
        ]);

        $remember = $request->boolean('remember_me');

        if ($this->authService->login($request->input('username'), $request->input('password'), $remember)) {
            return redirect()
                ->intended('/')
                ->with('success', 'Du är inloggad!');
        }

        return redirect('/account/login')
            ->with('error', 'Felaktiga användaruppgifter.');
    }

    /**
     * Handle logout.
     */
    public function logout(): RedirectResponse
    {
        $this->authService->logout();

        return redirect('/');
    }

    /**
     * Show the registration form.
     */
    public function showRegister(): View
    {
        return view('account.register', [
            'captcha_a' => self::CAPTCHA_WORDS[array_rand(self::CAPTCHA_WORDS)],
            'captcha_b' => self::CAPTCHA_WORDS[array_rand(self::CAPTCHA_WORDS)],
        ]);
    }

    /**
     * Handle registration form submission.
     */
    public function register(RegisterRequest $request): RedirectResponse
    {
        $user = User::create([
            'username' => $request->input('username'),
            'password' => $this->authService->encryptPassword(
                $request->input('username'),
                $request->input('password')
            ),
            'email' => $request->input('email'),
            'name' => $request->input('name'),
            'sex' => $request->input('sex', 'x'),
            'birth' => $request->input('birth'),
            'registered' => now(),
            'per_page' => 25,
            'level' => 1,
        ]);

        // Log in the new user
        $this->authService->login($request->input('username'), $request->input('password'));

        return redirect('/')
            ->with('success', 'Välkommen till Skivsamlingen ' . $user->username . '!');
    }

    /**
     * Show the forgot password form.
     */
    public function showForgot(): View
    {
        return view('account.forgot');
    }

    /**
     * Handle forgot password form submission.
     */
    public function forgot(Request $request): RedirectResponse
    {
        $request->validate([
            'username' => 'required',
        ], [
            'username.required' => 'Användarnamn eller e-post måste anges.',
        ]);

        $usernameOrEmail = $request->input('username');

        // Find user by username or email
        if (str_contains($usernameOrEmail, '@')) {
            $user = User::where('email', $usernameOrEmail)->first();
        } else {
            $user = User::where('username', $usernameOrEmail)->first();
        }

        if (!$user) {
            return redirect('/account/forgot')
                ->with('error', 'Användarnamnet eller e-postadressen kunde inte hittas.');
        }

        // Check if a recovery request already exists
        if (PasswordRecovery::existsForUser($user->username)) {
            return redirect('/')
                ->with('error', 'Ett mail för återställning har redan skickats.');
        }

        // Check if user has an email address
        if (!$user->email) {
            return redirect('/account/forgot')
                ->with('error', 'Kontot har ingen e-postadress registrerad. Kontakta support för hjälp.');
        }

        // Create recovery token
        $recovery = PasswordRecovery::createForUser($user);

        // Send email
        try {
            Mail::to($user->email)->send(new PasswordResetMail($user, $recovery));

            return redirect('/')
                ->with('success', 'Ett mail har skickats till din registrerade e-postadress. Använd länken i mailet för att återställa lösenordet.');
        } catch (\Exception $e) {
            Log::error('Failed to send password reset email', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            // Delete the recovery record since email failed
            $recovery->delete();

            return redirect('/')
                ->with('error', 'Vi ber om ursäkt! Ett problem uppstod när mailet skulle skickas. Var god försök igen senare.');
        }
    }

    /**
     * Show the password recovery form.
     */
    public function showRecover(string $username, string $hash): View|RedirectResponse
    {
        // Clean up expired tokens
        PasswordRecovery::cleanupExpired();

        // Find valid recovery record
        $recovery = PasswordRecovery::findValid($username, $hash);

        if (!$recovery) {
            return redirect('/account/forgot')
                ->with('error', 'Länken är inte giltig.');
        }

        return view('account.recover', [
            'username' => $username,
            'hash' => $hash,
        ]);
    }

    /**
     * Handle password recovery form submission.
     */
    public function recover(RecoverPasswordRequest $request, string $username, string $hash): RedirectResponse
    {
        // Clean up expired tokens
        PasswordRecovery::cleanupExpired();

        // Find valid recovery record
        $recovery = PasswordRecovery::findValid($username, $hash);

        if (!$recovery) {
            return redirect('/account/forgot')
                ->with('error', 'Länken är inte giltig.');
        }

        // Find the user
        $user = User::where('username', $username)->first();

        if (!$user) {
            return redirect('/account/forgot')
                ->with('error', 'Användaren kunde inte hittas.');
        }

        // Update password
        $user->password = $this->authService->encryptPassword($username, $request->input('password'));
        $user->save();

        // Delete the recovery record
        $recovery->delete();

        return redirect('/account/login')
            ->with('success', 'Ditt lösenord är uppdaterat.');
    }

    /**
     * Show the edit settings form.
     */
    public function edit(): View|RedirectResponse
    {
        if (!Auth::check()) {
            return redirect('/account/login');
        }

        return view('account.edit', [
            'user' => Auth::user(),
        ]);
    }

    /**
     * Handle settings update form submission.
     */
    public function update(SettingsRequest $request): RedirectResponse
    {
        if (!Auth::check()) {
            return redirect('/account/login');
        }

        $user = Auth::user();

        $user->fill([
            'name' => $request->input('name'),
            'sex' => $request->input('sex'),
            'email' => $request->input('email'),
            'public_email' => $request->input('public_email', 0),
            'birth' => $request->input('birth'),
            'about' => $request->input('about'),
            'per_page' => $request->input('per_page') ?: 25,
        ]);

        $user->save();

        return redirect('/account/edit')
            ->with('success', 'Dina uppgifter har uppdaterats.');
    }

    /**
     * Handle password change form submission.
     */
    public function password(PasswordRequest $request): RedirectResponse
    {
        if (!Auth::check()) {
            return redirect('/account/login');
        }

        $user = Auth::user();

        $user->password = $this->authService->encryptPassword(
            $user->username,
            $request->input('new_password')
        );
        $user->save();

        return redirect('/account/edit')
            ->with('success', 'Ditt lösenord är ändrat.');
    }

    /**
     * Handle account deletion (unregister).
     */
    public function unregister(UnregisterRequest $request): RedirectResponse
    {
        if (!Auth::check()) {
            return redirect('/account/login');
        }

        $user = Auth::user();
        $username = $user->username;
        $email = $user->email;
        $name = $user->name;

        // Log out the user first
        $this->authService->logout();

        // Delete user's records from collection
        DB::table('records_users')->where('user_id', $user->id)->delete();

        // Delete user's donations
        DB::table('donations')->where('user_id', $user->id)->delete();

        // Delete user's password recovery tokens
        PasswordRecovery::where('username', $username)->delete();

        // Delete user's persistent logins
        DB::table('persistent_logins')->where('user_id', $user->id)->delete();

        // Delete the user
        $user->delete();

        return redirect('/')
            ->with('success', 'Ditt konto har tagits bort. Tack för tiden på Skivsamlingen!')
            ->with('unregistered', [
                'username' => $username,
                'email' => $email,
                'name' => $name,
            ]);
    }
}
