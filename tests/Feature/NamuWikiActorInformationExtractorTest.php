<?php

namespace Tests\Feature;

use App\Services\NamuWiki\ActorInformationExtractor;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class NamuWikiActorInformationExtractorTest extends TestCase
{
    public function test_it_extracts_suggestions_from_the_recorded_actor_page(): void
    {
        Http::fake(['*' => Http::response($this->fixture('profile-page.html'), 200)]);

        $result = app(ActorInformationExtractor::class)->extract('https://namu.wiki/w/송혜교');

        $this->assertSame('송혜교', $result['title']);
        $this->assertTrue($result['profile_found']);
        $this->assertSame('송혜교', $result['suggestions']['name']);
        $this->assertNull($result['suggestions']['activity_name']);
        $this->assertSame('https://www.instagram.com/kyo1122', $result['suggestions']['related_link']);
        $this->assertSame('https://namu.wiki/w/송혜교', $result['suggestions']['source_url']);
        $this->assertNotEmpty($result['profile_rows']);

        Http::assertSent(fn (Request $request): bool => urldecode($request->url()) === 'https://namu.wiki/w/송혜교'
            && $request->hasHeader('User-Agent', ActorInformationExtractor::USER_AGENT));
    }

    public function test_it_rejects_a_url_outside_namuwiki_without_a_request(): void
    {
        Http::fake();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Enter an https://namu.wiki/w/... URL.');

        try {
            app(ActorInformationExtractor::class)->extract('https://example.com/w/송혜교');
        } finally {
            Http::assertNothingSent();
        }
    }

    public function test_it_reports_an_http_failure(): void
    {
        Http::fake(['*' => Http::response($this->fixture('missing-page.html'), 404)]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('NamuWiki returned HTTP 404.');

        app(ActorInformationExtractor::class)->extract('https://namu.wiki/w/FilmoProbeMissingPage-2026-10-26');
    }

    public function test_it_reports_a_page_without_an_actor_profile(): void
    {
        Http::fake(['*' => Http::response($this->fixture('page-without-profile.html'), 200)]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The NamuWiki page has no actor profile table.');

        app(ActorInformationExtractor::class)->extract('https://namu.wiki/w/나무위키');
    }

    private function fixture(string $name): string
    {
        return file_get_contents(__DIR__.'/../Fixtures/namuwiki/'.$name);
    }
}
