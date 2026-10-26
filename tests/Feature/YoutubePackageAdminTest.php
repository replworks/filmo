<?php

namespace Tests\Feature;

use App\Filament\Resources\YoutubeChannels\Pages\CreateYoutubeChannel;
use App\Filament\Resources\YoutubeChannels\Pages\EditYoutubeChannel;
use App\Filament\Resources\YoutubeChannels\Pages\ListYoutubeChannels;
use App\Filament\Resources\YoutubeChannels\Pages\ViewYoutubeChannel;
use App\Filament\Resources\YoutubeChannels\RelationManagers\VideosRelationManager;
use App\Filament\Resources\YoutubeSyncErrors\Pages\ListYoutubeSyncErrors;
use App\Filament\Resources\YoutubeVideos\Pages\CreateYoutubeVideo;
use App\Filament\Resources\YoutubeVideos\Pages\EditYoutubeVideo;
use App\Filament\Resources\YoutubeVideos\Pages\ListYoutubeVideos;
use App\Filament\Resources\YoutubeVideos\Pages\ViewYoutubeVideo;
use App\Filament\Resources\YoutubeVideos\RelationManagers\VideoActorsRelationManager;
use App\Jobs\CollectChannelVideos;
use App\Jobs\ExtractVideoActors;
use App\Models\Actor;
use App\Models\User;
use App\Models\Video;
use Cable8mm\Youtube\Models\YoutubeChannel;
use Cable8mm\Youtube\Models\YoutubeSyncError;
use Cable8mm\Youtube\Models\YoutubeVideo;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Livewire\Livewire;
use Tests\TestCase;

class YoutubePackageAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Bus::fake();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_the_video_catalog_lists_videos_across_channels_and_filters_them(): void
    {
        $firstChannel = $this->channel('First Channel');
        $secondChannel = $this->channel('Second Channel');
        $firstVideo = $this->video($firstChannel, 'video-1', '2026-10-05T00:00:00Z');
        $secondVideo = $this->video($secondChannel, 'video-2', '2026-10-06T00:00:00Z', unavailable: true);

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ListYoutubeVideos::class)
            ->assertActionExists('create')
            ->assertCanSeeTableRecords([$firstVideo, $secondVideo])
            ->filterTable('youtube_channel_id', $firstChannel->id)
            ->assertCanSeeTableRecords([$firstVideo])
            ->assertCanNotSeeTableRecords([$secondVideo])
            ->filterTable('youtube_channel_id')
            ->filterTable('availability', 'unavailable')
            ->assertCanSeeTableRecords([$secondVideo])
            ->assertCanNotSeeTableRecords([$firstVideo]);
    }

    public function test_the_operator_can_create_a_channel_and_initial_collection_starts(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(CreateYoutubeChannel::class)
            ->fillForm([
                'channel_id' => 'UCnewchannel000000000000',
                'handle' => '@newchannel',
                'title' => 'New Channel',
                'uploads_playlist_id' => 'UUnewchannel000000000000',
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoErrors();

        $channel = YoutubeChannel::where('channel_id', 'UCnewchannel000000000000')->firstOrFail();

        $this->assertSame('New Channel', $channel->title);
        Bus::assertDispatched(CollectChannelVideos::class, fn (CollectChannelVideos $job): bool => $job->channelId === $channel->id);
    }

    public function test_the_operator_can_add_a_video_from_a_channel_detail(): void
    {
        $channel = $this->channel();

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(VideosRelationManager::class, [
            'ownerRecord' => $channel,
            'pageClass' => ViewYoutubeChannel::class,
        ])
            ->assertTableActionExists('addVideo')
            ->callTableAction('addVideo', null, [
                'video_id' => 'added-video',
                'title' => 'Added Video',
                'published_at' => '2026-10-10 00:00:00',
            ])
            ->assertHasNoErrors();

        $this->assertDatabaseHas('youtube_videos', [
            'youtube_channel_id' => $channel->id,
            'video_id' => 'added-video',
            'title' => 'Added Video',
        ]);
    }

    public function test_the_operator_can_add_and_remove_an_actor_from_a_video_detail(): void
    {
        $video = Video::find($this->video($this->channel(), 'video-1', '2026-10-05T00:00:00Z')->id);
        $actor = Actor::create(['name' => 'Added Actor', 'profile' => null]);

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(VideoActorsRelationManager::class, [
            'ownerRecord' => $video,
            'pageClass' => ViewYoutubeVideo::class,
        ])
            ->assertTableActionExists('addActor')
            ->callTableAction('addActor', null, ['actor_id' => $actor->id])
            ->assertCanSeeTableRecords([$actor]);

        $this->assertDatabaseHas('actor_video', [
            'actor_id' => $actor->id,
            'youtube_video_id' => $video->id,
        ]);

        Livewire::test(VideoActorsRelationManager::class, [
            'ownerRecord' => $video,
            'pageClass' => ViewYoutubeVideo::class,
        ])
            ->callTableAction('removeActor', $actor);

        $this->assertDatabaseMissing('actor_video', [
            'actor_id' => $actor->id,
            'youtube_video_id' => $video->id,
        ]);
    }

    public function test_the_operator_can_create_a_video_without_starting_extraction(): void
    {
        $channel = $this->channel();

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(CreateYoutubeVideo::class)
            ->fillForm([
                'youtube_channel_id' => $channel->id,
                'video_id' => 'created-video',
                'title' => 'Created Video',
                'description' => 'Created by an Operator',
                'published_at' => '2026-10-05 00:00:00',
            ])
            ->call('create')
            ->assertHasNoErrors();

        $video = YoutubeVideo::where('video_id', 'created-video')->firstOrFail();

        $this->assertSame('Created Video', $video->title);
        $this->assertNull($video->extraction_status);
    }

    public function test_sync_errors_cannot_be_created_or_edited_through_the_admin(): void
    {
        $error = $this->syncError($this->channel(), 'quota_exceeded', 403, 'Quota exhausted.');

        $this->actingAs(User::factory()->admin()->create());

        $this->get('/admin/youtube-sync-errors/create')->assertNotFound();
        $this->get('/admin/youtube-sync-errors/'.$error->id.'/edit')->assertNotFound();
    }

    public function test_the_operator_can_edit_package_video_fields_but_not_extraction_fields(): void
    {
        $channel = $this->channel();
        $video = $this->video($channel, 'video-1', '2026-10-05T00:00:00Z');
        $video->forceFill([
            'extraction_status' => ExtractVideoActors::STATUS_COMPLETED,
            'extraction_error' => null,
            'extracted_at' => Carbon::parse('2026-10-06T00:00:00Z'),
        ])->save();

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(EditYoutubeVideo::class, ['record' => $video->id])
            ->assertFormFieldDoesNotExist('extraction_status')
            ->assertFormFieldDoesNotExist('extraction_error')
            ->assertFormFieldDoesNotExist('extracted_at')
            ->fillForm([
                'title' => 'Edited title',
                'description' => 'Edited description',
                'view_count' => 1234,
                'unavailable_at' => '2026-10-07 00:00:00',
            ])
            ->call('save')
            ->assertHasNoErrors();

        $video->refresh();

        $this->assertSame('Edited title', $video->title);
        $this->assertSame('Edited description', $video->description);
        $this->assertSame(1234, $video->view_count);
        $this->assertNotNull($video->unavailable_at);
        $this->assertSame(ExtractVideoActors::STATUS_COMPLETED, $video->extraction_status);
    }

    public function test_deleting_a_video_removes_its_actor_video_links(): void
    {
        $channel = $this->channel();
        $video = $this->video($channel, 'video-1', '2026-10-05T00:00:00Z');
        $actor = Actor::create(['name' => 'Actor', 'profile' => null]);
        $actor->videos()->attach($video->id);

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ListYoutubeVideos::class)
            ->callTableAction('delete', $video);

        $this->assertDatabaseMissing('youtube_videos', ['id' => $video->id]);
        $this->assertDatabaseMissing('actor_video', [
            'actor_id' => $actor->id,
            'youtube_video_id' => $video->id,
        ]);
        $this->assertDatabaseHas('actors', ['id' => $actor->id]);
    }

    public function test_the_operator_can_view_and_delete_a_sync_error(): void
    {
        $channel = $this->channel();
        $error = $this->syncError($channel, 'quota_exceeded', 403, 'Quota exhausted.');

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ListYoutubeSyncErrors::class)
            ->assertCanSeeTableRecords([$error]);

        $this->get('/admin/youtube-sync-errors/'.$error->id)
            ->assertOk()
            ->assertSee('quota_exceeded');

        Livewire::test(ListYoutubeSyncErrors::class)
            ->callTableAction('delete', $error);

        $this->assertDatabaseMissing('youtube_sync_errors', ['id' => $error->id]);
    }

    public function test_the_operator_can_edit_a_channel(): void
    {
        $channel = $this->channel('Original Channel');

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(EditYoutubeChannel::class, ['record' => $channel->id])
            ->fillForm([
                'title' => 'Edited Channel',
                'handle' => '@edited',
                'is_active' => false,
            ])
            ->call('save')
            ->assertHasNoErrors();

        $channel->refresh();

        $this->assertSame('Edited Channel', $channel->title);
        $this->assertSame('@edited', $channel->handle);
        $this->assertFalse($channel->is_active);
    }

    public function test_deleting_a_channel_cascades_to_videos_and_actor_video_links(): void
    {
        $channel = $this->channel();
        $otherChannel = $this->channel('Other Channel');
        $video = $this->video($channel, 'video-1', '2026-10-05T00:00:00Z');
        $otherVideo = $this->video($otherChannel, 'video-2', '2026-10-05T00:00:00Z');
        $actor = Actor::create(['name' => 'Actor', 'profile' => null]);
        $actor->videos()->attach([$video->id, $otherVideo->id]);

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ListYoutubeChannels::class)
            ->assertActionExists('create')
            ->callTableAction('delete', $channel);

        $this->assertDatabaseMissing('youtube_channels', ['id' => $channel->id]);
        $this->assertDatabaseMissing('youtube_videos', ['id' => $video->id]);
        $this->assertDatabaseMissing('actor_video', [
            'actor_id' => $actor->id,
            'youtube_video_id' => $video->id,
        ]);
        $this->assertDatabaseHas('youtube_videos', ['id' => $otherVideo->id]);
        $this->assertDatabaseHas('actor_video', [
            'actor_id' => $actor->id,
            'youtube_video_id' => $otherVideo->id,
        ]);
    }

    public function test_the_video_view_page_shows_extraction_state_and_the_youtube_url(): void
    {
        $channel = $this->channel();
        $video = $this->video($channel, 'video-1', '2026-10-05T00:00:00Z');
        $video->forceFill(['extraction_status' => ExtractVideoActors::STATUS_COMPLETED])->save();

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ViewYoutubeVideo::class, ['record' => $video->id])
            ->assertSee('Completed')
            ->assertSee('https://www.youtube.com/watch?v=video-1');
    }

    public function test_the_package_admin_pages_require_authentication(): void
    {
        $this->get('/admin/youtube-videos')->assertRedirect('/admin/login');
        $this->get('/admin/youtube-sync-errors')->assertRedirect('/admin/login');
        $this->get('/admin/youtube-channels/1/edit')->assertRedirect('/admin/login');
    }

    private function channel(string $title = 'Test Channel'): YoutubeChannel
    {
        return YoutubeChannel::withoutEvents(fn (): YoutubeChannel => YoutubeChannel::create([
            'channel_id' => 'UC'.substr(md5(uniqid()), 0, 22),
            'title' => $title,
            'uploads_playlist_id' => 'UU'.substr(md5(uniqid()), 0, 22),
        ]));
    }

    private function video(
        YoutubeChannel $channel,
        string $videoId,
        string $publishedAt,
        bool $unavailable = false,
    ): YoutubeVideo {
        return YoutubeVideo::create([
            'youtube_channel_id' => $channel->id,
            'video_id' => $videoId,
            'title' => 'Video '.$videoId,
            'description' => 'Description',
            'published_at' => Carbon::parse($publishedAt),
            'duration' => 'PT10M',
            'unavailable_at' => $unavailable ? Carbon::parse('2026-10-10T00:00:00Z') : null,
        ]);
    }

    private function syncError(
        YoutubeChannel $channel,
        string $reason,
        int $httpCode,
        string $message,
    ): YoutubeSyncError {
        return YoutubeSyncError::create([
            'youtube_channel_id' => $channel->id,
            'reason' => $reason,
            'http_code' => $httpCode,
            'message' => $message,
        ]);
    }
}
