# T-002 YouTube boundary observation

Observed on 2026-10-26 with the live YouTube Data API, using `filmo:probe-youtube`.

## Environment

| Item | Value |
| --- | --- |
| PHP | 8.4.27 |
| Laravel | 13.38.0 |
| `cable8mm/youtube` | v6.0.0 |
| Probe run | 2026-10-25T17:43:21+00:00 (2026-10-26 02:43 KST) |
| API key | `AIza…(39 chars)` |

Artifacts:

- `t-002-youtube.json`: successful resolution and collection responses
- `t-002-youtube-not-found.json`: unsupported and unknown references
- `t-002-youtube-invalid-key.json`: invalid API key response
- `t-002-youtube-transport.json`: transport failure response

The probe uses `Cable8mm\Youtube\Sync\ChannelInputResolver`, so a reference is resolved with at most one `channels.list` request and never with `search.list`. Channel lookups request `part=id,snippet,contentDetails`.

## Channel resolution

All five supported reference forms resolved to the same Channel.

| Input | Lookup | Outcome | Channel ID | Title | `customUrl` | Uploads playlist |
| --- | --- | --- | --- | --- | --- | --- |
| `UCBR8-60-B28hp2BmDPdntcQ` | `getChannelById` | resolved | `UCBR8-60-B28hp2BmDPdntcQ` | YouTube | `@youtube` | `UUBR8-60-B28hp2BmDPdntcQ` |
| `@YouTube` | `getChannelByHandle` | resolved | `UCBR8-60-B28hp2BmDPdntcQ` | YouTube | `@youtube` | `UUBR8-60-B28hp2BmDPdntcQ` |
| `https://www.youtube.com/@YouTube` | `getChannelByHandle` | resolved | `UCBR8-60-B28hp2BmDPdntcQ` | YouTube | `@youtube` | `UUBR8-60-B28hp2BmDPdntcQ` |
| `https://www.youtube.com/channel/UCBR8-60-B28hp2BmDPdntcQ` | `getChannelById` | resolved | `UCBR8-60-B28hp2BmDPdntcQ` | YouTube | `@youtube` | `UUBR8-60-B28hp2BmDPdntcQ` |
| `https://www.youtube.com/user/YouTube` | `getChannelByName` | resolved | `UCBR8-60-B28hp2BmDPdntcQ` | YouTube | `@youtube` | `UUBR8-60-B28hp2BmDPdntcQ` |

The returned `customUrl` is lowercase (`@youtube`) even though the input handle was `@YouTube`. With `part=id,snippet,contentDetails`, `statistics` is absent, so subscriber and Video counts are `null` in the observation.

Unsupported and unknown references:

| Input | Outcome | Detail |
| --- | --- | --- |
| `not a channel` | unsupported | no request is sent; `ChannelInputResolver::lookup()` returns `null` |
| `UCaaaaaaaaaaaaaaaaaaaaaa` | channel not found | `channels.list` returned 200 with no items; the resolver returned `channel not found` |
| `https://www.youtube.com/user/thisusernamedoesnotexist12345` | channel not found | `forUsername` returned 200 with no items |

## Uploads pages

The probe read two pages of the uploads playlist with `playlistItems.list`, `maxResults=50`.

| Page | Items | `totalResults` | Next token | First Video (published) | Last Video (published) |
| --- | --- | --- | --- | --- | --- |
| 1 | 50 | 1667 | present | `moaDf-3AAyY` (2026-10-25T14:00:38Z) | `K2xfwaOzMuY` (2026-09-28T20:00:32Z) |
| 2 | 50 | 1667 | present | `yebiaOdqz34` (2026-09-28T14:00:32Z) | `mTKYPdKH8y0` (2026-08-25T14:00:39Z) |

Observed behavior:

- A full page contains 50 items even though `totalResults` is 1667.
- A `nextPageToken` is returned while more pages exist. The token is opaque and was sent back unchanged for page 2.
- The playlist is ordered newest first by publish time; page 2 starts older than page 1 ends.
- Every one of the 100 items had `contentDetails.videoPublishedAt`, `status.privacyStatus=public`, and a thumbnail URL.
- In this sample, `snippet.publishedAt` and `contentDetails.videoPublishedAt` were equal for every item. The publish time is available in `contentDetails.videoPublishedAt` regardless.

## Video metadata

`videos.list` was called with `part=id,snippet,contentDetails,statistics,status` for the first three Video IDs.

| Video ID | Duration | Definition | Views | Likes | Comments | Privacy | Upload status |
| --- | --- | --- | --- | --- | --- | --- | --- |
| `moaDf-3AAyY` | `PT24S` | `hd` | 2387 | 79 | 84 | `public` | `processed` |
| `B5T4ZCfxp1Y` | `PT1M21S` | `hd` | 27162 | 1254 | 277 | `public` | `processed` |
| `Xyb7me-QpcI` | `PT18S` | `hd` | 19188 | 387 | 123 | `public` | `processed` |

Duration, statistics, and availability state are therefore not available from `playlistItems.list` alone; they require a `videos.list` request, as the package's `SyncChannelVideos` job already does.

A well-formed but nonexistent Video ID (`AAAAAAAAAAA`) returned an empty `items` array with no error. Non-returned Video IDs are how collection detects an unavailable Video.

## Failure responses

| Failure | Trigger | Response | `FailureReason` |
| --- | --- | --- | --- |
| Invalid API key | `YOUTUBE_API_KEY` set to an invalid value | HTTP 400, `Error 400 API key not valid. Please pass a valid API key. : badRequest` | `invalid_key` |
| Transport | Unreachable HTTPS proxy | `Curl Error : Failed to connect to www.googleapis.com:443 over proxy …` | `transport` |
| Channel not found | Valid key, unknown Channel ID or `/user/` name | HTTP 200 with an empty `items` array | resolver `channel not found` |
| Unsupported input | A value that is not an ID, handle, or YouTube URL | No request | resolver `unsupported input` |
| Quota exhausted | Documented response, not produced live (see below) | HTTP 403, reason `quotaExceeded` | `quota_exceeded` |

The invalid-key response carried reason `badRequest`, not `keyInvalid`; the package classifies it from the HTTP 400 and the `API key not valid` message.

## Quota exhaustion

Not produced live. Exhausting the API project's daily quota would block every live YouTube check until the daily reset (16:00 KST), so T-002 records the documented response instead, as permitted by TASKS.md.

Source: <https://developers.google.com/youtube/v3/docs/errors> — general error `quotaExceeded (403)`, detail `quotaExceeded`, "The request cannot be completed because you have exceeded your quota."

The documented payload is stored in `tests/Fixtures/youtube/quota-exceeded-403.json`. `YoutubeApiException::fromApiError()` turns it into `Error 403 The request cannot be completed because you have exceeded your quota. : quotaExceeded`, and `FailureReason::fromThrowable()` returns `quota_exceeded`. The probe test asserts that this documented response is recorded as `quota_exceeded` and stops further requests. It is distinguishable from success and from the other failure reasons above.
