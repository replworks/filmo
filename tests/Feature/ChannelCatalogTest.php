<?php

namespace Tests\Feature;

use App\Filament\Resources\Actors\ActorResource;
use App\Filament\Resources\YoutubeChannels\Pages\ListYoutubeChannels;
use App\Filament\Resources\YoutubeChannels\Pages\ViewYoutubeChannel;
use App\Filament\Resources\YoutubeChannels\RelationManagers\VideosRelationManager;
use App\Models\User;
use Cable8mm\Youtube\Models\YoutubeChannel;
use Cable8mm\Youtube\Models\YoutubeVideo;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Livewire\Livewire;
use Tests\TestCase;

class ChannelCatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Bus::fake();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get('/admin/youtube-channels')->assertRedirect('/admin/login');
    }

    public function test_the_channel_list_shows_channels_and_their_collection_state(): void
    {
        $healthy = $this->channel('Healthy Channel', [
            'handle' => '@healthy',
            'sync_status' => 'ok',
            'last_synced_at' => Carbon::parse('2026-10-25T18:00:00Z'),
        ]);
        $failed = $this->channel('Failed Channel', [
            'sync_status' => 'failed',
            'last_error_reason' => 'transport',
        ]);
        $paused = $this->channel('Paused Channel', [
            'sync_status' => 'paused',
            'last_error_reason' => 'quota_exceeded',
        ]);

        $this->video($healthy, 'video-1');
        $this->video($healthy, 'video-2');

        $this->actingAs(User::factory()->create());

        Livewire::test(ListYoutubeChannels::class)
            ->assertActionExists('actors')
            ->assertActionHasUrl('actors', ActorResource::getUrl())
            ->assertCanSeeTableRecords([$healthy, $failed, $paused])
            ->assertTableColumnStateSet('title', 'Healthy Channel', $healthy)
            ->assertTableColumnStateSet('handle', '@healthy', $healthy)
            ->assertTableColumnStateSet('collection_state', 'OK', $healthy)
            ->assertTableColumnStateSet('collection_state', 'Failed (transport)', $failed)
            ->assertTableColumnStateSet('collection_state', 'Paused (quota exhausted)', $paused)
            ->assertTableColumnStateSet('videos_count', 2, $healthy)
            ->assertTableColumnStateSet('last_synced_at', $healthy->last_synced_at, $healthy);
    }

    public function test_the_channel_list_has_an_empty_state(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/admin/youtube-channels')
            ->assertOk()
            ->assertSee('No Channels');
    }

    public function test_the_channel_view_shows_its_videos_and_availability(): void
    {
        $channel = $this->channel('Catalog Channel', ['sync_status' => 'ok']);
        $available = $this->video($channel, 'available-video', 'Available Video');
        $unavailable = $this->video($channel, 'unavailable-video', 'Unavailable Video', Carbon::now());
        $other = $this->channel('Other Channel', ['sync_status' => 'ok']);
        $otherVideo = $this->video($other, 'other-video', 'Other Video');

        $this->actingAs(User::factory()->create());

        $this->get('/admin/youtube-channels/'.$channel->id)
            ->assertOk()
            ->assertSee('Catalog Channel')
            ->assertSee('OK')
            ->assertSee($channel->channel_id);

        Livewire::test(ViewYoutubeChannel::class, ['record' => $channel->getRouteKey()])
            ->assertSee('Catalog Channel')
            ->assertSeeLivewire(VideosRelationManager::class);

        Livewire::test(VideosRelationManager::class, [
            'ownerRecord' => $channel,
            'pageClass' => ViewYoutubeChannel::class,
        ])
            ->assertCanSeeTableRecords([$available, $unavailable])
            ->assertCanNotSeeTableRecords([$otherVideo])
            ->assertTableColumnStateSet('availability', 'Available', $available)
            ->assertTableColumnStateSet('availability', 'Unavailable', $unavailable)
            ->assertTableColumnStateSet('youtube_url', 'https://www.youtube.com/watch?v='.$available->video_id, $available);
    }

    public function test_the_video_list_is_limited_to_fifty_videos_sorted_newest_first(): void
    {
        $channel = $this->channel('Large Channel', ['sync_status' => 'ok']);
        $newest = Carbon::parse('2026-10-25T00:00:00Z');

        $videos = [];

        for ($index = 0; $index < 51; $index++) {
            $videos[] = $this->video(
                $channel,
                'video-'.$index,
                'Video '.$index,
                null,
                $newest->copy()->subMinutes($index),
            );
        }

        $this->actingAs(User::factory()->create());

        Livewire::test(VideosRelationManager::class, [
            'ownerRecord' => $channel,
            'pageClass' => ViewYoutubeChannel::class,
        ])
            ->assertCountTableRecords(51)
            ->assertCanSeeTableRecords([$videos[0], $videos[49]], inOrder: true)
            ->assertCanNotSeeTableRecords([$videos[50]]);
    }

    public function test_the_video_list_has_an_empty_state(): void
    {
        $channel = $this->channel('Empty Channel', ['sync_status' => 'ok']);

        $this->actingAs(User::factory()->create());

        Livewire::test(VideosRelationManager::class, [
            'ownerRecord' => $channel,
            'pageClass' => ViewYoutubeChannel::class,
        ])
            ->assertCountTableRecords(0)
            ->assertSee('No Videos');
    }

    public function test_the_import_page_and_channel_list_link_to_each_other(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/admin/channel-import')
            ->assertOk()
            ->assertSee('Channel List')
            ->assertSee('/admin/youtube-channels');

        $this->get('/admin/youtube-channels')
            ->assertOk()
            ->assertSee('Import Channels')
            ->assertSee('/admin/channel-import');
    }

    private function channel(string $title, array $attributes = []): YoutubeChannel
    {
        return YoutubeChannel::withoutEvents(fn (): YoutubeChannel => YoutubeChannel::create(array_merge([
            'channel_id' => 'UC'.substr(md5($title), 0, 22),
            'handle' => '@'.str($title)->slug(),
            'title' => $title,
            'uploads_playlist_id' => 'UU'.substr(md5($title), 0, 22),
        ], $attributes)));
    }

    private function video(
        YoutubeChannel $channel,
        string $videoId,
        ?string $title = null,
        ?Carbon $unavailableAt = null,
        ?Carbon $publishedAt = null,
    ): YoutubeVideo {
        return YoutubeVideo::create([
            'youtube_channel_id' => $channel->id,
            'video_id' => $videoId,
            'title' => $title ?? 'Video '.$videoId,
            'description' => 'Description '.$videoId,
            'published_at' => $publishedAt ?? Carbon::parse('2026-10-01T00:00:00Z'),
            'duration' => 'PT10M',
            'thumbnail_url' => 'https://example.com/'.$videoId.'.jpg',
            'view_count' => 1000,
            'unavailable_at' => $unavailableAt,
        ]);
    }
}
