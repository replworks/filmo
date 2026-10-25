<?php

namespace App\Console\Commands;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Throwable;

class ProbeNamuWikiCommand extends Command
{
    public const USER_AGENT = 'FilmoBot/1.0 (+https://github.com/replworks/filmo)';

    public const TIMEOUT_SECONDS = 30;

    /**
     * A page that exists but has no actor profile table.
     */
    public const UNPROCESSABLE_URL = 'https://namu.wiki/w/나무위키';

    /**
     * A page that is expected not to exist.
     */
    public const MISSING_URL = 'https://namu.wiki/w/FilmoProbeMissingPage-2026-10-26';

    /**
     * Profile labels that identify an actor profile table.
     */
    public const PROFILE_LABELS = ['출생', '데뷔', '소속사', '본명', '활동명'];

    /**
     * Profile row labels that carry an external related link.
     */
    public const RELATED_LINK_LABELS = ['링크', 'SNS', '인스타그램'];

    protected $signature = 'filmo:probe-namuwiki
        {--url= : NamuWiki URL to retrieve and extract}
        {--unprocessable-url='.self::UNPROCESSABLE_URL.' : NamuWiki URL without an actor profile}
        {--missing-url='.self::MISSING_URL.' : NamuWiki URL expected to be unavailable}
        {--output=docs/observations/t-004-namuwiki.json : Path of the JSON observation}';

    protected $description = 'Observe live NamuWiki page retrieval and Actor Candidate information extraction';

    public function handle(): int
    {
        $url = trim((string) $this->option('url'));

        if ($url === '') {
            $this->error('Pass a --url.');

            return self::FAILURE;
        }

        $observation = [
            'probe' => 'filmo:probe-namuwiki',
            'ran_at' => Carbon::now()->toIso8601String(),
            'environment' => $this->environment(),
            'page' => $this->retrieve($url),
            'unprocessable' => $this->retrieve(trim((string) $this->option('unprocessable-url'))),
            'unavailable' => $this->retrieve(trim((string) $this->option('missing-url'))),
        ];

        $path = $this->writeObservation($observation);

        $this->summarize($observation, $path);

        return self::SUCCESS;
    }

