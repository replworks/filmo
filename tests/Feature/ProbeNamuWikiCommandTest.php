<?php

namespace Tests\Feature;

use App\Console\Commands\ProbeNamuWikiCommand;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProbeNamuWikiCommandTest extends TestCase
{
    private string $output;

    protected function setUp(): void
    {
        parent::setUp();

        $this->output = tempnam(sys_get_temp_dir(), 'filmo-namuwiki-probe-');
    }

    protected function tearDown(): void
    {
        @unlink($this->output);

        parent::tearDown();
    }

    public function test_it_records_page_content_suggestions_and_distinguishable_failures(): void
    {
        Http::fakeSequence()
            ->push($this->fixture('profile-page.html'), 200, ['Content-Type' => 'text/html; charset=utf-8'])
            ->push($this->fixture('page-without-profile.html'), 200, ['Content-Type' => 'text/html; charset=utf-8'])
            ->push($this->fixture('missing-page.html'), 404, ['Content-Type' => 'text/html; charset=utf-8']);

        $this->artisan('filmo:probe-namuwiki', [
            '--url' => 'https://namu.wiki/w/송혜교',
            '--output' => $this->output,
        ])->assertSuccessful();

        $observation = json_decode(file_get_contents($this->output), true);
        $page = $observation['page'];

        $this->assertSame('success', $page['outcome']);
        $this->assertSame(200, $page['http_status']);
        $this->assertSame(20105, $page['body_length']);
        $this->assertSame(64, strlen($page['body_sha256']));
        $this->assertFalse($page['redirected']);
        $this->assertNull($page['failure']);

        $this->assertSame('송혜교', $page['page']['title']);
        $this->assertTrue($page['page']['profile_found']);
        $this->assertSame('송혜교', $page['page']['suggestions']['name']);
        $this->assertNull($page['page']['suggestions']['activity_name']);
        $this->assertSame('https://www.instagram.com/kyo1122', $page['page']['suggestions']['related_link']);
        $this->assertSame('https://namu.wiki/w/송혜교', $page['page']['suggestions']['source_url']);

        $labels = array_column($page['page']['profile_rows'], 'label');
        $this->assertContains('출생', $labels);
        $this->assertContains('소속사', $labels);
        $this->assertContains('링크', $labels);

        $linkRow = collect($page['page']['profile_rows'])->firstWhere('label', '링크');
        $this->assertContains('https://www.instagram.com/kyo1122', $linkRow['links']);

        $unprocessable = $observation['unprocessable'];
        $this->assertSame('failed', $unprocessable['outcome']);
        $this->assertSame(200, $unprocessable['http_status']);
        $this->assertSame('unprocessable_page', $unprocessable['failure']['reason']);
        $this->assertFalse($unprocessable['page']['profile_found']);
        $this->assertNull($unprocessable['page']['suggestions']);

        $unavailable = $observation['unavailable'];
        $this->assertSame('failed', $unavailable['outcome']);
        $this->assertSame(404, $unavailable['http_status']);
        $this->assertSame('http_404', $unavailable['failure']['reason']);
        $this->assertNull($unavailable['page']['suggestions']);

        Http::assertSentCount(3);
        Http::assertSent(fn (Request $request): bool => urldecode($request->url()) === 'https://namu.wiki/w/송혜교'
            && $request->hasHeader('User-Agent', ProbeNamuWikiCommand::USER_AGENT));
        Http::assertSent(fn (Request $request): bool => urldecode($request->url()) === ProbeNamuWikiCommand::UNPROCESSABLE_URL);
        Http::assertSent(fn (Request $request): bool => urldecode($request->url()) === ProbeNamuWikiCommand::MISSING_URL);
    }

    public function test_it_refuses_to_run_without_a_url(): void
    {
        Http::fake();

        $this->artisan('filmo:probe-namuwiki', [
            '--output' => $this->output,
        ])->assertFailed();

        Http::assertNothingSent();
    }

    private function fixture(string $name): string
    {
        return file_get_contents(__DIR__.'/../Fixtures/namuwiki/'.$name);
    }
}
