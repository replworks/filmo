# T-010 Naver manual source live observation

Observed on 2026-10-26 with a live Naver page and the gated live test group.

## Environment

| Item | Value |
| --- | --- |
| PHP | 8.4.27 |
| Laravel | 13.38.0 |
| Run | 2026-10-26 03:57 KST |
| Command | `RUN_LIVE_TESTS=true php artisan test --compact --group=live` |
| Result | 5 passed (64 assertions), including this manual-source test |

The live test is `Tests\Feature\NaverPersonInformationLiveTest`. It is skipped
unless `RUN_LIVE_TESTS=true`. CI runs it as a skip; it passed against the live
site when run with the flag.

## Supplied URL

The Operator supplied:

<https://search.naver.com/search.naver?query=%EC%86%A1%ED%98%9C%EA%B5%90>

The live page opens with HTTP 200. Its title is `송혜교 : 네이버 검색`, and the
page names the actor, so it is usable as a manual research source. The product
does not retrieve or parse this page; the live test performs one explicit GET to
confirm that the supplied URL opens.

## Saved manual values

| Field | Value |
| --- | --- |
| Activity Name | Manual Activity |
| Name | Manual Name |
| Related Link | <https://www.instagram.com/kyo1122> |
| Naver Person Information URL | the supplied URL above |

The Candidate Source row is stored with kind `naver` and the supplied URL. The
values and URL are read back when the Actor Candidate page is reopened.

## Manual-only behavior

- The save action sends no HTTP request. `Tests\Feature\NaverPersonInformationTest`
  fakes the HTTP client and asserts that nothing was sent, so no Naver search
  result or person information is collected automatically.
- The Operator opens the URL with the **Open Naver Source** action (a new tab)
  and enters the values manually.
- The URL is retained on the candidate as a Candidate Source.
- A non-Naver URL is rejected with a validation error and saves nothing.
- The Naver URL is required for this manual source save.
- All Activity Name, Name, and Related Link values may be empty and the manual
  save still succeeds; the Candidate Source URL is retained.

## Automated coverage

`Tests\Feature\NaverPersonInformationTest` covers the successful save, empty
candidate fields, a non-Naver URL rejection, a missing URL, reopening the page
with the saved values and URL, and authentication.
