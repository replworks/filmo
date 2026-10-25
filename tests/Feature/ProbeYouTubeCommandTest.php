<?php

namespace Tests\Feature;

use Cable8mm\Youtube\Exceptions\YoutubeApiException;
use Cable8mm\Youtube\Sync\ChannelInputResolver;
use Cable8mm\Youtube\Sync\FailureReason;
use Cable8mm\Youtube\Youtube;
use Mockery\MockInterface;
use Tests\TestCase;

class ProbeYouTubeCommandTest extends TestCase
{
    private string $output;

    protected function setUp(): void
    {
        parent::setUp();

        $this->output = tempnam(sys_get_temp_dir(), 'filmo-probe-');
        config(['youtube.key' => 'test-key-0123456789']);
    }

    protected function tearDown(): void
    {
        @unlink($this->output);

        parent::tearDown();
    }

    public function test_it_records_channel_pages_and_video_metadata(): void
    {
        $channel = $this->channel();

        $this->mock(Youtube::class, function (MockInterface $mock) use ($channel): void {
            $mock->shouldReceive('getChannelByHandle')
                ->once()
                ->with('@YouTube', [], ChannelInputResolver::PART)
                ->andReturn($channel);

            $mock->shouldReceive('getPlaylistItemsByPlaylistId')
                ->once()
                ->with('UUuploads', '', 50)
                ->andReturn([
                    'results' => [$this->playlistItem('video-1'), $this->playlistItem('video-2')],
                    'info' => ['totalResults' => 3, 'nextPageToken' => 'TOKEN-2'],
                ]);

            $mock->shouldReceive('getPlaylistItemsByPlaylistId')
                ->once()
                ->with('UUuploads', 'TOKEN-2', 50)
                ->andReturn([
                    'results' => [$this->playlistItem('video-3')],
                    'info' => ['totalResults' => 3, 'nextPageToken' => false],
                ]);

            $mock->shouldReceive('getVideoInfo')
                ->once()
                ->with(['video-1', 'video-2', 'video-3'], ['id', 'snippet', 'contentDetails', 'statistics', 'status'])
                ->andReturn([$this->video('video-1')]);

            $mock->shouldReceive('getVideoInfo')
                ->once()
                ->with(['AAAAAAAAAAA'], ['id', 'snippet', 'contentDetails', 'statistics', 'status'])
                ->andReturn([]);
        });

        $this->artisan('filmo:probe-youtube', [
            '--channel' => ['@YouTube'],
            '--pages' => 2,
            '--output' => $this->output,
        ])->assertSuccessful();

        $observation = json_decode(file_get_contents($this->output), true);

        $this->assertSame('resolved', $observation['references'][0]['outcome']);
        $this->assertSame('UCaaaaaaaaaaaaaaaaaaaaaa', $observation['references'][0]['channel']['id']);
        $this->assertSame('UUuploads', $observation['references'][0]['channel']['uploads_playlist_id']);
        $this->assertSame('getChannelByHandle', $observation['references'][0]['lookup']['method']);

        $this->assertCount(2, $observation['pages']);
        $this->assertSame(2, $observation['pages'][0]['item_count']);
        $this->assertSame('TOKEN-2', $observation['pages'][0]['next_page_token']);
        $this->assertNull($observation['pages'][1]['next_page_token']);
        $this->assertSame('video-1', $observation['pages'][0]['items'][0]['video_id']);

        $this->assertSame('PT10M', $observation['videos'][0]['duration']);
        $this->assertSame('1000', $observation['videos'][0]['view_count']);
        $this->assertSame('public', $observation['videos'][0]['privacy_status']);

        $this->assertSame(0, $observation['missing_video']['returned_count']);
    }

    public function test_it_records_unsupported_and_missing_channels_without_calling_the_api_for_unsupported_input(): void
    {
        $this->mock(Youtube::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getChannelByHandle')
                ->once()
                ->with('@missing', [], ChannelInputResolver::PART)
                ->andReturnNull();
        });

        $this->artisan('filmo:probe-youtube', [
            '--channel' => ['not a channel', '@missing'],
            '--pages' => 1,
            '--output' => $this->output,
        ])->assertSuccessful();

        $observation = json_decode(file_get_contents($this->output), true);

