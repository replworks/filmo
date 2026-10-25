# TASKS.md

- [x] T-001 Establish the project foundation
  - Satisfies: TECH_STACK.md §§1–7
  - External boundary: no
  - Acceptance criteria:
    - All constraints in TECH_STACK.md are satisfied and every check defined there passes.
    - The application starts and its automated test suite passes.

- [x] T-002 Verify YouTube channel resolution and collection responses
  - Satisfies: PRODUCT_SPEC.md §§External Systems, Functional Requirements FR-001–FR-002, FR-005–FR-013
  - External boundary: yes
  - Acceptance criteria:
    - An executable live probe exercises the YouTube Data API with supported Channel IDs, handles, and Channel URLs, and records which references resolve and the returned Channel identity.
    - The probe records live Video page data, pagination behavior, returned metadata, and observable failure responses needed by Channel registration and collection. The quota-exhaustion response MAY be recorded from the documented YouTube Data API error specification when exhausting the daily quota is not acceptable.
    - Pass: at least one supported reference resolves to the intended Channel and a live response provides an available Video page; each required failure response, including the documented quota-exhaustion response, is distinguishable from success. Fail: a required response cannot be observed or documented, or cannot be distinguished from success.

- [x] T-003 Verify Gemini candidate extraction responses
  - Satisfies: PRODUCT_SPEC.md §§External Systems, Functional Requirements FR-014–FR-016, FR-037, FR-042
  - External boundary: yes
  - Acceptance criteria:
    - An executable live probe submits collected Video information to Gemini and records the returned Actor Candidate and Filmography Candidate values, including absent values, and the observed failure response.
    - Pass: a successful live response can be represented as candidate values tied to the selected Video, and a failed response can be distinguished without treating it as a successful extraction. Fail otherwise.

- [x] T-004 Verify NamuWiki page retrieval and information extraction
  - Satisfies: PRODUCT_SPEC.md §§External Systems, Functional Requirements FR-017–FR-018, FR-036, FR-041
  - External boundary: yes
  - Acceptance criteria:
    - An executable live probe retrieves an Operator-supplied NamuWiki page and records the page content behavior, extracted Actor Candidate information, and retrieval or processing failure behavior.
    - Pass: a live page produces values that can be presented as suggestions for an Actor Candidate, and an unavailable or unprocessable page produces a distinguishable failure. Fail otherwise.

- [x] T-005 Import Channel spreadsheets with row-level results
  - Satisfies: PRODUCT_SPEC.md §§SCR-001, Functional Requirements FR-001–FR-004, Error Conditions ERR-001–ERR-002, Acceptance Criteria AC-001–AC-002, AC-014
  - External boundary: yes
  - Acceptance criteria:
    - The Operator can submit an Excel spreadsheet with a `channel` column and sees one result for every processed row; supported Channel IDs, handles, and URLs register Channels through the live YouTube boundary.
    - Repeated Channel references, including duplicates in one spreadsheet, upsert the same Channel and preserve its Videos and synchronization state.
    - An unsupported row reports its row-level failure while other rows continue; a spreadsheet-processing failure retains row results already produced.
    - Live end-to-end tests pass for successful registration and Channel-resolution failure; automated unit tests pass where applicable.

- [x] T-006 Collect and retain registered Channel Videos
  - Satisfies: PRODUCT_SPEC.md §§SCR-002, Functional Requirements FR-005–FR-013, FR-043, Error Conditions ERR-003–ERR-004, Acceptance Criteria AC-003–AC-006, AC-018
  - External boundary: yes
  - Acceptance criteria:
    - Newly registered Channels begin collection without another spreadsheet import, and registered Channels receive recurring collection attempts.
    - Live collection retrieves every available uploads page and retains each returned Video with its Channel association and returned metadata across Operator sessions.
    - A Video absent from a later available listing remains recorded as unavailable; a later available response clears that state.
    - Successful pages remain retained when a later request fails; the failure is recorded and shown. Quota exhaustion is recorded and shown as paused collection.
    - Live end-to-end tests pass for successful multi-page collection and the observed failure and quota-exhaustion behavior; automated unit tests pass where applicable.

- [x] T-007 Show Channels, Videos, and collection state to the Operator
  - Satisfies: PRODUCT_SPEC.md §§SCR-002, User Flows UF-001, Acceptance Criteria AC-004–AC-006
  - External boundary: no
  - Acceptance criteria:
    - The Operator can see registered Channels, their recorded synchronization state and Video count, and the Videos belonging to a selected Channel.
    - Video items show the specified title, published date, availability state, and YouTube URL; Channel and Video lists expose their specified empty, populated, loading, and error states.
    - Automated tests pass for list contents, Channel-to-Video association, and the defined states.

