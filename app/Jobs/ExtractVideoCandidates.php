<?php

namespace App\Jobs;

use App\Models\ActorCandidate;
use App\Models\FilmographyCandidate;
use App\Services\Gemini\CandidateExtractor;
use Cable8mm\Youtube\Models\YoutubeVideo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * One explicit Gemini Candidate Extraction run for one collected Video.
 */
class ExtractVideoCandidates implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const COLLECTION_METHOD = 'gemini';

    public int $tries = 1;

    public function __construct(public int $youtubeVideoId) {}

    public function handle(): void
    {
        $video = YoutubeVideo::with('channel')->find($this->youtubeVideoId);

        if ($video === null) {
            return;
        }

        $video->extraction_status = self::STATUS_IN_PROGRESS;
        $video->extraction_error = null;
        $video->extracted_at = null;
        $video->save();

        try {
            $result = app(CandidateExtractor::class)->extract($this->videoInfo($video));

            DB::transaction(fn () => $this->storeCandidates($video, $result));

            $video->extraction_status = self::STATUS_COMPLETED;
            $video->extraction_error = null;
            $video->extracted_at = Carbon::now();
        } catch (Throwable $e) {
            $video->extraction_status = self::STATUS_FAILED;
            $video->extraction_error = $e->getMessage();
        }

        $video->save();
    }

    /**
     * @param  array<string, mixed>  $result
     */
    protected function storeCandidates(YoutubeVideo $video, array $result): void
    {
        $actors = [];

        foreach ($result['actor_candidates'] as $values) {
            $actors[] = ActorCandidate::create([
                'activity_name' => $values['activity_name'],
                'name' => $values['name'],
                'related_link' => $values['related_link'],
            ]);
        }

        foreach ($result['filmography_candidates'] as $values) {
            $actor = $this->matchingActor($actors, $values);

            if ($actor === null) {
                $actor = ActorCandidate::create([
                    'activity_name' => $values['activity_name'],
                    'name' => $values['name'],
                    'related_link' => $values['related_link'],
                ]);

                $actors[] = $actor;
            }

            FilmographyCandidate::create([
                'actor_candidate_id' => $actor->id,
                'youtube_video_id' => $video->id,
                'review_status' => FilmographyCandidate::REVIEW_STATUS_PENDING,
                'activity_name' => $values['activity_name'],
                'name' => $values['name'],
                'related_link' => $values['related_link'],
                'video_title' => $values['video_title'] ?? $video->title,
                'channel_name' => $values['channel_name'] ?? $video->channel?->title,
                'published_date' => $values['published_date'] ?? $video->published_at,
                'youtube_video_url' => $values['youtube_video_url'] ?? $this->videoUrl($video),
                'timecode' => $values['timecode'],
                'collection_method' => self::COLLECTION_METHOD,
                'confidence' => $values['confidence'],
                'model_identifier' => $result['model_identifier'],
                'prompt_identifier' => $result['prompt_identifier'],
                'candidate_source_url' => $this->videoUrl($video),
            ]);
        }
    }

    /**
     * @param  array<int, ActorCandidate>  $actors
     * @param  array<string, mixed>  $values
     */
    protected function matchingActor(array $actors, array $values): ?ActorCandidate
    {
        foreach ($actors as $actor) {
            if ($actor->activity_name === $values['activity_name']
                && $actor->name === $values['name']
                && $actor->related_link === $values['related_link']) {
                return $actor;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    protected function videoInfo(YoutubeVideo $video): array
    {
        return [
            'id' => $video->video_id,
            'title' => $video->title,
            'description' => $video->description ?? '',
            'channel_title' => $video->channel?->title,
            'published_at' => $video->published_at?->toIso8601String(),
            'duration' => $video->duration,
            'youtube_url' => $this->videoUrl($video),
        ];
    }

    protected function videoUrl(YoutubeVideo $video): string
    {
        return 'https://www.youtube.com/watch?v='.$video->video_id;
    }
}
