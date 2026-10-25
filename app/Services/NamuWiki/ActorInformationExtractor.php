<?php

namespace App\Services\NamuWiki;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class ActorInformationExtractor
{
    public const USER_AGENT = 'FilmoBot/1.0 (+https://github.com/replworks/filmo)';

    public const TIMEOUT_SECONDS = 30;

    public const PROFILE_LABELS = ['출생', '데뷔', '소속사', '본명', '활동명'];

    public const RELATED_LINK_LABELS = ['링크', 'SNS', '인스타그램'];

    /**
     * Retrieve one NamuWiki page and return Actor Candidate suggestions.
     *
     * @return array<string, mixed>
     *
     * @throws RuntimeException
     */
    public function extract(string $url): array
    {
        $url = trim($url);

        $this->validateUrl($url);

        try {
            $response = Http::withHeaders([
                'User-Agent' => self::USER_AGENT,
                'Accept' => 'text/html,application/xhtml+xml',
            ])
                ->withoutRedirecting()
                ->timeout(self::TIMEOUT_SECONDS)
                ->get($url);
        } catch (Throwable $e) {
            throw new RuntimeException('NamuWiki could not be reached: '.$e->getMessage());
        }

        if (! $response->successful()) {
            throw new RuntimeException('NamuWiki returned HTTP '.$response->status().'.');
        }

        $page = $this->parse($response->body(), $url);

        if (! $page['profile_found'] || $page['suggestions']['name'] === null) {
            throw new RuntimeException('The NamuWiki page has no actor profile table.');
        }

        return $page;
    }

    /**
     * @throws RuntimeException
     */
    protected function validateUrl(string $url): void
    {
        if ($url === '') {
            throw new RuntimeException('Enter a NamuWiki URL.');
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);
        $host = parse_url($url, PHP_URL_HOST);
        $path = parse_url($url, PHP_URL_PATH);

        if ($scheme !== 'https'
            || $host !== 'namu.wiki'
            || ! is_string($path)
            || ! str_starts_with($path, '/w/')) {
            throw new RuntimeException('Enter an https://namu.wiki/w/... URL.');
        }
    }

    /**
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
            return $this->page(null, [], null, $url);
        }

        $xpath = new DOMXPath($document);
        $title = $this->normalize((string) $xpath->evaluate('string(//title)'));
        $title = $this->normalize((string) preg_replace('/\s*-\s*나무위키\s*$/u', '', $title));
        $description = $this->normalize((string) $xpath->evaluate('string(//meta[@property="og:description"]/@content)'));

        $table = $this->profileTable($xpath);

        if ($table === null) {
            return $this->page($title === '' ? null : $title, [], $description, $url);
        }

        return $this->page($title === '' ? null : $title, $this->profileRows($xpath, $table), $description, $url);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<string, mixed>
     */
    protected function page(?string $title, array $rows, ?string $description, string $url): array
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
            'suggestions' => [
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
}
