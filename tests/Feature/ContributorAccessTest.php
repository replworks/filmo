<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ContributorAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_new_registered_user_is_a_contributor_without_email_verification(): void
    {
        $this->post(route('register.store'), [
            'name' => 'New Contributor',
            'email' => 'contributor@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect(route('home', absolute: false));

        $this->assertAuthenticated();

        $user = User::where('email', 'contributor@example.com')->firstOrFail();

        $this->assertFalse($user->is_admin);
        $this->assertNull($user->email_verified_at);

        $this->get('/contributor')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('contributor/index')
                ->where('auth.user.id', $user->id));

        $this->get('/admin')->assertForbidden();
    }

    public function test_guests_are_redirected_from_the_contributor_workspace(): void
    {
        $this->get('/contributor')->assertRedirect(route('login'));
    }

    public function test_an_administrator_can_open_the_contributor_workspace(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/contributor')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('contributor/index'));
    }

    public function test_a_contributor_cannot_open_the_operator_panel(): void
    {
        $contributor = User::factory()->create();

        $this->actingAs($contributor)
            ->get('/admin')
            ->assertForbidden();
    }
}
