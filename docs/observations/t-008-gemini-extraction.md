# T-008 Gemini Candidate Extraction live observation

Observed on 2026-10-26 with the live Gemini API, using the extraction job and
the gated live test group.

## Environment

| Item | Value |
| --- | --- |
| PHP | 8.4.27 |
| Laravel | 13.38.0 |
| `cable8mm/youtube` | v6.0.0 |
| Model | `gemini-3.6-flash` |
| Prompt identifier | `video-candidate-extraction/v1` |
| Run | 2026-10-26 03:46 KST |
| Command | `RUN_LIVE_TESTS=true php artisan test --compact --group=live` |
| Result | 3 passed (39 assertions), including this extraction test |

The live test is `Tests\Feature\VideoCandidateExtractionLiveTest`. It is skipped
unless `RUN_LIVE_TESTS=true` and `GEMINI_API_KEY` are set. CI runs it as a skip;
it passed against the live API when run with both set.

## Selected Video

| Field | Value |
| --- | --- |
| Video ID | `G6A-ONXm9D8` |
| Title | 세상 쿨한 예쁜 누나와의 연애 |
| Channel | 채널 352 |
| Published | 2026-10-24T10:30:29Z |
| Duration | `PT9M43S` |
| URL | <https://www.youtube.com/watch?v=G6A-ONXm9D8> |

The description names 수빈/라현서, 연우/홍연우, and 은빈/고은빈 with Instagram
links, so the live extraction has observable values.

## Successful extraction

| Item | Value |
| --- | --- |
| Extraction status | `completed` |
| Error | `null` |
| Extracted at | 2026-10-25 18:46:33 UTC |
| Actor Candidates | 3 |
| Filmography Candidates | 3 |
| Model identifier | `gemini-3.6-flash` |
| Prompt identifier | `video-candidate-extraction/v1` |

Actor Candidates:

| Activity Name | Name | Related Link |
| --- | --- | --- |
| 수빈 | 라현서 | <https://www.instagram.com/rara__0228/> |
| 연우 | 홍연우 | <https://www.instagram.com/yeon.woo._/> |
| 은빈 | 고은빈 | <https://www.instagram.com/ebyibi/> |

Filmography Candidates:

| Name | Review Status | Model | Prompt | Timecode | Confidence | YouTube URL |
| --- | --- | --- | --- | --- | --- | --- |
| 라현서 | `pending` | `gemini-3.6-flash` | `video-candidate-extraction/v1` | `null` | 1.0 | <https://www.youtube.com/watch?v=G6A-ONXm9D8> |
| 홍연우 | `pending` | `gemini-3.6-flash` | `video-candidate-extraction/v1` | `null` | 1.0 | <https://www.youtube.com/watch?v=G6A-ONXm9D8> |
| 고은빈 | `pending` | `gemini-3.6-flash` | `video-candidate-extraction/v1` | `null` | 1.0 | <https://www.youtube.com/watch?v=G6A-ONXm9D8> |

Each Filmography Candidate is associated with the selected Video
(`youtube_video_id`) and with an Actor Candidate. Every new Filmography
Candidate starts Pending. The Video's `extraction_status`, `extraction_error`,
and `extracted_at` record the run outcome.

The connector uses the same prompt contract and nullable response schema that
T-003 verified: Actor Candidate values are Activity Name, Name, and Related
Link; Filmography Candidate values add Video title, Channel name, published
date, YouTube URL, timecode, and confidence. Absent values are explicit nulls.

## Repeated extraction

A repeated run creates new Actor Candidates and new Filmography Candidates. The
job matches a Filmography Candidate to an Actor Candidate created in the same
run by the extracted values; it never reuses or changes candidates from an
earlier run. The tests assert that the earlier IDs and values remain and that
the new run adds a distinct set.

## Live failure

The live test runs a second Video with an invalid Gemini key. The live response
is an API-key error, and the job records:

| Item | Value |
| --- | --- |
| Extraction status | `failed` |
| Error | contains `API key not valid` |
| Extracted at | `null` |
| New candidates | none |
| Earlier candidates | unchanged |

A failed attempt creates no candidate records. The source Video and all
candidates from earlier runs remain unchanged.

## Extraction trigger and states

- Collection and registration never start extraction. A collected Video has no
  extraction status and no candidates until the Operator runs it.
- The Videos relation manager on SCR-002 exposes an explicit **Extract
  Candidates** action for the selected Video.
- The Extraction column shows `Not started`, `In progress`, `Completed`, or
  `Failed`; the error column shows the failure message.
- The action requires confirmation and runs a new extraction for that Video
  only. Other Videos on the Channel are untouched.
- During the synchronous run the action shows its loading state; the persisted
  `in_progress` state is also rendered by the Extraction column.

## Automated coverage

`Tests\Feature\ExtractVideoCandidatesTest` covers the success mapping, the
distinct repeated run, the failed run with no new candidates and unchanged
prior candidates, the absence of automatic extraction, the in-progress state,
and the Operator action for the selected Video. It uses the live responses
recorded under `tests/Fixtures/gemini/`.
