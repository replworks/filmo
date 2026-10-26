<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContributorAddRecordsTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guest_cannot_add_a_channel_or_change_data(): void
    {
        $this->post('/contributor/channels', ['channel' => '@YouTube'])
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('youtube_channels', 0);
    }

    public function test_an_invalid_video_url_is_rejected_without_creating_a_video(): void
    {
        $contributor = User::factory()->create();

        $this->actingAs($contributor)
            ->post('/contributor/videos', ['url' => 'https://example.com/not-a-video'])
            ->assertSessionHasErrors('url');

        $this->assertDatabaseCount('youtube_videos', 0);
    }

    public function test_the_contributor_workspace_shows_the_add_forms(): void
    {
        $contributor = User::factory()->create();

        $this->actingAs($contributor)
            ->get('/contributor')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('contributor/index')
                ->has('status'));
    }
}
