<?php

namespace Tests\Feature;

use App\Mail\PasswordResetMail;
use App\Models\Artist;
use App\Models\PasswordRecovery;
use App\Models\Record;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use DatabaseTruncation;

    protected function setUp(): void
    {
        parent::setUp();

        // Clear any stale auth state from previous tests
        Auth::logout();

        // Clear CI session cookies
        unset($_COOKIE['ci_session']);
        unset($_COOKIE['skiv_remember']);
    }

    // ===================
    // LOGIN TESTS
    // ===================

    public function test_login_page_loads(): void
    {
        $response = $this->get('/account/login');

        $response->assertStatus(200);
        $response->assertViewIs('account.login');
        $response->assertSee('Logga in');
    }

    public function test_login_page_redirects_if_already_logged_in(): void
    {
        $user = User::factory()->create(['username' => 'testuser']);

        $response = $this->actingAs($user)->get('/account/login');

        $response->assertRedirect('/users/testuser');
    }

    public function test_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'username' => 'validuser',
            'password' => User::encryptPassword('validuser', 'testpass123'),
        ]);

        $response = $this->post('/account/login', [
            'username' => 'validuser',
            'password' => 'testpass123',
        ]);

        $response->assertRedirect('/');
        $response->assertSessionHas('success', 'Du är inloggad!');
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_with_invalid_username(): void
    {
        $response = $this->post('/account/login', [
            'username' => 'nonexistent',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/account/login');
        $response->assertSessionHas('error', 'Felaktiga användaruppgifter.');
        $this->assertGuest();
    }

    public function test_login_with_invalid_password(): void
    {
        User::factory()->create([
            'username' => 'wrongpassuser',
            'password' => User::encryptPassword('wrongpassuser', 'correctpass'),
        ]);

        // Make request without any SharedAuth middleware interference
        $response = $this->post('/account/login', [
            'username' => 'wrongpassuser',
            'password' => 'wrongpass',
        ]);

        $response->assertRedirect('/account/login');
        $response->assertSessionHas('error', 'Felaktiga användaruppgifter.');
        $this->assertGuest();
    }

    public function test_login_with_legacy_sha1_password_upgrades_automatically(): void
    {
        $legacyPassword = sha1('oldpassword');
        $user = User::factory()->create([
            'username' => 'legacyuser',
            'password' => $legacyPassword,
        ]);

        $response = $this->post('/account/login', [
            'username' => 'legacyuser',
            'password' => 'oldpassword',
        ]);

        $response->assertRedirect('/');
        $this->assertAuthenticatedAs($user);

        // Verify password was upgraded to SHA256 format
        $user->refresh();
        $this->assertNotEquals($user->password, $legacyPassword);
        $expectedPassword = User::encryptPassword('legacyuser', 'oldpassword');
        $this->assertEquals($expectedPassword, $user->password);
    }

    public function test_login_requires_username(): void
    {
        $response = $this->post('/account/login', [
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors(['username']);
    }

    public function test_login_requires_password(): void
    {
        $response = $this->post('/account/login', [
            'username' => 'testuser',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    public function test_login_with_remember_me_creates_persistent_login(): void
    {
        $user = User::factory()->create([
            'username' => 'rememberuser',
            'password' => User::encryptPassword('rememberuser', 'testpass'),
        ]);

        $response = $this->post('/account/login', [
            'username' => 'rememberuser',
            'password' => 'testpass',
            'remember_me' => 'true',
        ]);

        $response->assertRedirect('/');
        $this->assertAuthenticatedAs($user);

        // Should have created a persistent login record
        $this->assertDatabaseHas('persistent_logins', [
            'user_id' => $user->id,
        ]);

        // Should have set the cookie
        $this->assertArrayHasKey('skiv_remember', $_COOKIE);
    }

    public function test_login_without_remember_me_does_not_create_persistent_login(): void
    {
        $user = User::factory()->create([
            'username' => 'norememberuser',
            'password' => User::encryptPassword('norememberuser', 'testpass'),
        ]);

        $response = $this->post('/account/login', [
            'username' => 'norememberuser',
            'password' => 'testpass',
        ]);

        $response->assertRedirect('/');
        $this->assertAuthenticatedAs($user);

        // Should NOT have created a persistent login record
        $this->assertDatabaseMissing('persistent_logins', [
            'user_id' => $user->id,
        ]);
        // Should not have set cookie
        $this->assertArrayNotHasKey('skiv_remember', $_COOKIE);
    }

    // ===================
    // LOGOUT TESTS
    // ===================

    public function test_logout_logs_out_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/account/logout');

        $response->assertRedirect('/');
        $this->assertGuest();
    }

    // ===================
    // REGISTRATION TESTS
    // ===================

    public function test_register_page_loads(): void
    {
        $response = $this->get('/account/register');

        $response->assertStatus(200);
        $response->assertViewIs('account.register');
        $response->assertSee('Bli medlem');
    }

    public function test_register_creates_new_user(): void
    {
        $response = $this->post('/account/register', [
            'username' => 'newuser',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'captcha' => '7',
            'captcha_a' => 'tre',
            'captcha_b' => 'fyra',
            'email' => 'newuser@example.com',
        ]);

        $response->assertRedirect('/');
        $response->assertSessionHas('success');

        $user = User::where('username', 'newuser')->first();
        $this->assertNotNull($user);
        $this->assertAuthenticatedAs($user);
    }

    public function test_register_requires_username(): void
    {
        $response = $this->post('/account/register', [
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'captcha' => '5',
            'captcha_a' => 'två',
            'captcha_b' => 'tre',
        ]);

        $response->assertSessionHasErrors(['username']);
    }

    public function test_register_username_min_length(): void
    {
        $response = $this->post('/account/register', [
            'username' => 'ab',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'captcha' => '5',
            'captcha_a' => 'två',
            'captcha_b' => 'tre',
        ]);

        $response->assertSessionHasErrors(['username']);
    }

    public function test_register_username_max_length(): void
    {
        $response = $this->post('/account/register', [
            'username' => str_repeat('a', 25),
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'captcha' => '5',
            'captcha_a' => 'två',
            'captcha_b' => 'tre',
        ]);

        $response->assertSessionHasErrors(['username']);
    }

    public function test_register_username_must_be_unique(): void
    {
        User::factory()->create(['username' => 'existinguser']);

        $response = $this->post('/account/register', [
            'username' => 'existinguser',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'captcha' => '5',
            'captcha_a' => 'två',
            'captcha_b' => 'tre',
        ]);

        $response->assertSessionHasErrors(['username']);
    }

    public function test_register_username_only_allows_valid_characters(): void
    {
        $response = $this->post('/account/register', [
            'username' => 'user name!',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'captcha' => '5',
            'captcha_a' => 'två',
            'captcha_b' => 'tre',
        ]);

        $response->assertSessionHasErrors(['username']);
    }

    public function test_register_password_min_length(): void
    {
        $response = $this->post('/account/register', [
            'username' => 'validuser',
            'password' => '12345',
            'password_confirmation' => '12345',
            'captcha' => '5',
            'captcha_a' => 'två',
            'captcha_b' => 'tre',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    public function test_register_passwords_must_match(): void
    {
        $response = $this->post('/account/register', [
            'username' => 'validuser',
            'password' => 'password123',
            'password_confirmation' => 'different123',
            'captcha' => '5',
            'captcha_a' => 'två',
            'captcha_b' => 'tre',
        ]);

        $response->assertSessionHasErrors(['password_confirmation']);
    }

    public function test_register_captcha_required(): void
    {
        $response = $this->post('/account/register', [
            'username' => 'validuser',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'captcha_a' => 'två',
            'captcha_b' => 'tre',
        ]);

        $response->assertSessionHasErrors(['captcha']);
    }

    public function test_register_captcha_must_be_correct(): void
    {
        $response = $this->post('/account/register', [
            'username' => 'validuser',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'captcha' => '999',
            'captcha_a' => 'två',
            'captcha_b' => 'tre',
        ]);

        $response->assertSessionHasErrors(['captcha']);
    }

    public function test_register_captcha_with_various_combinations(): void
    {
        // Test noll + noll = 0
        $response = $this->post('/account/register', [
            'username' => 'user1',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'captcha' => '0',
            'captcha_a' => 'noll',
            'captcha_b' => 'noll',
        ]);
        $response->assertSessionDoesntHaveErrors(['captcha']);

        // Test elva + ett = 12
        $response = $this->post('/account/register', [
            'username' => 'user2',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'captcha' => '12',
            'captcha_a' => 'elva',
            'captcha_b' => 'ett',
        ]);
        $response->assertSessionDoesntHaveErrors(['captcha']);
    }

    public function test_register_email_validation(): void
    {
        $response = $this->post('/account/register', [
            'username' => 'validuser',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'captcha' => '5',
            'captcha_a' => 'två',
            'captcha_b' => 'tre',
            'email' => 'invalid-email',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_register_with_optional_fields(): void
    {
        $response = $this->post('/account/register', [
            'username' => 'fulluser',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'captcha' => '5',
            'captcha_a' => 'två',
            'captcha_b' => 'tre',
            'email' => 'full@example.com',
            'name' => 'Full Name',
            'sex' => 'm',
            'birth' => '1990-01-15',
        ]);

        $response->assertRedirect('/');

        $user = User::where('username', 'fulluser')->first();

        $this->assertEquals('Full Name', $user->name);
        $this->assertEquals('m', $user->sex);
        $this->assertEquals('1990-01-15', $user->birth->format('Y-m-d'));
    }

    // ===================
    // PASSWORD RECOVERY TESTS
    // ===================

    public function test_forgot_page_loads(): void
    {
        $response = $this->get('/account/forgot');

        $response->assertStatus(200);
        $response->assertViewIs('account.forgot');
        $response->assertSee('Glömt lösenord');
    }

    public function test_forgot_sends_email_for_valid_username(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'username' => 'forgotuser',
            'email' => 'forgot@example.com',
        ]);

        $response = $this->post('/account/forgot', [
            'username' => 'forgotuser',
        ]);

        $response->assertRedirect('/');
        $response->assertSessionHas('success');

        Mail::assertSent(PasswordResetMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });

        $this->assertDatabaseHas('password_recovery', [
            'username' => 'forgotuser',
        ]);
    }

    public function test_forgot_sends_email_for_valid_email(): void
    {
        Mail::fake();

        User::factory()->create([
            'username' => 'emailuser',
            'email' => 'findme@example.com',
        ]);

        $response = $this->post('/account/forgot', [
            'username' => 'findme@example.com',
        ]);

        $response->assertRedirect('/');
        $response->assertSessionHas('success');

        Mail::assertSent(PasswordResetMail::class);
    }

    public function test_forgot_fails_for_nonexistent_user(): void
    {
        Mail::fake();

        $response = $this->post('/account/forgot', [
            'username' => 'nonexistent',
        ]);

        $response->assertRedirect('/account/forgot');
        $response->assertSessionHas('error', 'Användarnamnet eller e-postadressen kunde inte hittas.');

        Mail::assertNotSent(PasswordResetMail::class);
    }

    public function test_forgot_fails_if_recovery_already_sent(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'username' => 'alreadysent',
            'email' => 'already@example.com',
        ]);

        PasswordRecovery::create([
            'username' => 'alreadysent',
            'hash' => 'existinghash',
            'created_on' => time(),
        ]);

        $response = $this->post('/account/forgot', [
            'username' => 'alreadysent',
        ]);

        $response->assertRedirect('/');
        $response->assertSessionHas('error', 'Ett mail för återställning har redan skickats.');

        Mail::assertNotSent(PasswordResetMail::class);
    }

    public function test_forgot_fails_if_user_has_no_email(): void
    {
        Mail::fake();

        User::factory()->create([
            'username' => 'noemail',
            'email' => null,
        ]);

        $response = $this->post('/account/forgot', [
            'username' => 'noemail',
        ]);

        $response->assertRedirect('/account/forgot');
        $response->assertSessionHas('error');

        Mail::assertNotSent(PasswordResetMail::class);
    }

    public function test_recover_page_loads_with_valid_token(): void
    {
        $user = User::factory()->create(['username' => 'recoveruser']);
        $recovery = PasswordRecovery::create([
            'username' => 'recoveruser',
            'hash' => 'validhash123',
            'created_on' => time(),
        ]);

        $response = $this->get('/account/recover/recoveruser/validhash123');

        $response->assertStatus(200);
        $response->assertViewIs('account.recover');
        $response->assertSee('Välj nytt lösenord');
    }

    public function test_recover_page_redirects_with_invalid_token(): void
    {
        $response = $this->get('/account/recover/nonexistent/invalidhash');

        $response->assertRedirect('/account/forgot');
        $response->assertSessionHas('error', 'Länken är inte giltig.');
    }

    public function test_recover_page_redirects_with_expired_token(): void
    {
        User::factory()->create(['username' => 'expireduser']);
        PasswordRecovery::create([
            'username' => 'expireduser',
            'hash' => 'expiredhash',
            'created_on' => time() - (49 * 60 * 60), // 49 hours ago (expired)
        ]);

        $response = $this->get('/account/recover/expireduser/expiredhash');

        $response->assertRedirect('/account/forgot');
        $response->assertSessionHas('error', 'Länken är inte giltig.');
    }

    public function test_recover_updates_password(): void
    {
        $user = User::factory()->create(['username' => 'resetuser']);
        $recovery = PasswordRecovery::create([
            'username' => 'resetuser',
            'hash' => 'resethash',
            'created_on' => time(),
        ]);

        $response = $this->post('/account/recover/resetuser/resethash', [
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertRedirect('/account/login');
        $response->assertSessionHas('success', 'Ditt lösenord är uppdaterat.');

        // Verify password was updated
        $user->refresh();
        $this->assertTrue($user->verifyPassword('newpassword123'));

        // Verify token was deleted
        $this->assertEmpty(PasswordRecovery::where('username', 'resetuser')->get());
    }

    public function test_recover_validates_password_length(): void
    {
        User::factory()->create(['username' => 'shortpassuser']);
        PasswordRecovery::create([
            'username' => 'shortpassuser',
            'hash' => 'shorthash',
            'created_on' => time(),
        ]);

        $response = $this->post('/account/recover/shortpassuser/shorthash', [
            'password' => '12345',
            'password_confirmation' => '12345',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    public function test_recover_validates_password_confirmation(): void
    {
        User::factory()->create(['username' => 'mismatchuser']);
        PasswordRecovery::create([
            'username' => 'mismatchuser',
            'hash' => 'mismatchhash',
            'created_on' => time(),
        ]);

        $response = $this->post('/account/recover/mismatchuser/mismatchhash', [
            'password' => 'password123',
            'password_confirmation' => 'different123',
        ]);

        $response->assertSessionHasErrors(['password_confirmation']);
    }

    // ===================
    // SETTINGS TESTS
    // ===================

    public function test_edit_page_requires_authentication(): void
    {
        $response = $this->get('/account/edit');

        $response->assertRedirect('/account/login');
    }

    public function test_edit_page_loads_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/account/edit');

        $response->assertStatus(200);
        $response->assertViewIs('account.edit');
        $response->assertSee('Inställningar');
    }

    public function test_update_settings_successfully(): void
    {
        $user = User::factory()->create([
            'username' => 'settingsuser',
            'name' => 'Old Name',
            'email' => 'old@example.com',
        ]);

        $response = $this->actingAs($user)->post('/account/edit', [
            'name' => 'New Name',
            'email' => 'new@example.com',
            'sex' => 'f',
            'public_email' => '1',
            'about' => 'New about text',
            'per_page' => 50,
        ]);

        $response->assertRedirect('/account/edit');
        $response->assertSessionHas('success', 'Dina uppgifter har uppdaterats.');

        $user->refresh();
        $this->assertEquals('New Name', $user->name);
        $this->assertEquals('new@example.com', $user->email);
        $this->assertEquals('f', $user->sex);
        $this->assertEquals(1, $user->public_email);
        $this->assertEquals('New about text', $user->about);
        $this->assertEquals(50, $user->per_page);
    }

    public function test_update_settings_validates_email(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/account/edit', [
            'email' => 'invalid-email',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_update_settings_validates_per_page_max(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/account/edit', [
            'per_page' => 101,
        ]);

        $response->assertSessionHasErrors(['per_page']);
    }

    public function test_update_settings_validates_about_max_length(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/account/edit', [
            'about' => str_repeat('a', 3001),
        ]);

        $response->assertSessionHasErrors(['about']);
    }

    // ===================
    // PASSWORD CHANGE TESTS
    // ===================

    public function test_password_change_requires_authentication(): void
    {
        $response = $this->post('/account/password', [
            'current_password' => 'old',
            'new_password' => 'newpass123',
            'new_password_confirmation' => 'newpass123',
        ]);

        $response->assertRedirect('/account/login');
    }

    public function test_password_change_with_correct_current_password(): void
    {
        $user = User::factory()->create([
            'username' => 'passuser',
            'password' => User::encryptPassword('passuser', 'oldpassword'),
        ]);

        $response = $this->actingAs($user)->post('/account/password', [
            'current_password' => 'oldpassword',
            'new_password' => 'newpassword123',
            'new_password_confirmation' => 'newpassword123',
        ]);

        $response->assertRedirect('/account/edit');
        $response->assertSessionHas('success', 'Ditt lösenord är ändrat.');

        $user->refresh();
        $this->assertTrue($user->verifyPassword('newpassword123'));
        $this->assertFalse($user->verifyPassword('oldpassword'));
    }

    public function test_password_change_with_incorrect_current_password(): void
    {
        $user = User::factory()->create([
            'username' => 'wrongpassuser',
            'password' => User::encryptPassword('wrongpassuser', 'correctpass'),
        ]);

        $response = $this->actingAs($user)->post('/account/password', [
            'current_password' => 'wrongpass',
            'new_password' => 'newpassword123',
            'new_password_confirmation' => 'newpassword123',
        ]);

        $response->assertSessionHasErrors(['current_password']);
    }

    public function test_password_change_validates_new_password_length(): void
    {
        $user = User::factory()->create([
            'username' => 'shortuser',
            'password' => User::encryptPassword('shortuser', 'oldpassword'),
        ]);

        $response = $this->actingAs($user)->post('/account/password', [
            'current_password' => 'oldpassword',
            'new_password' => '12345',
            'new_password_confirmation' => '12345',
        ]);

        $response->assertSessionHasErrors(['new_password']);
    }

    public function test_password_change_validates_confirmation(): void
    {
        $user = User::factory()->create([
            'username' => 'confuser',
            'password' => User::encryptPassword('confuser', 'oldpassword'),
        ]);

        $response = $this->actingAs($user)->post('/account/password', [
            'current_password' => 'oldpassword',
            'new_password' => 'newpassword123',
            'new_password_confirmation' => 'different123',
        ]);

        $response->assertSessionHasErrors(['new_password_confirmation']);
    }

    // ===================
    // UNREGISTER TESTS
    // ===================

    public function test_unregister_requires_authentication(): void
    {
        $response = $this->post('/account/unregister', [
            'password' => 'password',
            'confirmation' => 'ta bort',
        ]);

        $response->assertRedirect('/account/login');
    }

    public function test_unregister_deletes_user_account(): void
    {
        $user = User::factory()->create([
            'username' => 'deleteuser',
            'password' => User::encryptPassword('deleteuser', 'mypassword'),
        ]);

        $response = $this->actingAs($user)->post('/account/unregister', [
            'password' => 'mypassword',
            'confirmation' => 'ta bort',
        ]);

        $response->assertRedirect('/');
        $response->assertSessionHas('success');
        $this->assertGuest();
        $this->assertNull(User::where('username', 'deleteuser')->first());
    }

    public function test_unregister_deletes_user_records(): void
    {
        $user = User::factory()->create([
            'username' => 'recorduser',
            'password' => User::encryptPassword('recorduser', 'mypassword'),
        ]);

        // Create an artist and record, then add to user's collection
        $artist = Artist::create(['name' => 'Test Artist']);
        $record = Record::create([
            'artist_id' => $artist->id,
            'title' => 'Test Album',
            'year' => 2020,
            'format' => 'LP',
        ]);
        $user->records()->attach($record->id);

        $response = $this->actingAs($user)->post('/account/unregister', [
            'password' => 'mypassword',
            'confirmation' => 'ta bort',
        ]);

        $response->assertRedirect('/');
        $this->assertDatabaseMissing('records_users', [
            'user_id' => $user->id,
        ]);
    }

    public function test_unregister_with_incorrect_password(): void
    {
        $user = User::factory()->create([
            'username' => 'wrongdeluser',
            'password' => User::encryptPassword('wrongdeluser', 'correctpass'),
        ]);

        $response = $this->actingAs($user)->post('/account/unregister', [
            'password' => 'wrongpass',
            'confirmation' => 'ta bort',
        ]);

        $response->assertSessionHasErrors(['password']);
        $this->assertDatabaseHas('users', [
            'username' => 'wrongdeluser',
        ]);
    }

    public function test_unregister_with_incorrect_confirmation(): void
    {
        $user = User::factory()->create([
            'username' => 'wrongconfuser',
            'password' => User::encryptPassword('wrongconfuser', 'mypassword'),
        ]);

        $response = $this->actingAs($user)->post('/account/unregister', [
            'password' => 'mypassword',
            'confirmation' => 'remove',
        ]);

        $response->assertSessionHasErrors(['confirmation']);
        $this->assertDatabaseHas('users', [
            'username' => 'wrongconfuser',
        ]);
    }

    public function test_unregister_confirmation_is_case_insensitive(): void
    {
        $user = User::factory()->create([
            'username' => 'caseuser',
            'password' => User::encryptPassword('caseuser', 'mypassword'),
        ]);

        $response = $this->actingAs($user)->post('/account/unregister', [
            'password' => 'mypassword',
            'confirmation' => 'TA BORT',
        ]);

        $response->assertRedirect('/');
        $this->assertDatabaseMissing('users', [
            'username' => 'caseuser',
        ]);
    }

    // ===================
    // EMAIL CONTENT TESTS
    // ===================

    public function test_password_reset_email_contains_correct_content(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'username' => 'emailcontentuser',
            'email' => 'content@example.com',
        ]);

        $this->post('/account/forgot', [
            'username' => 'emailcontentuser',
        ]);

        Mail::assertSent(PasswordResetMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email)
                && $mail->user->id === $user->id;
        });
    }

    public function test_password_reset_email_contains_reset_link(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'username' => 'linkuser',
            'email' => 'link@example.com',
        ]);

        $this->post('/account/forgot', [
            'username' => 'linkuser',
        ]);

        // Get the recovery record to check the hash
        $recovery = PasswordRecovery::where('username', 'linkuser')->first();

        Mail::assertSent(PasswordResetMail::class, function ($mail) use ($user, $recovery) {
            $resetUrl = $mail->getResetUrl();
            return str_contains($resetUrl, '/account/recover/linkuser/' . $recovery->hash);
        });
    }

    public function test_password_reset_email_renders_correctly(): void
    {
        $user = User::factory()->create([
            'username' => 'renderuser',
            'email' => 'render@example.com',
        ]);

        $recovery = PasswordRecovery::create([
            'username' => 'renderuser',
            'hash' => 'testhash123',
            'created_on' => time(),
        ]);

        $mail = new PasswordResetMail($user, $recovery);

        // Render the email to catch any template errors
        $rendered = $mail->render();

        $this->assertStringContainsString('Hej!', $rendered);
        $this->assertStringContainsString('/account/recover/renderuser/testhash123', $rendered);
        $this->assertStringContainsString('48 timmar', $rendered);
    }

    // ===================
    // SWEDISH ERROR MESSAGE TESTS
    // ===================

    public function test_validation_messages_are_in_swedish(): void
    {
        // Test registration validation messages
        $response = $this->post('/account/register', [
            'username' => '',
            'password' => '',
            'password_confirmation' => '',
            'captcha' => '',
            'captcha_a' => 'ett',
            'captcha_b' => 'två',
        ]);

        // Check that Swedish error messages are returned
        $errors = session('errors');
        $this->assertNotNull($errors);

        // The errors should contain Swedish text
        $allErrors = $errors->all();
        $hasSwedishText = false;
        foreach ($allErrors as $error) {
            if (preg_match('/[åäöÅÄÖ]|måste|anges/', $error)) {
                $hasSwedishText = true;
                break;
            }
        }
        $this->assertTrue($hasSwedishText, 'Validation messages should be in Swedish');
    }
}
