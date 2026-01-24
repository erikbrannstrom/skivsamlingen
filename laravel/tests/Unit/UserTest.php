<?php

namespace Tests\Unit;

use App\Models\User;
use Tests\TestCase;

class UserTest extends TestCase
{
    public function test_timestamps_are_disabled(): void
    {
        $user = new User();

        $this->assertFalse($user->timestamps);
    }

    public function test_fillable_contains_expected_fields(): void
    {
        $user = new User();
        $fillable = $user->getFillable();

        $this->assertContains('username', $fillable);
        $this->assertContains('password', $fillable);
        $this->assertContains('email', $fillable);
        $this->assertContains('name', $fillable);
        $this->assertContains('birth', $fillable);
        $this->assertContains('about', $fillable);
        $this->assertContains('sex', $fillable);
    }

    public function test_password_is_hidden(): void
    {
        $user = new User();
        $hidden = $user->getHidden();

        $this->assertContains('password', $hidden);
    }

    public function test_casts_are_configured_correctly(): void
    {
        $user = new User();
        $casts = $user->getCasts();

        $this->assertEquals('date', $casts['birth']);
        $this->assertEquals('datetime', $casts['registered']);
        $this->assertEquals('boolean', $casts['public_email']);
        $this->assertEquals('integer', $casts['per_page']);
    }

    public function test_encrypt_password_produces_consistent_hash(): void
    {
        $username = 'testuser';
        $password = 'secret123';

        $hash1 = User::encryptPassword($username, $password);
        $hash2 = User::encryptPassword($username, $password);

        $this->assertEquals($hash1, $hash2);
        $this->assertEquals(64, strlen($hash1)); // SHA256 produces 64 hex characters
    }

    public function test_encrypt_password_differs_by_username(): void
    {
        $password = 'secret123';

        $hash1 = User::encryptPassword('user1', $password);
        $hash2 = User::encryptPassword('user2', $password);

        $this->assertNotEquals($hash1, $hash2);
    }

    public function test_verify_password_with_sha256_hash(): void
    {
        $user = new User();
        $user->username = 'testuser';
        $user->password = User::encryptPassword('testuser', 'correct');

        $this->assertTrue($user->verifyPassword('correct'));
        $this->assertFalse($user->verifyPassword('wrong'));
    }

    public function test_verify_password_with_legacy_sha1_hash(): void
    {
        $user = new User();
        $user->username = 'testuser';
        $user->password = sha1('legacypass');

        $this->assertTrue($user->verifyPassword('legacypass'));
        $this->assertFalse($user->verifyPassword('wrong'));
    }

    public function test_has_legacy_password(): void
    {
        $user = new User();
        $user->username = 'testuser';
        $user->password = sha1('legacypass');

        $this->assertTrue($user->hasLegacyPassword('legacypass'));
        $this->assertFalse($user->hasLegacyPassword('wrong'));
    }

    public function test_has_legacy_password_returns_false_for_sha256(): void
    {
        $user = new User();
        $user->username = 'testuser';
        $user->password = User::encryptPassword('testuser', 'modern');

        $this->assertFalse($user->hasLegacyPassword('modern'));
    }
}
