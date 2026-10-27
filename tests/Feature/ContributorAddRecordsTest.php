<?php

namespace Tests\Feature;

use App\Models\ChannelRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContributorAddRecordsTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guest_cannot_add_a_channel_or_change_data(): void
    {
        $this->post('/contributor/channel-requests', ['channel' => '@YouTube'])
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('youtube_channels', 0);
        $this->assertDatabaseCount('channel_requests', 0);
    }

    public function test_an_invalid_video_url_is_rejected_without_creating_a_video(): void
    {
        $contributor = User::factory()->create();

        $this->actingAs($contributor)
            ->post('/contributor/actors', [
                'name' => '새 배우',
                'new_video_urls' => ['https://example.com/not-a-video'],
            ])
            ->assertSessionHasErrors('new_video_urls');

        $this->assertDatabaseCount('youtube_videos', 0);
        $this->assertDatabaseCount('actors', 0);
    }

    public function test_a_contributor_can_submit_a_channel_request(): void
    {
        $contributor = User::factory()->create();

        $this->actingAs($contributor)
            ->post('/contributor/channel-requests', ['channel' => '@YouTube'])
            ->assertSessionHas('status');

        $request = ChannelRequest::firstOrFail();

        $this->assertSame($contributor->id, $request->user_id);
        $this->assertSame('@YouTube', $request->input);
        $this->assertSame(ChannelRequest::STATUS_PENDING, $request->status);
        $this->assertDatabaseCount('youtube_channels', 0);
    }

    public function test_the_contributor_workspace_shows_the_add_forms(): void
    {
        $contributor = User::factory()->create();

        $this->actingAs($contributor)
            ->get('/contributor')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('contributor/index')
                ->has('status')
                ->has('contributions')
                ->has('channelRequests'));
    }
}
