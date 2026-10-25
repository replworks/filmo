<?php

namespace Tests\Feature;

use App\Jobs\CollectChannelVideos;
use Cable8mm\Youtube\Exceptions\YoutubeApiException;
use Cable8mm\Youtube\Models\YoutubeChannel;
use Cable8mm\Youtube\Models\YoutubeVideo;
use Cable8mm\Youtube\Sync\QuotaBlock;
use Cable8mm\Youtube\Youtube;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Tests\Support\FakeYoutube;
use Tests\TestCase;

class CollectChannelVideosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Bus::fake();
    }

    protected function tearDown(): void
    {
        (new QuotaBlock)->clear();

        parent::tearDown();
    }

    public function test_it_collects_every_page_and_stores_returned_video_metadata(): void
    {
        $channel = $this->channel();

        $fake = new FakeYoutube(
            pages: [
                '' => [
                    'results' => [$this->playlistItem('video-1'), $this->playlistItem('video-2')],
                    'info' => ['nextPageToken' => 'PAGE-2'],
                ],
                'PAGE-2' => [
                    'results' => [$this->playlistItem('video-3')],
                    'info' => ['nextPageToken' => false],
                ],
            ],
            videoItems: [$this->video('video-1'), $this->video('video-2'), $this->video('video-3')],
        );

        $this->app->instance(Youtube::class, $fake);

        (new CollectChannelVideos($channel->id))->handle();

        $this->assertSame([
            ['UUuploads', '', 50],
            ['UUuploads', 'PAGE-2', 50],
        ], $fake->playlistCalls);
        $this->assertSame([['video-1', 'video-2'], ['video-3']], $fake->videoCalls);
        $this->assertDatabaseCount('youtube_videos', 3);

        $video = YoutubeVideo::where('video_id', 'video-1')->firstOrFail();

        $this->assertSame($channel->id, $video->youtube_channel_id);
        $this->assertSame('Title video-1', $video->title);
        $this->assertSame('Description video-1', $video->description);
        $this->assertSame('PT10M', $video->duration);
        $this->assertSame('https://example.com/video-1.jpg', $video->thumbnail_url);
        $this->assertSame(1000, $video->view_count);
        $this->assertSame(10, $video->like_count);
        $this->assertSame(2, $video->comment_count);
        $this->assertSame('2026-10-01T00:00:00+00:00', $video->published_at->toIso8601String());
        $this->assertNull($video->unavailable_at);

        $channel->refresh();

        $this->assertSame('ok', $channel->sync_status);
        $this->assertNotNull($channel->last_synced_at);
        $this->assertNull($channel->last_error_reason);
    }

    public function test_it_keeps_videos_from_earlier_pages_when_a_later_page_fails(): void
    {
        $channel = $this->channel();

        $fake = new FakeYoutube(
            pages: [
                '' => [
                    'results' => [$this->playlistItem('video-1')],
                    'info' => ['nextPageToken' => 'PAGE-2'],
                ],
                'PAGE-2' => new YoutubeApiException('Curl Error : Could not resolve host: www.googleapis.com'),
            ],
            videoItems: [$this->video('video-1')],
        );

        $this->app->instance(Youtube::class, $fake);

        (new CollectChannelVideos($channel->id))->handle();

        $this->assertDatabaseHas('youtube_videos', ['video_id' => 'video-1']);
        $this->assertNull(YoutubeVideo::where('video_id', 'video-1')->firstOrFail()->unavailable_at);

        $channel->refresh();

        $this->assertSame('failed', $channel->sync_status);
        $this->assertSame('transport', $channel->last_error_reason);
        $this->assertDatabaseHas('youtube_sync_errors', [
            'youtube_channel_id' => $channel->id,
            'reason' => 'transport',
        ]);
    }

    public function test_it_marks_videos_missing_from_a_complete_listing_unavailable_and_clears_the_state_when_returned(): void
    {
        $channel = $this->channel();
        $this->videoRecord($channel, 'video-1');
        $this->videoRecord($channel, 'video-2');
        $this->videoRecord($channel, 'video-3', Carbon::now()->subWeek());

        $this->app->instance(Youtube::class, new FakeYoutube(
            pages: ['' => ['results' => [$this->playlistItem('video-1')], 'info' => ['nextPageToken' => false]]],
            videoItems: [$this->video('video-1')],
        ));

        (new CollectChannelVideos($channel->id))->handle();

        $this->assertNull(YoutubeVideo::where('video_id', 'video-1')->firstOrFail()->unavailable_at);
        $this->assertNotNull(YoutubeVideo::where('video_id', 'video-2')->firstOrFail()->unavailable_at);
        $this->assertNotNull(YoutubeVideo::where('video_id', 'video-3')->firstOrFail()->unavailable_at);

        $this->app->instance(Youtube::class, new FakeYoutube(
            pages: ['' => ['results' => [$this->playlistItem('video-1'), $this->playlistItem('video-2')], 'info' => ['nextPageToken' => false]]],
            videoItems: [$this->video('video-1'), $this->video('video-2')],
        ));

        (new CollectChannelVideos($channel->id))->handle();

        $this->assertNull(YoutubeVideo::where('video_id', 'video-1')->firstOrFail()->unavailable_at);
        $this->assertNull(YoutubeVideo::where('video_id', 'video-2')->firstOrFail()->unavailable_at);
        $this->assertNotNull(YoutubeVideo::where('video_id', 'video-3')->firstOrFail()->unavailable_at);
    }

    public function test_a_limited_collection_does_not_flag_unread_videos_as_unavailable(): void
    {
        $channel = $this->channel();
        $unread = $this->videoRecord($channel, 'video-unread');

        $this->app->instance(Youtube::class, new FakeYoutube(
            pages: [
                '' => [
                    'results' => [$this->playlistItem('video-1')],
                    'info' => ['nextPageToken' => 'PAGE-2'],
                ],
            ],
            videoItems: [$this->video('video-1')],
        ));

        (new CollectChannelVideos($channel->id, maxPages: 1))->handle();

        $this->assertNull($unread->refresh()->unavailable_at);
        $this->assertSame('ok', $channel->refresh()->sync_status);
    }

    public function test_it_records_quota_exhaustion_and_pauses_collection(): void
    {
        $channel = $this->channel();
        $documented = json_decode(file_get_contents(base_path('tests/Fixtures/youtube/quota-exceeded-403.json')));

        $this->app->instance(Youtube::class, new FakeYoutube(
            pages: ['' => YoutubeApiException::fromApiError($documented->error)],
        ));

        (new CollectChannelVideos($channel->id))->handle();

        $channel->refresh();

        $this->assertSame('paused', $channel->sync_status);
        $this->assertSame('quota_exceeded', $channel->last_error_reason);
        $this->assertTrue((new QuotaBlock)->isActive());
        $this->assertDatabaseHas('youtube_sync_errors', [
            'youtube_channel_id' => $channel->id,
            'reason' => 'quota_exceeded',
            'http_code' => 403,
        ]);
    }

    public function test_it_resolves_a_missing_uploads_playlist_before_collecting(): void
    {
        $channel = $this->channel(['uploads_playlist_id' => null, 'handle' => null, 'title' => 'Old title']);

        $this->app->instance(Youtube::class, new FakeYoutube(
            pages: ['' => ['results' => [$this->playlistItem('video-1')], 'info' => ['nextPageToken' => false]]],
            videoItems: [$this->video('video-1')],
            channelItem: (object) [
                'id' => $channel->channel_id,
                'snippet' => (object) ['title' => 'Resolved title', 'customUrl' => '@resolved'],
                'contentDetails' => (object) [
                    'relatedPlaylists' => (object) ['uploads' => 'UUresolved'],
                ],
            ],
        ));

        (new CollectChannelVideos($channel->id))->handle();

        $channel->refresh();

        $this->assertSame('UUresolved', $channel->uploads_playlist_id);
        $this->assertSame('Resolved title', $channel->title);
        $this->assertSame('@resolved', $channel->handle);
        $this->assertDatabaseHas('youtube_videos', ['video_id' => 'video-1']);
    }

    public function test_it_records_a_channel_that_cannot_be_resolved(): void
    {
        $channel = $this->channel(['uploads_playlist_id' => null]);

        $this->app->instance(Youtube::class, new FakeYoutube(channelItem: null));

        (new CollectChannelVideos($channel->id))->handle();

        $channel->refresh();

        $this->assertSame('failed', $channel->sync_status);
        $this->assertSame('channel_not_found', $channel->last_error_reason);
        $this->assertDatabaseHas('youtube_sync_errors', [
            'youtube_channel_id' => $channel->id,
            'reason' => 'channel_not_found',
        ]);
    }

    private function channel(array $attributes = []): YoutubeChannel
    {
        return YoutubeChannel::create(array_merge([
            'channel_id' => 'UCaaaaaaaaaaaaaaaaaaaaaa',
            'handle' => '@test',
            'title' => 'Test Channel',
            'uploads_playlist_id' => 'UUuploads',
        ], $attributes));
    }

    private function videoRecord(YoutubeChannel $channel, string $videoId, ?Carbon $unavailableAt = null): YoutubeVideo
    {
        return YoutubeVideo::create([
            'youtube_channel_id' => $channel->id,
            'video_id' => $videoId,
            'title' => 'Stored '.$videoId,
            'published_at' => Carbon::parse('2026-09-01T00:00:00Z'),
            'unavailable_at' => $unavailableAt,
        ]);
    }

    private function playlistItem(string $videoId): object
    {
        return (object) [
            'contentDetails' => (object) ['videoId' => $videoId],
        ];
    }

    private function video(string $videoId): object
    {
        return (object) [
            'id' => $videoId,
            'snippet' => (object) [
                'title' => 'Title '.$videoId,
                'description' => 'Description '.$videoId,
                'publishedAt' => '2026-10-01T00:00:00Z',
                'thumbnails' => (object) [
                    'high' => (object) ['url' => 'https://example.com/'.$videoId.'.jpg'],
                ],
            ],
            'contentDetails' => (object) ['duration' => 'PT10M'],
            'statistics' => (object) [
                'viewCount' => '1000',
                'likeCount' => '10',
                'commentCount' => '2',
            ],
        ];
    }
}
