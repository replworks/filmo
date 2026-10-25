<?php

namespace App\Jobs;

use App\Models\Actor;
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
 * One explicit Gemini Actor Extraction run for one collected Video.
 */
class ExtractVideoActors implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

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

            DB::transaction(fn () => $this->storeActors($video, $result['actor_candidates']));

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
     * @param  array<int, array<string, mixed>>  $actors
     */
    protected function storeActors(YoutubeVideo $video, array $actors): void
    {
        foreach ($actors as $values) {
            $name = $this->stringValue($values['name'] ?? null);

            if ($name === null) {
                continue;
            }

            $activityName = $this->stringValue($values['activity_name'] ?? null);
            $relatedLink = $this->stringValue($values['related_link'] ?? null);

            $actor = Actor::query()
                ->active()
                ->where('name', $name)
                ->when(
                    $activityName === null,
                    fn ($query) => $query->whereNull('activity_name'),
                    fn ($query) => $query->where('activity_name', $activityName),
                )
                ->whereHas('videos', fn ($query) => $query->where('youtube_videos.id', $video->id))
                ->first();

            if ($actor === null) {
                $actor = Actor::create([
                    'name' => $name,
                    'activity_name' => $activityName,
                ]);
            }

            if ($relatedLink !== null) {
                $actor->referenceUrls()->firstOrCreate(['url' => $relatedLink]);
            }

            $actor->videos()->syncWithoutDetaching([$video->id]);
        }
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
            'youtube_url' => 'https://www.youtube.com/watch?v='.$video->video_id,
        ];
    }

    protected function stringValue(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
