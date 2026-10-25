# T-009 NamuWiki suggestions live observation

Observed on 2026-10-26 with the live NamuWiki site, using the Actor Candidate
view screen and the gated live test group.

## Environment

| Item | Value |
| --- | --- |
| PHP | 8.4.27 |
| Laravel | 13.38.0 |
| Run | 2026-10-26 03:51 KST |
| Command | `RUN_LIVE_TESTS=true php artisan test --compact --filter=NamuWikiSuggestionsLiveTest` |
| Result | 1 passed (14 assertions) |

The live test is `Tests\Feature\NamuWikiSuggestionsLiveTest`. It is skipped
unless `RUN_LIVE_TESTS=true`. CI runs it as a skip; it passed against the live
site when run with the flag.

## Candidate and supplied URL

The Actor Candidate already had these values:

| Field | Value |
| --- | --- |
| Activity Name | Existing Activity |
| Name | Existing Name |
| Related Link | <https://existing.example> |

The Operator supplied <https://namu.wiki/w/송혜교> in the NamuWiki URL input.

## Live suggestions

| Suggested field | Value |
| --- | --- |
| Activity Name | `null` (no `활동명` row) |
| Name | 송혜교 |
| Related Link | <https://www.instagram.com/kyo1122> |
| Candidate Source URL | <https://namu.wiki/w/송혜교> |

The suggestions are shown in the extraction result area. The candidate's
existing Activity Name, Name, and Related Link are unchanged after extraction.
The Candidate Source URL is retained as a `candidate_sources` row with kind
`namuwiki`.

The extraction creates no Filmography Candidate and no Actor-Video
relationship. It has no code path that writes Actor Candidate field values; only
a later Operator save (T-011) can do that.

## Live failure

The same candidate was then given a missing NamuWiki URL
(`https://namu.wiki/w/FilmoProbeMissingPage-2026-10-26`).

| Item | Value |
| --- | --- |
| Outcome | `failed` |
| Shown error | `NamuWiki returned HTTP 404.` |
| URL input | retained |
| Candidate values | retained |
| New Candidate Source | none |

The failure is shown in the extraction result area, and the supplied URL stays
in the form so the Operator can correct or retry it.

## Retrieval behavior

The connector from T-004 was verified with the same live page: NamuWiki returns
server-rendered HTML with a profile table. Production extraction uses
`App\Services\NamuWiki\ActorInformationExtractor`, which:

- accepts only `https://namu.wiki/w/...` URLs,
- does not follow redirects,
- sends the descriptive `FilmoBot` user agent,
- reads the profile table and maps Activity Name from a `활동명` row, Name from
  the page title, and Related Link from the `링크`/`SNS`/`인스타그램` row,
- records absent values as `null`.

The URL restriction keeps the Operator-supplied URL from reaching another host.
The recorded fixtures under `tests/Fixtures/namuwiki/` cover the successful
profile page, a page without an actor profile, and the 404 page.

## Automated coverage

`Tests\Feature\NamuWikiSuggestionsTest` covers the UI flow, no overwrite, the
retained URL and values on failure, the Candidate Source row, the absence of
Filmography/actor-video records, invalid-host rejection, the Actor Candidate
list, and authentication. `Tests\Feature\NamuWikiActorInformationExtractorTest`
covers the extractor's success and failure responses.
