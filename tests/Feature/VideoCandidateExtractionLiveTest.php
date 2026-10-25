<?php

namespace Tests\Feature;

use App\Jobs\ExtractVideoCandidates;
use App\Models\ActorCandidate;
use App\Models\FilmographyCandidate;
use Cable8mm\Youtube\Models\YoutubeChannel;
use Cable8mm\Youtube\Models\YoutubeVideo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('live')]
class VideoCandidateExtractionLiveTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! env('RUN_LIVE_TESTS', false)) {
            $this->markTestSkipped('Set RUN_LIVE_TESTS=true to run live boundary tests.');
        }

        $key = (string) config('services.gemini.key');

        if ($key === '') {
            $this->markTestSkipped('GEMINI_API_KEY is not configured.');
        }
    }

    public function test_it_extracts_live_candidates_and_records_a_live_failure(): void
    {
        $channel = YoutubeChannel::withoutEvents(fn (): YoutubeChannel => YoutubeChannel::create([
            'channel_id' => 'UCbbbbbbbbbbbbbbbbbbbbbb',
            'title' => '채널 352',
            'uploads_playlist_id' => 'UUbbbbbbbbbbbbbbbbbbbbbb',
        ]));

        $video = $this->video($channel, 'G6A-ONXm9D8', $this->description());

        (new ExtractVideoCandidates($video->id))->handle();

        $video->refresh();

        $this->assertSame(ExtractVideoCandidates::STATUS_COMPLETED, $video->extraction_status);
        $this->assertNull($video->extraction_error);
        $this->assertNotNull($video->extracted_at);
        $this->assertGreaterThanOrEqual(1, ActorCandidate::count());
        $this->assertGreaterThanOrEqual(1, FilmographyCandidate::count());
        $this->assertSame(
            FilmographyCandidate::count(),
            FilmographyCandidate::where('review_status', FilmographyCandidate::REVIEW_STATUS_PENDING)->count(),
        );
        $this->assertSame(
            FilmographyCandidate::count(),
            FilmographyCandidate::where('youtube_video_id', $video->id)->count(),
        );

        $candidate = FilmographyCandidate::firstOrFail();

        $this->assertNotNull($candidate->model_identifier);
        $this->assertSame('video-candidate-extraction/v1', $candidate->prompt_identifier);
        $this->assertSame('gemini', $candidate->collection_method);
        $this->assertNotNull($candidate->actor_candidate_id);
        $this->assertSame('https://www.youtube.com/watch?v=G6A-ONXm9D8', $candidate->candidate_source_url);

        $actorCount = ActorCandidate::count();
        $filmographyCount = FilmographyCandidate::count();
        $actorIds = ActorCandidate::pluck('id')->all();
        $filmographyIds = FilmographyCandidate::pluck('id')->all();

        $failing = $this->video($channel, 'failing-live-video', $this->description());

        config(['services.gemini.key' => 'invalid-key-for-filmo-live-probe']);

        (new ExtractVideoCandidates($failing->id))->handle();

        $failing->refresh();

        $this->assertSame(ExtractVideoCandidates::STATUS_FAILED, $failing->extraction_status);
        $this->assertStringContainsString('API key not valid', (string) $failing->extraction_error);
        $this->assertSame($actorCount, ActorCandidate::count());
        $this->assertSame($filmographyCount, FilmographyCandidate::count());
        $this->assertSame($actorIds, ActorCandidate::pluck('id')->all());
        $this->assertSame($filmographyIds, FilmographyCandidate::pluck('id')->all());
        $this->assertSame(0, FilmographyCandidate::where('youtube_video_id', $failing->id)->count());
    }

    private function video(YoutubeChannel $channel, string $videoId, string $description): YoutubeVideo
    {
        return YoutubeVideo::create([
            'youtube_channel_id' => $channel->id,
            'video_id' => $videoId,
            'title' => '세상 쿨한 예쁜 누나와의 연애',
            'description' => $description,
            'published_at' => Carbon::parse('2026-10-24T10:30:29Z'),
            'duration' => 'PT9M43S',
            'thumbnail_url' => 'https://example.com/'.$videoId.'.jpg',
            'view_count' => 1000,
        ]);
    }

    private function description(): string
    {
        return <<<'DESCRIPTION'
        연상 테토녀와 연하 에겐남의 연애

        ⭐️인스타⭐️
        연상 테토녀 수빈 역 라현서 https://www.instagram.com/rara__0228/
        연하 에겐남 연우 역 홍연우 https://www.instagram.com/yeon.woo._/
        후배 은빈 역 고은빈 https://www.instagram.com/ebyibi/
        그리고 채널 352 https://www.instagram.com/channel_352/
        DESCRIPTION;
    }
}
