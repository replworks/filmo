<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_new_administrator(): void
    {
        $this->artisan('filmo:create-admin', [
            'email' => 'admin@example.com',
            '--name' => 'New Admin',
            '--password' => 'secret123',
        ])->assertSuccessful();

        $admin = User::where('email', 'admin@example.com')->firstOrFail();

        $this->assertSame('New Admin', $admin->name);
        $this->assertTrue($admin->is_admin);
        $this->assertTrue(Hash::check('secret123', $admin->password));
    }

    public function test_it_promotes_an_existing_user_without_changing_their_password(): void
    {
        $user = User::factory()->create([
            'name' => 'Existing User',
            'email' => 'existing@example.com',
            'is_admin' => false,
        ]);
        $password = $user->password;

        $this->artisan('filmo:create-admin', [
            'email' => 'existing@example.com',
        ])->assertSuccessful();

        $user->refresh();

        $this->assertTrue($user->is_admin);
        $this->assertSame('Existing User', $user->name);
        $this->assertSame($password, $user->password);
    }

    public function test_it_updates_name_and_password_when_supplied_for_an_existing_user(): void
    {
        User::factory()->create([
            'name' => 'Old Name',
            'email' => 'existing@example.com',
            'is_admin' => false,
        ]);

        $this->artisan('filmo:create-admin', [
            'email' => 'existing@example.com',
            '--name' => 'New Name',
            '--password' => 'secret123',
        ])->assertSuccessful();

        $user = User::where('email', 'existing@example.com')->firstOrFail();

        $this->assertSame('New Name', $user->name);
        $this->assertTrue($user->is_admin);
        $this->assertTrue(Hash::check('secret123', $user->password));
    }

    public function test_it_rejects_an_invalid_email(): void
    {
        $this->artisan('filmo:create-admin', [
            'email' => 'not-an-email',
            '--name' => 'Admin',
            '--password' => 'secret123',
        ])->assertFailed();

        $this->assertSame(0, User::count());
    }

    public function test_it_requires_a_name_and_password_for_a_new_administrator(): void
    {
        $this->artisan('filmo:create-admin', [
            'email' => 'admin@example.com',
            '--no-interaction' => true,
        ])->assertFailed();

        $this->assertDatabaseMissing('users', ['email' => 'admin@example.com']);
    }

    public function test_it_requires_a_password_of_at_least_eight_characters(): void
    {
        $this->artisan('filmo:create-admin', [
            'email' => 'admin@example.com',
            '--name' => 'Admin',
            '--password' => 'short',
        ])->assertFailed();

        $this->assertDatabaseMissing('users', ['email' => 'admin@example.com']);
    }
}
