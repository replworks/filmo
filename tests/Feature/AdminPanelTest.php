<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_the_login_page_is_rendered(): void
    {
        $this->get('/admin/login')->assertOk();
    }

    public function test_an_authenticated_user_can_reach_the_panel(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin')->assertOk();
    }
}