        $this->assertSame('unsupported', $observation['references'][0]['outcome']);
        $this->assertNull($observation['references'][0]['lookup']);
        $this->assertSame('not_found', $observation['references'][1]['outcome']);
        $this->assertSame([], $observation['pages']);
    }

    public function test_it_records_an_invalid_key_failure(): void
    {
        $this->mock(Youtube::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getChannelById')
                ->once()
                ->andThrow(new YoutubeApiException('Error 400 API key not valid. Please pass a valid API key. : keyInvalid', 400));
        });

        $this->artisan('filmo:probe-youtube', [
            '--channel' => ['UCaaaaaaaaaaaaaaaaaaaaaa'],
            '--output' => $this->output,
        ])->assertSuccessful();

        $observation = json_decode(file_get_contents($this->output), true);

        $this->assertSame('error', $observation['references'][0]['outcome']);
        $this->assertSame(FailureReason::INVALID_KEY, $observation['references'][0]['failure']['reason']);
        $this->assertSame(400, $observation['references'][0]['failure']['http_code']);
        $this->assertSame([], $observation['pages']);
        $this->assertNull($observation['missing_video']);
    }

    public function test_it_stops_requesting_when_quota_is_exhausted(): void
    {
        $documented = json_decode(file_get_contents(__DIR__.'/../Fixtures/youtube/quota-exceeded-403.json'));

        $this->mock(Youtube::class, function (MockInterface $mock) use ($documented): void {
            $mock->shouldReceive('getChannelByHandle')
                ->once()
                ->andThrow(YoutubeApiException::fromApiError($documented->error));

            $mock->shouldNotReceive('getPlaylistItemsByPlaylistId');
            $mock->shouldNotReceive('getVideoInfo');
        });

        $this->artisan('filmo:probe-youtube', [
            '--channel' => ['@YouTube'],
            '--output' => $this->output,
        ])->assertSuccessful();

        $observation = json_decode(file_get_contents($this->output), true);

        $this->assertSame(FailureReason::QUOTA_EXCEEDED, $observation['references'][0]['failure']['reason']);
        $this->assertNull($observation['missing_video']);
    }

    public function test_it_keeps_the_first_page_when_the_next_page_fails(): void
    {
        $this->mock(Youtube::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getChannelByHandle')
                ->once()
                ->andReturn($this->channel());

            $mock->shouldReceive('getPlaylistItemsByPlaylistId')
                ->once()
                ->with('UUuploads', '', 50)
                ->andReturn([
                    'results' => [$this->playlistItem('video-1')],
                    'info' => ['totalResults' => 2, 'nextPageToken' => 'TOKEN-2'],
                ]);

            $mock->shouldReceive('getPlaylistItemsByPlaylistId')
                ->once()
                ->with('UUuploads', 'TOKEN-2', 50)
                ->andThrow(new YoutubeApiException('Curl Error : Could not resolve host: www.googleapis.com'));

            $mock->shouldReceive('getVideoInfo')
                ->once()
                ->andReturn([$this->video('video-1')]);

            $mock->shouldReceive('getVideoInfo')
                ->with(['AAAAAAAAAAA'], ['id', 'snippet', 'contentDetails', 'statistics', 'status'])
                ->andReturn([]);
        });

        $this->artisan('filmo:probe-youtube', [
            '--channel' => ['@YouTube'],
            '--pages' => 2,
            '--output' => $this->output,
        ])->assertSuccessful();

        $observation = json_decode(file_get_contents($this->output), true);

        $this->assertSame('ok', $observation['pages'][0]['outcome']);
        $this->assertSame('error', $observation['pages'][1]['outcome']);
        $this->assertSame(FailureReason::TRANSPORT, $observation['pages'][1]['failure']['reason']);
    }

    public function test_it_refuses_to_run_without_an_api_key(): void
    {
        config(['youtube.key' => '']);

        $this->artisan('filmo:probe-youtube', [
            '--channel' => ['@YouTube'],
            '--output' => $this->output,
        ])->assertFailed();
    }

    public function test_it_refuses_to_run_without_a_channel(): void
    {
        $this->artisan('filmo:probe-youtube', [
            '--output' => $this->output,
        ])->assertFailed();
    }

    private function channel(): object
    {
        return (object) [
            'id' => 'UCaaaaaaaaaaaaaaaaaaaaaa',
            'snippet' => (object) [
                'title' => 'Test Channel',
                'customUrl' => '@YouTube',
                'publishedAt' => '2005-02-15T00:00:00Z',
            ],
            'contentDetails' => (object) [
                'relatedPlaylists' => (object) ['uploads' => 'UUuploads'],
            ],
            'statistics' => (object) [
                'subscriberCount' => '100',
                'hiddenSubscriberCount' => false,
                'videoCount' => '3',
                'viewCount' => '1000',
            ],
        ];
    }

    private function playlistItem(string $videoId): object
    {
        return (object) [
            'id' => 'playlist-item-'.$videoId,
            'snippet' => (object) [
                'title' => 'Title '.$videoId,
                'channelTitle' => 'Test Channel',
                'publishedAt' => '2026-10-01T00:00:00Z',
                'thumbnails' => (object) ['high' => (object) ['url' => 'https://example.com/'.$videoId.'.jpg']],
            ],
            'contentDetails' => (object) [
                'videoId' => $videoId,
                'videoPublishedAt' => '2026-09-30T00:00:00Z',
            ],
            'status' => (object) ['privacyStatus' => 'public'],
        ];
    }

    private function video(string $videoId): object
    {
        return (object) [
            'id' => $videoId,
            'snippet' => (object) [
                'title' => 'Title '.$videoId,
                'description' => 'Description',
                'publishedAt' => '2026-09-30T00:00:00Z',
                'thumbnails' => (object) ['high' => (object) ['url' => 'https://example.com/'.$videoId.'.jpg']],
            ],
            'contentDetails' => (object) [
                'duration' => 'PT10M',
                'definition' => 'hd',
                'caption' => 'false',
                'licensedContent' => true,
            ],
            'statistics' => (object) [
                'viewCount' => '1000',
                'likeCount' => '10',
                'commentCount' => '2',
            ],
            'status' => (object) [
                'privacyStatus' => 'public',
                'uploadStatus' => 'processed',
                'embeddable' => true,
                'madeForKids' => false,
            ],
        ];
    }
}
