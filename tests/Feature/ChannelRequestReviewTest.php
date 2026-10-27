<?php

namespace Tests\Feature;

use App\Filament\Pages\ChannelRequestReview;
use App\Models\ChannelRequest;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ChannelRequestReviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_an_administrator_can_reject_a_channel_request(): void
    {
        $admin = User::factory()->admin()->create();
        $contributor = User::factory()->create();
        $request = ChannelRequest::create([
            'user_id' => $contributor->id,
            'input' => '@YouTube',
            'status' => ChannelRequest::STATUS_PENDING,
        ]);

        $this->actingAs($admin);

        Livewire::test(ChannelRequestReview::class)
            ->call('reject', $request->id, '중복 채널')
            ->assertNotified('Channel request rejected');

        $request->refresh();

        $this->assertSame(ChannelRequest::STATUS_REJECTED, $request->status);
        $this->assertSame($admin->id, $request->reviewed_by);
        $this->assertSame('중복 채널', $request->review_note);
        $this->assertDatabaseCount('youtube_channels', 0);
    }

    public function test_a_non_admin_cannot_open_the_channel_request_review_page(): void
    {
        $contributor = User::factory()->create();

        $this->actingAs($contributor)
            ->get('/admin/channel-request-review')
            ->assertForbidden();
    }
}
