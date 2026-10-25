# T-005 Channel import live observation

Observed on 2026-10-26 with the live YouTube Data API, using the
`live` PHPUnit group.

## Environment

| Item | Value |
| --- | --- |
| PHP | 8.4.27 |
| Laravel | 13.38.0 |
| `cable8mm/youtube` | v6.0.0 |
| Database | SQLite in-memory test database |
| Run | 2026-10-26 03:26 KST |
| Command | `RUN_LIVE_TESTS=true php artisan test --compact --group=live` |
| Result | 1 passed (3 assertions) |

The live test is `Tests\Feature\ChannelImportLiveTest`. It is skipped unless
`RUN_LIVE_TESTS=true` and `YOUTUBE_API_KEY` are set, so CI runs it as a skip; it
passed against the live API when run with both set.

## Spreadsheet

The live test uploads this CSV through the Channel Import page's import path:

```csv
channel
@YouTube
@thisusernamedoesnotexist12345
not a channel
```

## Row results

| Row | Input | Result |
| --- | --- | --- |
| 2 | `@YouTube` | `created` |
| 3 | `@thisusernamedoesnotexist12345` | `failed: channel not found` |
| 4 | `not a channel` | `failed: unsupported input` |

Every processed row has a result, and the two failures do not stop the other
rows.

## Registered Channel

| Field | Value |
| --- | --- |
| Channel ID | `UCBR8-60-B28hp2BmDPdntcQ` |
| Title | YouTube |
| Handle | `@youtube` |
| Uploads playlist | `UUBR8-60-B28hp2BmDPdntcQ` |

The handle resolves through `channels.list` without `search.list`, as recorded
in the T-002 observation. The created Channel is persisted in
`youtube_channels`.

## Failure behavior

- An unknown handle returns no `channels.list` item. The resolver reports
  `channel not found`, and the row result is `failed: channel not found`.
- An unsupported value is rejected before any request. The resolver reports
  `unsupported input`, and the row result is `failed: unsupported input`.

## Deterministic coverage

The following behavior is covered by `Tests\Feature\ChannelImportTest` with a
mocked YouTube boundary, because it does not require a distinct live response:

- Channel IDs, handles, Channel URLs, and `/user/` URLs resolve and upsert one
  Channel record.
- Duplicate references in one spreadsheet report `created` once and `updated`
  thereafter, and the database keeps one Channel.
- Importing an existing Channel updates its identity fields while preserving its
  Videos, synchronization state, and last synchronization time.
- `.csv`, `.xlsx`, and `.xls` files import through the same path.
- A spreadsheet without a `channel` column reports a failure and imports
  nothing.
- A spreadsheet-processing failure keeps the row results produced before the
  failure.
- An unsupported file extension is rejected before processing.

The live test covers the external boundary: successful Channel resolution and
registration, an unknown Channel reference, and an unsupported reference.
