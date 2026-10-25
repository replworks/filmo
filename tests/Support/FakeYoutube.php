<?php

namespace Tests\Support;

use Cable8mm\Youtube\Youtube;
use Throwable;

class FakeYoutube extends Youtube
{
    /**
     * @var array<int, array{0: string, 1: string, 2: int}>
     */
    public array $playlistCalls = [];

    /**
     * @var array<int, array<int, string>>
     */
    public array $videoCalls = [];

    /**
     * @param  array<string, array<string, mixed>|Throwable>  $pages  Keyed by page token.
     * @param  array<int, object>  $videoItems
     */
    public function __construct(
        public array $pages = [],
        public array $videoItems = [],
        public ?object $channelItem = null,
    ) {}

    public function getChannelById(mixed $id, array $optionalParams = [], array $part = [])
    {
        return $this->channelItem;
    }

    public function getPlaylistItemsByPlaylistId(string $playlistId, string $pageToken = '', int $maxResults = 50, array $part = [])
    {
        $this->playlistCalls[] = [$playlistId, $pageToken, $maxResults];

        $page = $this->pages[$pageToken] ?? ['results' => [], 'info' => ['nextPageToken' => false]];

        if ($page instanceof Throwable) {
            throw $page;
        }

        return $page;
    }

    public function getVideoInfo(array|string $vId, array $part = [])
    {
        $ids = is_array($vId) ? $vId : [$vId];
        $this->videoCalls[] = $ids;

        return array_values(array_filter(
            $this->videoItems,
            fn (object $item): bool => in_array($item->id, $ids, true),
        ));
    }
}