    /**
     * Retrieve one page and record its content and extraction outcome.
     *
     * @return array<string, mixed>
     */
    protected function retrieve(string $url): array
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => self::USER_AGENT,
                'Accept' => 'text/html,application/xhtml+xml',
            ])
                ->timeout(self::TIMEOUT_SECONDS)
                ->get($url);
        } catch (Throwable $e) {
            return [
                'outcome' => 'failed',
                'requested_url' => $url,
                'effective_url' => null,
                'redirected' => false,
                'redirect_count' => 0,
                'http_status' => null,
                'content_type' => null,
                'body_length' => null,
                'body_sha256' => null,
                'page' => null,
                'failure' => [
                    'reason' => 'transport',
                    'message' => $e->getMessage(),
                ],
            ];
        }

        $html = $response->body();
        $page = $this->parse($html, $url);
        $effective = $response->effectiveUri();
        $redirectCount = (int) ($response->handlerStats()['redirect_count'] ?? 0);

        $result = [
            'outcome' => 'success',
            'requested_url' => $url,
            'effective_url' => $effective === null ? null : (string) $effective,
            'redirected' => $redirectCount > 0,
            'redirect_count' => $redirectCount,
            'http_status' => $response->status(),
            'content_type' => $response->header('Content-Type') ?: null,
            'body_length' => strlen($html),
            'body_sha256' => hash('sha256', $html),
            'page' => $page,
            'failure' => null,
        ];

        if (! $response->successful()) {
            $result['outcome'] = 'failed';
            $result['failure'] = [
                'reason' => 'http_'.$response->status(),
                'message' => 'NamuWiki returned HTTP '.$response->status().'.',
            ];

            return $result;
        }

        if (! $page['profile_found'] || $page['suggestions']['name'] === null) {
            $result['outcome'] = 'failed';
            $result['failure'] = [
                'reason' => 'unprocessable_page',
                'message' => 'The page has no actor profile table to extract candidate information from.',
            ];
        }

        return $result;
    }

    /**
     * Read the page title, actor profile rows, and candidate suggestions.
     *
     * @return array<string, mixed>
     */
    protected function parse(string $html, string $url): array
    {
        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $loaded = $document->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $loaded) {
            return $this->parsedPage(null, [], null);
        }

        $xpath = new DOMXPath($document);
        $title = $this->normalize((string) $xpath->evaluate('string(//title)'));
        $title = $this->normalize((string) preg_replace('/\s*-\s*나무위키\s*$/u', '', $title));
        $description = $this->normalize((string) $xpath->evaluate('string(//meta[@property="og:description"]/@content)'));

        $table = $this->profileTable($xpath);

        if ($table === null) {
            return $this->parsedPage($title === '' ? null : $title, [], $description);
        }

        $rows = $this->profileRows($xpath, $table);

        return $this->parsedPage($title === '' ? null : $title, $rows, $description, $url);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<string, mixed>
     */
    protected function parsedPage(?string $title, array $rows, ?string $description, ?string $url = null): array
    {
        $activityName = null;
        $relatedLink = null;

        foreach ($rows as $row) {
            if ($activityName === null && str_contains($row['label'], '활동명')) {
                $activityName = $row['value'];
            }

            if ($relatedLink === null
                && in_array($row['label'], self::RELATED_LINK_LABELS, true)
                && $row['links'] !== []) {
                $relatedLink = $row['links'][0];
            }
        }

        return [
            'title' => $title,
            'description' => $description === '' ? null : $description,
            'profile_found' => $rows !== [],
            'profile_rows' => $rows,
            'suggestions' => $rows === [] || $url === null ? null : [
                'activity_name' => $activityName,
                'name' => $title,
                'related_link' => $relatedLink,
                'source_url' => $url,
            ],
        ];
    }

    protected function profileTable(DOMXPath $xpath): ?DOMElement
    {
        foreach ($xpath->query('//table') as $table) {
            if (! $table instanceof DOMElement) {
                continue;
            }

            $labels = [];

            foreach ($xpath->query('.//tr', $table) as $row) {
                $cells = $xpath->query('./th|./td', $row);

                if ($cells === false || $cells->length === 0) {
                    continue;
                }

                $labels[] = $this->normalize($cells->item(0)->textContent);
            }

            if (in_array('출생', $labels, true)
                && count(array_intersect(self::PROFILE_LABELS, $labels)) >= 2) {
                return $table;
            }
        }

        return null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function profileRows(DOMXPath $xpath, DOMElement $table): array
    {
        $rows = [];

        foreach ($xpath->query('.//tr', $table) as $row) {
            $cells = $xpath->query('./th|./td', $row);

            if ($cells === false || $cells->length < 2) {
                continue;
            }

            $label = $this->normalize($cells->item(0)->textContent);
            $valueCell = $cells->item(1);

            if ($label === '' || ! $valueCell instanceof DOMElement) {
                continue;
            }

            $value = $this->normalize($valueCell->textContent);
            $links = [];

            foreach ($xpath->query('.//a[@href]', $valueCell) as $link) {
                if (! $link instanceof DOMElement) {
                    continue;
                }

                $href = trim($link->getAttribute('href'));

                if (str_starts_with($href, 'http://') || str_starts_with($href, 'https://')) {
                    $links[$href] = true;
                }
            }

            $rows[] = [
                'label' => $label,
                'value' => $value === '' ? null : $value,
                'links' => array_keys($links),
            ];
        }

        return $rows;
    }

    protected function normalize(string $value): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $value));
    }

    /**
     * @return array<string, mixed>
     */
    protected function environment(): array
    {
        return [
            'php' => PHP_VERSION,
            'laravel' => app()->version(),
            'user_agent' => self::USER_AGENT,
            'timeout_seconds' => self::TIMEOUT_SECONDS,
        ];
    }

    /**
     * @param  array<string, mixed>  $observation
     */
    protected function writeObservation(array $observation): string
    {
        $option = (string) $this->option('output');
        $path = str_starts_with($option, '/') ? $option : base_path($option);

        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode($observation, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL);

        return $path;
    }

    /**
     * @param  array<string, mixed>  $observation
     */
    protected function summarize(array $observation, string $path): void
    {
        foreach (['page', 'unprocessable', 'unavailable'] as $label) {
            $result = $observation[$label];

            if ($result['outcome'] === 'success') {
                $suggestions = $result['page']['suggestions'];

                $this->line(sprintf(
                    '%s: success (HTTP %s, %d bytes), name=%s, activity_name=%s, related_link=%s',
                    $label,
                    $result['http_status'],
                    $result['body_length'],
                    $suggestions['name'] ?? 'none',
                    $suggestions['activity_name'] ?? 'none',
                    $suggestions['related_link'] ?? 'none',
                ));

                continue;
            }

            $this->line(sprintf(
                '%s: failed (HTTP %s) %s',
                $label,
                $result['http_status'] ?? 'no response',
                $result['failure']['reason'] ?? 'unknown',
            ));
        }

        $this->info('Wrote '.$path);
    }
}
