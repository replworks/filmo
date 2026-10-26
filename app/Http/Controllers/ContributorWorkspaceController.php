<?php

namespace App\Http\Controllers;

use App\Models\Actor;
use App\Models\Contribution;
use App\Models\Video;
use App\Services\Contribution\ContributionManager;
use App\Support\CatalogOwner;
use Cable8mm\Youtube\Exceptions\YoutubeApiException;
use Cable8mm\Youtube\Models\YoutubeChannel;
use Cable8mm\Youtube\Models\YoutubeVideo;
use Cable8mm\Youtube\Support\UrlParser;
use Cable8mm\Youtube\Sync\ChannelInputResolver;
use Cable8mm\Youtube\Youtube;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class ContributorWorkspaceController extends Controller
{
    public function index(Request $request): Response
    {
        $contributions = Contribution::query()
            ->with('contributable')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get()
            ->map(function (Contribution $contribution): array {
                $record = $contribution->contributable;

                return [
                    'id' => $contribution->id,
                    'status' => $contribution->status,
                    'type' => class_basename((string) $contribution->contributable_type),
                    'record_id' => $contribution->contributable_id,
                    'record_title' => match (true) {
                        $record instanceof YoutubeChannel => $record->title,
                        $record instanceof Video => $record->title,
                        $record instanceof Actor => $record->name,
                        default => '—',
                    },
                    'payload' => $contribution->payload,
                    'created_at' => $contribution->created_at?->toIso8601String(),
                    'review_note' => $contribution->review_note,
                ];
            })
            ->all();

        return Inertia::render('contributor/index', [
            'status' => $request->session()->get('status'),
            'contributions' => $contributions,
        ]);
    }

    public function storeChannel(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'channel' => ['required', 'string', 'max:2048'],
        ]);

        try {
            $resolved = app(ChannelInputResolver::class)->resolve($data['channel']);
        } catch (YoutubeApiException) {
            return back()->withErrors(['channel' => 'YouTube에서 채널을 확인하지 못했습니다.'])->withInput();
        }

        if (! is_object($resolved)) {
            return back()->withErrors(['channel' => '채널을 찾을 수 없습니다.'])->withInput();
        }

        $channel = YoutubeChannel::firstOrNew(['channel_id' => $resolved->id]);

        if (! $channel->exists) {
            $channel->fill([
                'handle' => $resolved->snippet->customUrl ?? null,
                'title' => $resolved->snippet->title ?? $resolved->id,
                'uploads_playlist_id' => $resolved->contentDetails->relatedPlaylists->uploads ?? null,
                'is_active' => true,
            ]);
            $channel->user_id = CatalogOwner::id();
            $channel->is_public = false;
            $channel->save();
        }

        return $this->submitContribution(
            $channel,
            [
                'title' => $resolved->snippet->title ?? $resolved->id,
                'handle' => $resolved->snippet->customUrl ?? null,
                'uploads_playlist_id' => $resolved->contentDetails->relatedPlaylists->uploads ?? null,
                'is_active' => true,
            ],
            '채널 기여를 제출했습니다. 운영자 승인 후 공개됩니다.',
        );
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

            $channel = YoutubeChannel::firstOrNew(['channel_id' => $channelItem->id]);

            if (! $channel->exists) {
                $channel->fill([
                    'handle' => $channelItem->snippet->customUrl ?? null,
                    'title' => $channelItem->snippet->title ?? $channelItem->id,
                    'uploads_playlist_id' => $channelItem->contentDetails->relatedPlaylists->uploads ?? null,
                    'is_active' => true,
                ]);
                $channel->user_id = CatalogOwner::id();
                $channel->is_public = false;
                $channel->save();
            }

            $video = Video::firstOrNew(['video_id' => $item->id]);

            if (! $video->exists) {
                $video->fill([
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
                ]);
                $video->user_id = CatalogOwner::id();
                $video->is_public = false;
                $video->save();
            }
        } catch (Throwable) {
            return back()->withErrors(['url' => 'YouTube 영상을 확인하지 못했습니다.'])->withInput();
        }

        return $this->submitContribution(
            $video,
            [
                'title' => $item->snippet->title ?? $item->id,
                'description' => $item->snippet->description ?? null,
                'published_at' => Carbon::parse($item->snippet->publishedAt)->toDateTimeString(),
                'duration' => $item->contentDetails->duration ?? null,
                'thumbnail_url' => $item->snippet->thumbnails->high->url ?? $item->snippet->thumbnails->default->url ?? null,
                'view_count' => $item->statistics->viewCount ?? null,
                'like_count' => $item->statistics->likeCount ?? null,
                'comment_count' => $item->statistics->commentCount ?? null,
                'unavailable_at' => null,
            ],
            '영상 기여를 제출했습니다. 운영자 승인 후 공개됩니다.',
        );
    }

    public function editChannel(YoutubeChannel $channel): Response
    {
        return Inertia::render('contributor/channel', [
            'status' => session('status'),
            'channel' => [
                'id' => $channel->id,
                'title' => $channel->title,
                'handle' => $channel->handle,
                'uploads_playlist_id' => $channel->uploads_playlist_id,
                'is_active' => (bool) $channel->is_active,
            ],
        ]);
    }

    public function updateChannel(Request $request, YoutubeChannel $channel): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'handle' => ['nullable', 'string', 'max:255'],
            'uploads_playlist_id' => ['nullable', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
        ]);

        return $this->submitContribution($channel, $data, '채널 변경 기여를 제출했습니다.');
    }

    public function editVideo(Video $video): Response
    {
        return Inertia::render('contributor/video', [
            'status' => session('status'),
            'video' => [
                'id' => $video->id,
                'title' => $video->title,
                'description' => $video->description,
                'published_at' => $video->published_at?->format('Y-m-d\\TH:i'),
                'duration' => $video->duration,
                'thumbnail_url' => $video->thumbnail_url,
                'view_count' => $video->view_count,
                'like_count' => $video->like_count,
                'comment_count' => $video->comment_count,
                'unavailable_at' => $video->unavailable_at?->format('Y-m-d\\TH:i'),
                'channel' => $video->channel?->title,
            ],
        ]);
    }

    public function updateVideo(Request $request, Video $video): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'published_at' => ['required', 'date'],
            'duration' => ['nullable', 'string', 'max:255'],
            'thumbnail_url' => ['nullable', 'url', 'max:2048'],
            'view_count' => ['nullable', 'integer', 'min:0'],
            'like_count' => ['nullable', 'integer', 'min:0'],
            'comment_count' => ['nullable', 'integer', 'min:0'],
            'unavailable_at' => ['nullable', 'date'],
        ]);

        return $this->submitContribution($video, $data, '영상 변경 기여를 제출했습니다.');
    }

    public function storeActor(Request $request): RedirectResponse
    {
        $data = $this->validateActor($request);

        $actor = Actor::create([
            ...$this->actorValues($data),
            'user_id' => CatalogOwner::id(),
            'is_public' => false,
        ]);

        return $this->submitContribution(
            $actor,
            [
                ...$this->actorValues($data),
                'reference_urls' => $data['reference_urls'] ?? [],
            ],
            '배우 기여를 제출했습니다. 운영자 승인 후 공개됩니다.',
        );
    }

    public function editActor(Actor $actor): Response
    {
        $actor->load(['referenceUrls', 'videos.channel']);

        return Inertia::render('contributor/actor', [
            'status' => session('status'),
            'actor' => [
                'id' => $actor->id,
                'name' => $actor->name,
                'activity_name' => $actor->activity_name,
                'birth_date' => $actor->birth_date?->toDateString(),
                'birth_place' => $actor->birth_place,
                'education' => $actor->education,
                'debut' => $actor->debut,
                'agency' => $actor->agency,
                'nationality' => $actor->nationality,
                'profile' => $actor->profile ?? [],
                'reference_urls' => $actor->referenceUrls->pluck('url')->all(),
                'videos' => $actor->videos
                    ->map(fn (YoutubeVideo $video): array => [
                        'id' => $video->id,
                        'title' => $video->title,
                        'channel' => $video->channel?->title,
                    ])
                    ->all(),
            ],
            'videos' => Video::query()
                ->with('channel')
                ->orderBy('title')
                ->get()
                ->map(fn (Video $video): array => [
                    'id' => $video->id,
                    'title' => $video->title,
                    'channel' => $video->channel?->title,
                ])
                ->all(),
        ]);
    }

    public function updateActor(Request $request, Actor $actor): RedirectResponse
    {
        $data = $this->validateActor($request);

        return $this->submitContribution(
            $actor,
            [
                ...$this->actorValues($data),
                'reference_urls' => $data['reference_urls'] ?? [],
            ],
            '배우 변경 기여를 제출했습니다.',
        );
    }

    public function attachVideo(Request $request, Actor $actor): RedirectResponse
    {
        $data = $request->validate([
            'video_id' => ['required', 'integer', 'exists:youtube_videos,id'],
        ]);

        return $this->submitContribution(
            $actor,
            ['association' => 'attach', 'video_id' => (int) $data['video_id']],
            '배우-영상 연결 기여를 제출했습니다.',
        );
    }

    public function detachVideo(Actor $actor, Video $video): RedirectResponse
    {
        return $this->submitContribution(
            $actor,
            ['association' => 'detach', 'video_id' => $video->id],
            '배우-영상 연결 해제 기여를 제출했습니다.',
        );
    }

    public function destroyContribution(Request $request, Contribution $contribution): RedirectResponse
    {
        abort_unless($contribution->user_id === $request->user()->id, 403);
        abort_unless($contribution->status === Contribution::STATUS_PENDING, 403);

        $contribution->delete();

        return back()->with('status', '기여를 삭제했습니다.');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function submitContribution(Model $record, array $payload, string $success): RedirectResponse
    {
        try {
            app(ContributionManager::class)->submit(request()->user(), $record, $payload);
        } catch (RuntimeException $e) {
            return back()->withErrors(['conflict' => $e->getMessage()])->withInput();
        }

        return back()->with('status', $success);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateActor(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'activity_name' => ['nullable', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date'],
            'birth_place' => ['nullable', 'string', 'max:255'],
            'education' => ['nullable', 'string'],
            'debut' => ['nullable', 'string', 'max:255'],
            'agency' => ['nullable', 'string', 'max:255'],
            'nationality' => ['nullable', 'string', 'max:255'],
            'profile' => ['nullable', 'array'],
            'profile.*' => ['nullable', 'string'],
            'reference_urls' => ['nullable', 'array'],
            'reference_urls.*' => ['nullable', 'url', 'max:2048'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function actorValues(array $data): array
    {
        $profile = $data['profile'] ?? [];

        return [
            ...Arr::except($data, ['profile', 'reference_urls']),
            'profile' => $profile === [] ? null : $profile,
        ];
    }
}
