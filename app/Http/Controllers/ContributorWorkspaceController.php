<?php

namespace App\Http\Controllers;

use App\Jobs\CollectChannelVideos;
use Cable8mm\Youtube\Exceptions\YoutubeApiException;
use Cable8mm\Youtube\Models\YoutubeChannel;
use Cable8mm\Youtube\Models\YoutubeVideo;
use Cable8mm\Youtube\Support\UrlParser;
use Cable8mm\Youtube\Sync\ChannelInputResolver;
use Cable8mm\Youtube\Youtube;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;
use Throwable;

class ContributorWorkspaceController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('contributor/index', [
            'status' => $request->session()->get('status'),
        ]);
    }

    public function storeChannel(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'channel' => ['required', 'string', 'max:2048'],
        ]);

        try {
            $resolved = app(ChannelInputResolver::class)->resolve($data['channel']);
        } catch (YoutubeApiException $e) {
            return back()->withErrors(['channel' => 'YouTube에서 채널을 확인하지 못했습니다.'])->withInput();
        }

        if (! is_object($resolved)) {
            return back()->withErrors(['channel' => '채널을 찾을 수 없습니다.'])->withInput();
        }

        $channel = YoutubeChannel::withoutEvents(fn (): YoutubeChannel => YoutubeChannel::updateOrCreate(
            ['channel_id' => $resolved->id],
            [
                'handle' => $resolved->snippet->customUrl ?? null,
                'title' => $resolved->snippet->title ?? $resolved->id,
                'uploads_playlist_id' => $resolved->contentDetails->relatedPlaylists->uploads ?? null,
                'is_active' => true,
            ],
        ));

        CollectChannelVideos::dispatch($channel->id);

        return redirect()
            ->route('contributor.index')
            ->with('status', '채널을 추가했습니다. 영상 수집을 시작합니다.');
    }

    public function storeVideo(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'url' => ['required', 'string', 'max:2048'],
        ]);

        try {
            $videoId = UrlParser::videoId($data['url']);
        } catch (InvalidArgumentException) {
            return back()->withErrors(['url' => 'YouTube 영상 URL을 확인해 주세요.'])->withInput();
        }

        try {
            $youtube = app(Youtube::class);
            $items = $youtube->getVideoInfo($videoId, ['id', 'snippet', 'contentDetails', 'statistics']);
            $item = is_array($items) ? ($items[0] ?? null) : $items;

            if (! is_object($item)) {
                return back()->withErrors(['url' => '영상을 찾을 수 없습니다.'])->withInput();
            }

            $channelItem = $youtube->getChannelById(
                $item->snippet->channelId,
                [],
                ['id', 'snippet', 'contentDetails'],
            );

            if (! is_object($channelItem)) {
                return back()->withErrors(['url' => '영상의 채널을 찾을 수 없습니다.'])->withInput();
            }

            $channel = YoutubeChannel::withoutEvents(fn (): YoutubeChannel => YoutubeChannel::updateOrCreate(
                ['channel_id' => $channelItem->id],
                [
                    'handle' => $channelItem->snippet->customUrl ?? null,
                    'title' => $channelItem->snippet->title ?? $channelItem->id,
                    'uploads_playlist_id' => $channelItem->contentDetails->relatedPlaylists->uploads ?? null,
                    'is_active' => true,
                ],
            ));

            YoutubeVideo::updateOrCreate(
                ['video_id' => $item->id],
                [
                    'youtube_channel_id' => $channel->id,
                    'title' => $item->snippet->title ?? $item->id,
                    'description' => $item->snippet->description ?? null,
                    'published_at' => Carbon::parse($item->snippet->publishedAt),
                    'duration' => $item->contentDetails->duration ?? null,
                    'thumbnail_url' => $item->snippet->thumbnails->high->url ?? $item->snippet->thumbnails->default->url ?? null,
                    'view_count' => $item->statistics->viewCount ?? null,
                    'like_count' => $item->statistics->likeCount ?? null,
                    'comment_count' => $item->statistics->commentCount ?? null,
                    'unavailable_at' => null,
                ],
            );
        } catch (Throwable) {
            return back()->withErrors(['url' => 'YouTube 영상을 확인하지 못했습니다.'])->withInput();
        }

        return redirect()
            ->route('contributor.index')
            ->with('status', '영상을 추가했습니다.');
    }
}
