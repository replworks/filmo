<?php

namespace App\Http\Controllers;

use App\Models\Actor;
use App\Models\ChannelRequest;
use App\Models\Contribution;
use App\Models\Video;
use App\Services\Contribution\ContributionManager;
use App\Support\CatalogOwner;
use Cable8mm\Youtube\Models\YoutubeChannel;
use Cable8mm\Youtube\Models\YoutubeVideo;
use Cable8mm\Youtube\Support\UrlParser;
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
                    'record_title' => $record instanceof Actor ? $record->name : '—',
                    'payload' => $contribution->payload,
                    'created_at' => $contribution->created_at?->toIso8601String(),
                    'review_note' => $contribution->review_note,
                ];
            })
            ->all();

        $channelRequests = ChannelRequest::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get()
            ->map(fn (ChannelRequest $channelRequest): array => [
                'id' => $channelRequest->id,
                'input' => $channelRequest->input,
                'status' => $channelRequest->status,
                'created_at' => $channelRequest->created_at?->toIso8601String(),
                'review_note' => $channelRequest->review_note,
            ])
            ->all();

        return Inertia::render('contributor/index', [
            'status' => $request->session()->get('status'),
            'contributions' => $contributions,
            'channelRequests' => $channelRequests,
        ]);
    }

    public function storeChannelRequest(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'channel' => ['required', 'string', 'max:2048'],
        ]);

        ChannelRequest::create([
            'user_id' => $request->user()->id,
            'input' => $data['channel'],
            'status' => ChannelRequest::STATUS_PENDING,
        ]);

        return back()->with('status', '채널 추가 요청을 제출했습니다. 운영자 승인 후 채널과 영상이 추가됩니다.');
    }

    public function storeActor(Request $request): RedirectResponse
    {
        $data = $this->validateActor($request);
        $videos = $this->resolveVideoPayload($request, $data);

        if ($videos instanceof RedirectResponse) {
            return $videos;
        }

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
                'videos' => $videos,
            ],
            '배우 필모 기여를 제출했습니다. 운영자 승인 후 공개됩니다.',
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
                    'channel' => $video->channel?->title ?? $video->channel_title,
                ])
                ->all(),
        ]);
    }

    public function updateActor(Request $request, Actor $actor): RedirectResponse
    {
        $data = $this->validateActor($request);
        $videos = $this->resolveVideoPayload($request, $data);

        if ($videos instanceof RedirectResponse) {
            return $videos;
        }

        return $this->submitContribution(
            $actor,
            [
                ...$this->actorValues($data),
                'reference_urls' => $data['reference_urls'] ?? [],
                'videos' => $videos,
            ],
            '배우 필모 변경 기여를 제출했습니다.',
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
     * @param  array<string, mixed>  $data
     * @return array<int, int>|RedirectResponse
     */
    private function resolveVideoPayload(Request $request, array $data): array|RedirectResponse
    {
        $existing = collect($data['video_ids'] ?? [])->map(fn ($id): int => (int) $id);
        $newVideos = [];

        foreach (collect($data['new_video_urls'] ?? [])->filter() as $url) {
            try {
                $videoId = UrlParser::videoId($url);
                $items = app(Youtube::class)->getVideoInfo($videoId, ['id', 'snippet', 'contentDetails', 'statistics']);
                $item = is_array($items) ? ($items[0] ?? null) : $items;

                if (! is_object($item)) {
                    return back()->withErrors(['new_video_urls' => '영상을 찾을 수 없습니다.'])->withInput();
                }

                $channel = YoutubeChannel::query()
                    ->where('channel_id', $item->snippet->channelId)
                    ->first();

                $video = Video::firstOrNew(['video_id' => $item->id]);

                if (! $video->exists) {
                    $video->fill([
                        'youtube_channel_id' => $channel?->id,
                        'channelid' => $item->snippet->channelId ?? null,
                        'channel_title' => $item->snippet->channelTitle ?? null,
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

                $newVideos[] = $video->id;
            } catch (InvalidArgumentException) {
                return back()->withErrors(['new_video_urls' => 'YouTube 영상 URL을 확인해 주세요.'])->withInput();
            } catch (Throwable) {
                return back()->withErrors(['new_video_urls' => 'YouTube 영상을 확인하지 못했습니다.'])->withInput();
            }
        }

        return $existing->merge($newVideos)->unique()->values()->all();
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
            'video_ids' => ['nullable', 'array'],
            'video_ids.*' => ['integer', 'exists:youtube_videos,id'],
            'new_video_urls' => ['nullable', 'array'],
            'new_video_urls.*' => ['nullable', 'string', 'max:2048'],
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
            ...Arr::except($data, ['profile', 'reference_urls', 'video_ids', 'new_video_urls']),
            'profile' => $profile === [] ? null : $profile,
        ];
    }
}
