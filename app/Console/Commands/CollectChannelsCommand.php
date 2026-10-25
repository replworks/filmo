<?php

namespace App\Console\Commands;

use App\Jobs\CollectChannelVideos;
use Cable8mm\Youtube\Models\YoutubeChannel;
use Cable8mm\Youtube\Sync\QuotaBlock;
use Illuminate\Console\Command;

class CollectChannelsCommand extends Command
{
    protected $signature = 'filmo:collect-channels
        {--channel=* : Channel ID to collect, even when inactive}
        {--pages= : Maximum uploads pages per Channel, instead of every page}';

    protected $description = 'Queue a full Video collection for each registered Channel';

    public function handle(): int
    {
        $quota = new QuotaBlock;

        if ($quota->isActive()) {
            $this->line('collection paused until '.$quota->until()?->toIso8601String());

            return self::SUCCESS;
        }

        $maxPages = $this->option('pages') === null
            ? null
            : max(1, (int) $this->option('pages'));

        $channelIds = $this->option('channel');

        if ($channelIds === []) {
            $channels = YoutubeChannel::where('is_active', true)->orderBy('id')->get();
        } else {
            $found = YoutubeChannel::whereIn('channel_id', $channelIds)->get()->keyBy('channel_id');
            $channels = [];

            foreach ($channelIds as $channelId) {
                if (isset($found[$channelId])) {
                    $channels[] = $found[$channelId];
                } else {
                    $this->line('unknown channel '.$channelId);
                }
            }
        }

        foreach ($channels as $channel) {
            CollectChannelVideos::dispatch($channel->id, $maxPages);
        }

        $this->info('Queued collection for '.count($channels).' channel(s).');

        return self::SUCCESS;
    }
}
