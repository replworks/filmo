<?php

namespace Tests\Feature;

use App\Jobs\CollectChannelVideos;
use Cable8mm\Youtube\Models\YoutubeChannel;
use Cable8mm\Youtube\Sync\QuotaBlock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class CollectChannelsCommandTest extends TestCase
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

    public function test_it_queues_collection_for_every_active_channel(): void
    {
        $first = $this->channel('UCaaaaaaaaaaaaaaaaaaaaaa');
        $second = $this->channel('UCbbbbbbbbbbbbbbbbbbbbbb');
        $this->channel('UCcccccccccccccccccccccc', ['is_active' => false]);

        $this->artisan('filmo:collect-channels')
            ->expectsOutputToContain('Queued collection for 2 channel(s).')
            ->assertSuccessful();

        Bus::assertDispatchedTimes(CollectChannelVideos::class, 2);
        Bus::assertDispatched(CollectChannelVideos::class, fn (CollectChannelVideos $job): bool => $job->channelId === $first->id && $job->maxPages === null);
        Bus::assertDispatched(CollectChannelVideos::class, fn (CollectChannelVideos $job): bool => $job->channelId === $second->id && $job->maxPages === null);
    }

    public function test_it_queues_collection_for_named_channels_even_when_inactive(): void
    {
        $channel = $this->channel('UCdddddddddddddddddddddd', ['is_active' => false]);

        $this->artisan('filmo:collect-channels', ['--channel' => [$channel->channel_id]])
            ->expectsOutputToContain('Queued collection for 1 channel(s).')
            ->assertSuccessful();

        Bus::assertDispatched(CollectChannelVideos::class, fn (CollectChannelVideos $job): bool => $job->channelId === $channel->id);
    }

    public function test_it_caps_the_pages_when_requested(): void
    {
        $channel = $this->channel('UCeeeeeeeeeeeeeeeeeeeeee');

        $this->artisan('filmo:collect-channels', ['--pages' => 2])->assertSuccessful();

        Bus::assertDispatched(CollectChannelVideos::class, fn (CollectChannelVideos $job): bool => $job->channelId === $channel->id && $job->maxPages === 2);
    }

    public function test_it_does_not_queue_while_quota_is_exhausted(): void
    {
        $this->channel('UCffffffffffffffffffffff');
        (new QuotaBlock)->start();

        $this->artisan('filmo:collect-channels')
            ->expectsOutputToContain('collection paused until')
            ->assertSuccessful();

        Bus::assertNothingDispatched();
    }

    private function channel(string $channelId, array $attributes = []): YoutubeChannel
    {
        return YoutubeChannel::withoutEvents(fn (): YoutubeChannel => YoutubeChannel::create(array_merge([
            'channel_id' => $channelId,
            'title' => 'Channel '.$channelId,
            'uploads_playlist_id' => 'UU'.substr($channelId, 2),
        ], $attributes)));
    }
}
