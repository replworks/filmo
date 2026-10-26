<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_database_seeder_creates_the_local_admin(): void
    {
        $this->seed();

        $admin = User::where('email', 'cable8mm@gmail.com')->firstOrFail();

        $this->assertSame('Filmo Admin', $admin->name);
        $this->assertTrue($admin->is_admin);
        $this->assertTrue(Hash::check('1234', $admin->password));
        $this->assertTrue($admin->canAccessPanel(Filament::getPanel('admin')));
        $this->assertDatabaseHas('users', ['email' => 'test@example.com', 'is_admin' => false]);
    }

    public function test_reseeding_the_admin_does_not_duplicate_it(): void
    {
        $this->seed(AdminUserSeeder::class);
        $this->seed(AdminUserSeeder::class);

        $this->assertSame(1, User::where('email', 'cable8mm@gmail.com')->count());
    }

    public function test_the_admin_seeder_does_nothing_outside_local_and_testing(): void
    {
        $this->app->detectEnvironment(fn (): string => 'production');

        $this->seed(AdminUserSeeder::class);

        $this->assertDatabaseMissing('users', ['email' => 'cable8mm@gmail.com']);
    }
}
