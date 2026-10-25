# T-006 Channel collection live observation

Observed on 2026-10-26 with the live YouTube Data API, using the
`CollectChannelVideos` job and the gated live test group.

## Environment

| Item | Value |
| --- | --- |
| PHP | 8.4.27 |
| Laravel | 13.38.0 |
| `cable8mm/youtube` | v6.0.0 |
| Database | SQLite in-memory for the live test; local SQLite for the sample run |
| Run | 2026-10-26 03:32 KST |
| Command | `RUN_LIVE_TESTS=true php artisan test --compact --filter=ChannelCollectionLiveTest` |
| Result | 1 passed (15 assertions) |

The live test is `Tests\Feature\ChannelCollectionLiveTest`. It is skipped unless
`RUN_LIVE_TESTS=true` and `YOUTUBE_API_KEY` are set. CI runs it as a skip; it
passed against the live API when run with both set.

## Multi-page collection

The sample run collected two pages of the `YouTube` Channel
(`UCBR8-60-B28hp2BmDPdntcQ`, uploads playlist `UUBR8-60-B28hp2BmDPdntcQ`) with
`maxPages=2`. Production and the recurring schedule pass no page limit, so the
job reads until the uploads playlist stops returning a `nextPageToken`.

| Item | Value |
| --- | --- |
| Channel status | `ok` |
| Last synchronized | 2026-10-25 18:32:48 UTC |
| Videos retained | 100 |
| Sync errors | 0 |
| Newest collected Video | `QRn8XR3PMAI` |
| Newest title | they’ve got a game plan, but do they have what it takes to escape the demon camera? |
| Newest published | 2026-10-25 18:00:38 UTC |
| Newest duration | `PT1M21S` |
| Newest views | 2415 |
| Oldest collected Video | `6VOWJfYfaNM` |
| Oldest published | 2026-08-25 19:03:31 UTC |

Each Video is stored with its Channel association, title, description,
published date, duration, thumbnail URL, view/like/comment counts, and
`unavailable_at = null`. The live test asserts that the metadata is populated
and that no sync error is recorded.

The live test creates a Video outside the two read pages and asserts that it is
not marked unavailable. A limited collection therefore does not treat unread
pages as an absence observation.

## Live failure

The live test then collects a Channel with an invalid API key. The request fails
with the live YouTube error response and the job records:

| Item | Value |
| --- | --- |
| Channel status | `failed` |
| `last_error_reason` | `invalid_key` |
| Sync error row | present with reason `invalid_key` |

The invalid-key failure is live. The collection failure path does not throw out
of the job: it records the error, retains any Videos stored from successful
pages, and leaves the Channel state readable for SCR-002.

## Quota exhaustion

Not produced live. Exhausting the project's daily quota would block every live
YouTube check until the daily reset, as recorded for T-002. The documented
`quotaExceeded (403)` response from
`tests/Fixtures/youtube/quota-exceeded-403.json` is used in the automated test.

On that response the job:

- records a `youtube_sync_errors` row with reason `quota_exceeded` and HTTP 403,
- sets the Channel `sync_status` to `paused` and `last_error_reason` to
  `quota_exceeded`,
- starts the `QuotaBlock`, which stops new collection attempts until the daily
  reset.

The paused state is a recorded Channel state for SCR-002 to show.

## Partial pages and unavailable Videos

Deterministic tests cover the behaviors that need controlled responses:

- Every returned uploads page is read until the next page token is absent, and
  page size is 50.
- Each page's Videos are stored before the next page is requested. A later page
  failure keeps the earlier pages' Videos and records the failure.
- A complete collection marks a stored Video that the listing did not return as
  unavailable. A later collection that returns it clears `unavailable_at`.
- A limited collection does not flag unread Videos as unavailable.
- A missing uploads playlist is resolved from `channels.list` before collecting.
- A Channel that cannot be resolved is recorded as `channel_not_found`.
- A newly created Channel dispatches the collection job through a model-created
  listener; the import test asserts this without another spreadsheet import.

## Recurring collection

`routes/console.php` schedules `filmo:collect-channels` every day at 04:00
`Asia/Seoul`, without overlapping runs. The command queues a collection job for
every active Channel and passes no page limit, so the recurring collection reads
every available uploads page. The schedule test asserts the cron expression
`0 4 * * *` and the `Asia/Seoul` timezone.

## Display

SCR-002 is delivered by T-007. T-006 records the states that screen shows:
`sync_status` (`ok`, `failed`, `paused`), `last_synced_at`,
`last_error_reason`, `youtube_sync_errors`, and the `QuotaBlock` pause.
