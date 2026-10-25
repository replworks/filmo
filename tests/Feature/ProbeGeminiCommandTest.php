<?php

namespace Tests\Feature;

use App\Console\Commands\ProbeGeminiCommand;
use Cable8mm\Youtube\Youtube;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Mockery\MockInterface;
use Tests\TestCase;

class ProbeGeminiCommandTest extends TestCase
{
    private string $output;

    protected function setUp(): void
    {
        parent::setUp();

        $this->output = tempnam(sys_get_temp_dir(), 'filmo-gemini-probe-');
        config([
            'services.gemini.key' => 'test-key-0123456789',
            'services.gemini.model' => 'gemini-3.6-flash',
        ]);
        Sleep::fake();
    }

    protected function tearDown(): void
    {
        @unlink($this->output);

        parent::tearDown();
    }

    public function test_it_records_extraction_values_and_distinguishes_the_invalid_key_failure(): void
    {
        $this->mockVideo();

        Http::fake(function (Request $request) {
            if ($request->hasHeader('x-goog-api-key', ProbeGeminiCommand::INVALID_KEY)) {
                return Http::response($this->fixture('api-key-invalid.json'), 400);
            }

            return Http::response($this->fixture('extraction-success.json'), 200);
        });

        $this->artisan('filmo:probe-gemini', [
            '--video' => 'G6A-ONXm9D8',
            '--output' => $this->output,
        ])->assertSuccessful();

        $observation = json_decode(file_get_contents($this->output), true);
        $extraction = $observation['extraction'];

        $this->assertSame('G6A-ONXm9D8', $observation['video']['id']);
        $this->assertSame('세상 쿨한 예쁜 누나와의 연애', $observation['video']['title']);

        $this->assertSame('success', $extraction['outcome']);
        $this->assertSame(200, $extraction['http_status']);
        $this->assertSame('gemini-3.6-flash', $extraction['model_version']);
        $this->assertSame('STOP', $extraction['finish_reason']);
        $this->assertNull($extraction['failure']);

        $this->assertSame([
            ['activity_name' => '수빈', 'name' => '라현서', 'related_link' => 'https://www.instagram.com/rara__0228/'],
            ['activity_name' => '연우', 'name' => '홍연우', 'related_link' => 'https://www.instagram.com/yeon.woo._/'],
            ['activity_name' => '은빈', 'name' => '고은빈', 'related_link' => 'https://www.instagram.com/ebyibi/'],
        ], $extraction['actor_candidates']);

        $this->assertCount(3, $extraction['filmography_candidates']);
        $this->assertSame('G6A-ONXm9D8', $extraction['filmography_candidates'][0]['video_id']);
        $this->assertSame('세상 쿨한 예쁜 누나와의 연애', $extraction['filmography_candidates'][0]['video_title']);
        $this->assertSame('https://www.youtube.com/watch?v=G6A-ONXm9D8', $extraction['filmography_candidates'][0]['youtube_video_url']);
        $this->assertArrayHasKey('timecode', $extraction['filmography_candidates'][0]);
        $this->assertNull($extraction['filmography_candidates'][0]['timecode']);

        $failure = $observation['failure'];

        $this->assertSame('failed', $failure['outcome']);
        $this->assertSame(400, $failure['http_status']);
        $this->assertSame('API_KEY_INVALID', $failure['failure']['reason']);
        $this->assertSame('INVALID_ARGUMENT', $failure['failure']['status']);
        $this->assertSame('API key not valid. Please pass a valid API key.', $failure['failure']['message']);
        $this->assertNull($failure['actor_candidates']);
        $this->assertNull($failure['filmography_candidates']);

        Http::assertSentCount(2);
        Http::assertSent(fn (Request $request): bool => $request->hasHeader('x-goog-api-key', 'test-key-0123456789')
            && str_contains($request->url(), 'models/gemini-3.6-flash:generateContent')
            && str_contains($request['contents'][0]['parts'][0]['text'], '세상 쿨한 예쁜 누나와의 연애'));
        Http::assertSent(fn (Request $request): bool => $request->hasHeader('x-goog-api-key', ProbeGeminiCommand::INVALID_KEY));

        Sleep::assertNeverSlept();
    }

    public function test_it_retries_a_transient_model_failure(): void
    {
        $this->mockVideo();

        Http::fakeSequence()
            ->push($this->fixture('model-unavailable-503.json'), 503)
            ->push($this->fixture('extraction-success.json'), 200)
            ->push($this->fixture('api-key-invalid.json'), 400);

        $this->artisan('filmo:probe-gemini', [
            '--video' => 'G6A-ONXm9D8',
            '--output' => $this->output,
        ])->assertSuccessful();

        $observation = json_decode(file_get_contents($this->output), true);

        $this->assertSame('success', $observation['extraction']['outcome']);
        $this->assertSame('failed', $observation['failure']['outcome']);
        Http::assertSentCount(3);
        Sleep::assertSleptTimes(1);
    }

    public function test_it_refuses_to_run_without_an_api_key(): void
    {
        config(['services.gemini.key' => '']);

        $this->artisan('filmo:probe-gemini', [
            '--video' => 'G6A-ONXm9D8',
            '--output' => $this->output,
        ])->assertFailed();
    }

    public function test_it_refuses_to_run_without_a_video(): void
    {
        $this->artisan('filmo:probe-gemini', [
            '--output' => $this->output,
        ])->assertFailed();
    }

    public function test_it_fails_when_youtube_returns_no_video(): void
    {
        $this->mock(Youtube::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getVideoInfo')
                ->once()
                ->with(['G6A-ONXm9D8'], ProbeGeminiCommand::VIDEO_PART)
                ->andReturn([]);
        });

        Http::fake();

        $this->artisan('filmo:probe-gemini', [
            '--video' => 'G6A-ONXm9D8',
            '--output' => $this->output,
        ])->assertFailed();

        Http::assertNothingSent();
    }

    private function mockVideo(): void
    {
        $video = (object) [
            'id' => 'G6A-ONXm9D8',
            'snippet' => (object) [
                'title' => '세상 쿨한 예쁜 누나와의 연애',
                'description' => '연상 테토녀 수빈 역 라현서 https://www.instagram.com/rara__0228/',
                'channelTitle' => '채널 352',
                'publishedAt' => '2026-10-24T10:30:29Z',
            ],
            'contentDetails' => (object) ['duration' => 'PT9M43S'],
            'status' => (object) ['privacyStatus' => 'public'],
        ];

        $this->mock(Youtube::class, function (MockInterface $mock) use ($video): void {
            $mock->shouldReceive('getVideoInfo')
                ->once()
                ->with(['G6A-ONXm9D8'], ProbeGeminiCommand::VIDEO_PART)
                ->andReturn([$video]);
        });
    }

    private function fixture(string $name): string
    {
        return file_get_contents(__DIR__.'/../Fixtures/gemini/'.$name);
    }
}
