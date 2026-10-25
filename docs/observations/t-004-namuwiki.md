# T-004 NamuWiki boundary observation

Observed on 2026-10-26 with the live NamuWiki site, using `filmo:probe-namuwiki`.

## Environment

| Item | Value |
| --- | --- |
| PHP | 8.4.27 |
| Laravel | 13.38.0 |
| Probe run | 2026-10-25T18:17:55+00:00 (2026-10-26 03:17 KST) |
| User agent | `FilmoBot/1.0 (+https://github.com/replworks/filmo)` |
| Timeout | 30 seconds |
| Supplied page | <https://namu.wiki/w/송혜교> |

Artifacts:

- `t-004-namuwiki.json`: the supplied page, the unprocessable page, and the unavailable page
- `tests/Fixtures/namuwiki/profile-page.html`: the observed profile table and page title
- `tests/Fixtures/namuwiki/page-without-profile.html`: a minimal page carrying the observed non-profile title
- `tests/Fixtures/namuwiki/missing-page.html`: the observed 404 page

## Page retrieval

The probe requests the supplied URL with the Laravel HTTP Client and records the
response before extracting anything.

| Item | Value |
| --- | --- |
| HTTP status | 200 |
| Content type | `text/html; charset=utf-8` |
| Body length | 875,381 bytes |
| Body SHA-256 | `fce132a30d9f0d3e2dc9ad863286f29e01077780b1149555acc35d8209fccc4a` |
| Effective URL | `https://namu.wiki/w/%EC%86%A1%ED%98%9C%EA%B5%90` |
| Redirects | 0 |
| Page title | 송혜교 |
| Description | 대한민국 의 배우 이자 방송인 . 데뷔 전 대구광역시 에서 무남독녀 외딸로 태어났다. … |

NamuWiki returns server-rendered HTML. A browser-like user agent is not required;
the descriptive `FilmoBot` user agent received the page. The effective URL is the
percent-encoded form of the supplied Korean URL, which is not a redirect.

## Page content

The page contains a profile table with 13 label/value rows. The probe records
every row and its external links, so the extraction stays traceable to the page.

| Label | Value (shortened) | Links |
| --- | --- | --- |
| 출생 | 1981년 11월 22일[1] (44세) | |
| 출신 | 서울특별시 강남구 개포동 | |
| 국적 | 대한민국 | |
| 본관 | 여산 송씨 (礪山 宋氏) | |
| 신체 | 161cm｜45kg｜A형 | |
| 가족 | 아버지 송대섭[2], 어머니[3] | |
| 학력 | 서울대도국민학교 (졸업)… | |
| 종교 | 무종교 | |
| 소속사 | STAND[5] (2026. 10. 23. ~ ) | |
| 데뷔 | 1996년 선경 스마트 모델 선발대회 대상… | |
| MBTI | INFJ[8] | |
| 서명 | `null` | |
| 링크 | | <https://www.instagram.com/kyo1122>, <https://www.threads.com/kyo1122>, <https://m.cafe.daum.net/hyekyoandhyebaragi/_rec> |

A profile table is identified by a `출생` row plus at least one other profile
label (`데뷔`, `소속사`, `본명`, or `활동명`). This is a probe heuristic derived
from the observed page structure, not a site API contract.

## Extracted suggestions

| Actor Candidate field | Suggested value | Source |
| --- | --- | --- |
| Activity Name | `null` | No `활동명` row on this page |
| Name | 송혜교 | Page title |
| Related Link | <https://www.instagram.com/kyo1122> | First external link in the `링크` row |
| Candidate Source URL | <https://namu.wiki/w/송혜교> | Supplied URL |

The values can be presented as suggestions for an Actor Candidate: Name and
Related Link are present, and Activity Name is an explicit `null` rather than a
dropped field. A page without an `활동명` row still produces a usable suggestion.

The probe only returns these values. It has no persistence path: it does not
create or update a Filmography Candidate or an Actor-Video relationship, and it
does not overwrite existing Actor Candidate values. Those constraints apply when
the Operator later selects and saves suggested values.

## Unprocessable page

The probe also retrieves an existing page that is not an actor profile.

| Item | Value |
| --- | --- |
| URL | <https://namu.wiki/w/나무위키> |
| HTTP status | 200 |
| Body length | 249,905 bytes |
| Page title | 나무위키 |
| Profile found | no |
| Outcome | `failed` |
| Failure reason | `unprocessable_page` |
| Suggestions | `null` |

A successful HTTP response with no profile table is a processing failure, not a
successful extraction with empty suggestions.

## Unavailable page

| Item | Value |
| --- | --- |
| URL | <https://namu.wiki/w/FilmoProbeMissingPage-2026-10-26> |
| HTTP status | 404 |
| Body length | 32,980 bytes |
| Page title | FilmoProbeMissingPage-2026-10-26 |
| Profile found | no |
| Outcome | `failed` |
| Failure reason | `http_404` |
| Suggestions | `null` |

NamuWiki returns an HTML page for a missing document rather than only a bare
error, so the probe records the status, page title, and body length as retrieval
behavior. A non-2xx response is a retrieval failure regardless of the HTML body.

## Distinguishability

| Case | `outcome` | `failure.reason` | Suggestions |
| --- | --- | --- | --- |
| Supplied actor page | `success` | `null` | present |
| Existing non-profile page | `failed` | `unprocessable_page` | `null` |
| Missing page | `failed` | `http_404` | `null` |

A failure records no suggestions. The three cases are distinguishable without
treating an unavailable or unprocessable page as a successful extraction.