- [x] T-008 Manually extract candidates from a selected Video
  - Satisfies: PRODUCT_SPEC.md §§SCR-002, Functional Requirements FR-014–FR-016, FR-037–FR-038, FR-042, Error Condition ERR-005, Acceptance Criteria AC-007, AC-015, AC-017
  - External boundary: yes
  - Acceptance criteria:
    - The Operator can explicitly start Gemini Candidate Extraction for a selected collected Video and sees its in-progress, completed, or failed state; collection and registration alone never start extraction.
    - A successful live Gemini response creates new Actor Candidates and Filmography Candidates tied to the selected Video; each Filmography Candidate starts Pending. A new run creates a distinct set without changing prior candidates.
    - A live Gemini failure is shown and creates no candidates for that failed attempt; the source Video and earlier candidates remain unchanged.
    - Live end-to-end tests pass for successful extraction and an observed extraction failure; automated unit tests pass where applicable.

- [x] T-009 Suggest Actor Candidate information from NamuWiki
  - Satisfies: PRODUCT_SPEC.md §§SCR-003, Functional Requirements FR-017–FR-018, FR-022, FR-036, FR-041, Error Condition ERR-006, Acceptance Criterion AC-008
  - External boundary: yes
  - Acceptance criteria:
    - The Operator can associate a NamuWiki URL with an Actor Candidate and request extraction; live page values appear as suggestions for that candidate.
    - Suggestions do not overwrite existing candidate values and do not create or update a Filmography Candidate or Actor-Video relationship; only values selected and saved by the Operator change candidate data.
    - A live retrieval or extraction failure is shown while the supplied URL and prior candidate information remain available.
    - Live end-to-end tests pass for a successful page and an observed retrieval or processing failure; automated unit tests pass where applicable.

- [x] T-010 Record Naver person information as a manual source
  - Satisfies: PRODUCT_SPEC.md §§SCR-003, User Flows UF-004, Functional Requirements FR-019–FR-022, Acceptance Criterion AC-009
  - External boundary: yes
  - Acceptance criteria:
    - The Operator can open the supplied Naver Person Information URL, manually enter candidate values, and save those values with their source URL.
    - A live end-to-end test confirms the supplied URL opens as a manual research source and the saved values and URL are retained.
    - Automated unit tests pass where applicable, and no automated Naver search-result or person-information collection occurs.

- [x] T-011 Edit and save optional candidate fields and sources
  - Satisfies: PRODUCT_SPEC.md §§SCR-003–SCR-004, Functional Requirements FR-022–FR-025, Error Condition ERR-007, Acceptance Criterion AC-010
  - External boundary: no
  - Acceptance criteria:
    - The Operator can edit Actor Candidate and Filmography Candidate fields and save Candidate Sources associated with the relevant candidate data.
    - All proposed candidate and evidence fields can be empty and the candidate can still be saved.
    - A save failure is shown and preserves the Operator's entered values; automated tests pass for successful saves, empty values, and failure handling.

- [x] T-012 Confirm Actor Identity associations
  - Satisfies: PRODUCT_SPEC.md §§SCR-003, User Flow UF-006, Functional Requirements FR-039–FR-040, Acceptance Criterion AC-016
  - External boundary: no
  - Acceptance criteria:
    - The Operator can select at least two Actor Candidates and explicitly associate them with one Actor Identity.
    - Matching names alone never associate candidates; failed association leaves prior associations unchanged and reports failure.
    - Automated tests pass for explicit association, insufficient selection, same-name unassociated candidates, and failure handling.

- [x] T-013 Review Filmography Candidates
  - Satisfies: PRODUCT_SPEC.md §§SCR-004, Functional Requirements FR-023–FR-025, FR-026–FR-035, Error Condition ERR-008, Acceptance Criteria AC-011–AC-013
  - External boundary: no
  - Acceptance criteria:
    - The Operator can list Pending Filmography Candidates and inspect each candidate's recorded data and Candidate Sources.
    - The Operator can edit candidate fields, approve or reject a Pending candidate, and supply an optional review note; the decision, reviewer, and review time are retained.
    - Approved candidates become eligible for public display; Pending and Rejected candidates remain ineligible. No public Viewer screen is added.
    - A failed review save is shown and leaves the candidate Pending; automated tests pass for display eligibility, successful decisions, and failure handling.

- [x] T-014 Simplify to the Actor and Actor-Video catalog
  - Satisfies: PRODUCT_SPEC.md §§SCR-002–SCR-003, Functional Requirements FR-014–FR-025, Acceptance Criteria AC-007–AC-014
  - External boundary: no
  - Supersedes: T-008–T-013
  - Acceptance criteria:
    - Actor and Actor-Video appearance records replace separate Actor Candidate, Actor Identity, Filmography Candidate, Candidate Source, and Review Status records.
    - A successful Gemini Actor Extraction associates extracted Actors with the selected Video; a repeated extraction does not duplicate that association.
    - The Operator can edit Actor Name, Activity Name, Actor URL, and flexible Profile Fields, add or remove an Actor-Video association, and merge Actors; merged Actors are hidden and their associations move to the survivor.
    - No review or approval screen, source table, or name-only automatic merge remains; all tests pass.
