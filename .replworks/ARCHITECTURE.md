# ARCHITECTURE.md

## 1. Purpose

- This document defines the internal responsibilities, information flows, failure boundaries, and invariants for the current Operator-facing catalog product.
- The architecture preserves the distinction between collected source data, unreviewed candidates, operator-confirmed identities, and reviewed filmography entries.

## 2. Core Concepts

- Channel: a registered source of YouTube Videos.
- Video: a YouTube video associated with one Channel.
- Channel Import Row: one Channel reference read from an Operator-supplied spreadsheet.
- Actor Candidate: a source-derived person record that has not been approved by an Operator.
- Actor Identity: an Operator-confirmed grouping of Actor Candidates that refer to the same person.
- Filmography Candidate: a proposed relation between an Actor Candidate and one Video.
- Candidate Source: provenance attached to Actor Candidate information or a Filmography Candidate.
- Review Status: the current decision state of a Filmography Candidate: Pending, Approved, or Rejected.
- Collection Attempt: one processing attempt to collect the available Videos for a Channel.
- Gemini Candidate Extraction: one Operator-started analysis of a selected Video that creates new Actor Candidates and Filmography Candidates.
- NamuWiki Information Suggestion: Actor Candidate field values extracted from a supplied NamuWiki page and presented to the Operator for review.

## 3. System Flow

- The Operator submits a spreadsheet of Channel references.
- The Spreadsheet Import Coordinator processes each row independently and reports that row's result.
- The Channel Registry resolves each accepted reference and upserts its Channel record.
- Duplicate Channel references update the same Channel record without changing its Videos or collection state.
- Newly registered Channels enter the Channel Collection Coordinator's initial collection work.
- The Channel Collection Coordinator continues recurring collection attempts for registered Channels.
- Each successful page of Video data is upserted into the Channel and Video Catalog immediately.
- A later collection failure does not discard Videos collected earlier in the same attempt.
- The Operator selects a collected Video and starts Gemini Candidate Extraction manually.
- The Video Candidate Extraction Coordinator submits the selected Video information to the Gemini Connector.
- A successful extraction creates new Actor Candidates and Pending Filmography Candidates associated with the selected Video.
- A failed or repeated extraction does not update or merge candidates created by an earlier extraction attempt.
- The Operator may associate a NamuWiki URL with an Actor Candidate.
- The Actor Information Intake Coordinator obtains Actor Candidate information suggestions from the page and returns them for Operator review.
- The Operator chooses any suggested values to save; extraction does not overwrite existing Actor Candidate values.
- The Operator may record a Naver Person Information URL and manually entered candidate values.
- The Operator explicitly associates Actor Candidates with one Actor Identity after confirming they identify the same person.
- The Operator reviews each Pending Filmography Candidate and records an approval or rejection decision.

## 4. Components

- C-01 Operator Interface
- C-02 Spreadsheet Import Coordinator
- C-03 Channel Registry
- C-04 Channel and Video Catalog
- C-05 Channel Collection Coordinator
- C-06 YouTube Connector
- C-07 Video Candidate Extraction Coordinator
- C-08 Gemini Connector
- C-09 Actor Information Intake Coordinator
- C-10 NamuWiki Connector
- C-11 Candidate Registry
- C-12 Actor Identity Association Manager
- C-13 Filmography Review Manager

## 5. Component Responsibilities

### C-01 Operator Interface

- Responsibilities: display catalog records and operation results; accept spreadsheet imports, manual extraction commands, candidate edits, identity confirmations, and review decisions.
- Inputs: Operator actions and results from C-02, C-03, C-05, C-07, C-09, C-12, and C-13.
- Outputs: commands to the responsible coordinator or manager; rendered operation results to the Operator.
- Ownership boundary: owns interaction state only; owns no catalog record, candidate record, external request, or business rule.

### C-02 Spreadsheet Import Coordinator

- Responsibilities: read the Channel column; process rows independently; return one result per processed row; continue processing after an individual row failure.
- Inputs: a YouTube Channel Spreadsheet from C-01.
- Outputs: one Channel registration command per valid row to C-03; row-level success or failure results to C-01.
- Ownership boundary: owns the import operation and its row results; owns no Channel identity or persistent catalog data.

### C-03 Channel Registry

- Responsibilities: resolve a Channel reference; upsert the resulting Channel; preserve existing Video records and collection state on repeat registration; request initial collection for a newly registered Channel.
- Inputs: a normalized Channel reference from C-02; resolution results from C-06.
- Outputs: Channel changes to C-04; initial collection requests to C-05; registration results to C-02.
- Ownership boundary: owns Channel identity resolution and registration rules; C-04 owns the persisted Channel record.

### C-04 Channel and Video Catalog

- Responsibilities: retain Channel and Video records, their association, availability state, collection status, and the data returned for each record; provide reads to Operator-facing components.
- Inputs: Channel upserts from C-03; Video and collection-state upserts from C-05.
- Outputs: persisted catalog records and read results to C-01, C-03, C-05, and C-07.
- Ownership boundary: is the sole owner and writer of persisted Channel and Video data; other components request changes through its operations.

### C-05 Channel Collection Coordinator

- Responsibilities: start initial collection for newly registered Channels; schedule recurring collection attempts for registered Channels; retrieve every available uploads page; persist each successful page before proceeding; record per-Channel progress, failure, and quota-paused state.
- Inputs: initial collection requests from C-03; recurring cycle triggers; page results and failures from C-06.
- Outputs: collection requests to C-06; Video and collection-state upserts to C-04; collection results to C-01.
- Ownership boundary: owns collection scheduling and collection-attempt state; owns no Channel or Video identity and performs no Actor Candidate Extraction.

### C-06 YouTube Connector

- Responsibilities: resolve Channel references and retrieve Channel and Video information from YouTube; expose page results, quota exhaustion, and request failures to the caller.
- Inputs: Channel references and collection requests from C-03 and C-05.
- Outputs: Channel resolution results, Video pages, quota state, or failure results.
- Ownership boundary: owns communication with YouTube; persists no Channel, Video, or candidate data.

### C-07 Video Candidate Extraction Coordinator

- Responsibilities: accept only an explicit Operator command for a selected Video; obtain the Video record; request candidate extraction; create new Actor Candidates and Pending Filmography Candidates for each successful run; associate each Filmography Candidate with its source Video and Candidate Source; retain prior candidates when extraction is repeated.
- Inputs: a selected Video and explicit extraction command from C-01; extraction results from C-08.
- Outputs: extraction requests to C-08; new Actor Candidate and Filmography Candidate records to C-11; extraction status to C-01.
- Ownership boundary: owns video-based candidate extraction attempts; starts no extraction from Video registration or collection events; owns no external communication or persisted candidate record.

### C-08 Gemini Connector

- Responsibilities: submit selected Video information for Actor Candidate and Filmography Candidate extraction; return extracted values or an extraction failure.
- Inputs: Video information from C-07.
- Outputs: extracted candidate values or a failure result to C-07.
- Ownership boundary: owns communication with Gemini; decides no Actor Identity, Review Status, or candidate merge.

### C-09 Actor Information Intake Coordinator

- Responsibilities: handle NamuWiki information extraction requests; return extracted values as suggestions; handle manually supplied Naver person information as candidate data; preserve source provenance; ensure extracted suggestions do not overwrite existing Actor Candidate values.
- Inputs: an Actor Candidate ID and NamuWiki URL; or an Actor Candidate ID, Naver Person Information URL, and manually entered values from C-01; page content from C-10.
- Outputs: NamuWiki Information Suggestions to C-01; candidate value and Candidate Source commands to C-11 when the Operator saves selected values.
- Ownership boundary: owns actor-information intake rules; creates no Filmography Candidate and no Actor-Video relation from either reference source.

### C-10 NamuWiki Connector

- Responsibilities: retrieve content for an Operator-supplied NamuWiki URL and report retrieval failures.
- Inputs: a NamuWiki URL from C-09.
- Outputs: page content or a failure result to C-09.
- Ownership boundary: owns communication with NamuWiki; persists no Actor Candidate information and creates no Video association.

### C-11 Candidate Registry

- Responsibilities: retain Actor Candidates, Actor Identities, Filmography Candidates, Candidate Sources, optional candidate values, and review metadata; provide candidate read operations; preserve candidates across Operator sessions.
- Inputs: candidate creation and update commands from C-07 and C-09; identity-association commands from C-12; review-decision commands from C-13.
- Outputs: persisted candidate state and read results to C-01, C-07, C-09, C-12, and C-13.
- Ownership boundary: is the sole owner and writer of persistent candidate, identity, source, and review data; other components cannot write these records directly.

### C-12 Actor Identity Association Manager

- Responsibilities: associate selected Actor Candidates with one Actor Identity only after explicit Operator confirmation; reject association commands that do not contain at least two candidates; never infer identity from matching names.
- Inputs: selected Actor Candidate IDs and explicit confirmation from C-01; candidate data from C-11.
- Outputs: identity-association commands to C-11; association results to C-01.
- Ownership boundary: owns identity association decisions; owns no Actor Candidate fields and performs no automatic name-based matching.

### C-13 Filmography Review Manager

- Responsibilities: apply the Operator's approval or rejection to a Pending Filmography Candidate; retain the decision, review time, and optional review note; expose review results.
- Inputs: a Filmography Candidate ID, decision, and optional note from C-01; candidate state from C-11.
- Outputs: review-state commands to C-11; review results to C-01.
- Ownership boundary: owns review transition rules; is the only component that changes Review Status; does not extract candidates or resolve Actor Identity.

## 6. Responsibility Boundaries

- C-01 owns interaction and command delivery, while coordinators and managers own operation rules.
- C-02 owns spreadsheet row processing; C-03 owns Channel registration semantics; C-04 owns persistent Channel and Video data.
- C-05 owns initial and recurring collection; C-06 owns the YouTube external boundary.
- C-07 owns manually initiated video extraction; C-08 owns the Gemini external boundary.
- C-09 owns Actor Candidate information intake; C-10 owns the NamuWiki external boundary.
- Naver person information is entered by the Operator and stored by C-11 through commands from C-09; no Naver connector participates.
- C-11 is the only writer of candidate, identity, source, and review records.
- C-12 owns explicit identity association; C-13 owns Filmography Candidate review decisions.
- NamuWiki and Naver sources can contribute Actor Candidate information only; neither source can create or update an Actor-Video relationship.
- Gemini extraction can propose Actor Candidates and Filmography Candidates but cannot approve candidates or associate Actor Candidates as the same Actor.

## 7. Data Flow

- Channel import: C-01 -> C-02 -> C-03 -> C-06 for reference resolution -> C-04 for Channel upsert -> C-05 for initial collection -> C-06 for Video pages -> C-04 for each successful Video page.
- Recurring Video collection: cycle trigger -> C-05 -> C-06 -> C-04; C-01 reads Channel status and Videos from C-04.
- Video candidate extraction: C-01 selected-Video command -> C-07 -> C-04 Video read -> C-08 -> C-07 -> C-11 -> C-01 extraction result.
- NamuWiki Actor Candidate information: C-01 URL command -> C-09 -> C-10 -> C-09 suggestions -> C-01 review and field selection -> C-09 -> C-11 on save.
- Naver person information: C-01 manually entered URL and values -> C-09 -> C-11 on save.
- Actor identity association: C-01 selected candidates and confirmation -> C-12 -> C-11.
- Filmography review: C-01 candidate decision -> C-13 -> C-11 -> C-01 review result.

## 8. Architectural Rules

- Spreadsheet rows are processed independently; one invalid row does not roll back successful rows.
- Channel registration is an upsert; repeated references never delete associated Videos or reset collection state.
- Video collection persists each successful page as it arrives; a later failure does not roll back successful Video upserts.
- Channel collection starts for a newly registered Channel and recurs for registered Channels.
- Video Candidate Extraction starts only from an explicit Operator command for a selected Video.
- Each successful Gemini Candidate Extraction creates new candidate records; it does not merge, replace, or modify candidates from another run.
- Every Filmography Candidate created from Video extraction retains an association to its source Video and Actor Candidate.
- NamuWiki extraction yields Actor Candidate information suggestions only; it does not create or update Filmography Candidates or Actor-Video relations.
- NamuWiki suggestions never overwrite existing Actor Candidate values before the Operator selects and saves values.
- Naver person information is stored only from Operator-entered values and source references.
- Actor Candidates are associated with an Actor Identity only after explicit Operator confirmation; name matching alone never causes association.
- All descriptive and evidence values may be absent; internal record identity and the source context required to identify a candidate are maintained separately from those optional values.
- Only C-13 changes a Filmography Candidate Review Status.
- Only an Approved Filmography Candidate is eligible for public display.

## 9. Failure Boundaries

- Spreadsheet parse or Channel resolution failure: C-02 reports failure for that row and continues processing other rows.
- Duplicate Channel row: the row resolves to the existing Channel and upserts it; existing Videos and collection state remain unchanged.
- YouTube request failure after successful pages: C-05 records the Channel failure and C-04 retains all previously and newly successful Video upserts.
- YouTube quota exhaustion: C-05 records the paused state and stops further requests until collection can resume.
- Gemini failure: C-07 reports a failed attempt; no candidate from that failed attempt is created; prior candidates and the Video remain unchanged.
- Repeated Gemini extraction: C-07 creates a distinct set of candidates for a successful new attempt; prior candidate sets remain unchanged.
- NamuWiki retrieval or extraction failure: C-09 reports failure; existing Actor Candidate values and prior Candidate Sources remain unchanged.
- NamuWiki suggestion conflicts with a non-empty Actor Candidate value: C-09 presents the suggestion without replacing the value; the Operator selects and saves any change.
- Candidate save failure: C-11 retains its prior persisted state and C-01 shows the failure with the Operator's inputs retained.
- Identity association failure: C-11 retains prior associations; no partial group association is exposed.
- Filmography review failure: C-11 retains Review Status Pending and prior review metadata.
- Candidate Registry operation failure: candidate creation, Actor Identity association, or review decisions fail without reporting success; any operation whose data was not committed leaves its prior state intact.
- Channel and Video Catalog operation failure: spreadsheet registration or Video collection cannot report an uncommitted catalog change as successful; previously committed catalog records remain intact.
- A persistence failure may affect more than one component; this architecture defines logical ownership and operation outcomes, not independent physical availability domains.

## 10. Non-Goals

- This architecture provides no public Viewer query or presentation path.
- This architecture provides no Channel discovery process; Channel references enter through Operator-supplied spreadsheets.
- This architecture provides no independent work, series, or production catalog apart from YouTube Videos.
- This architecture provides no automatic identity-resolution process.
- This architecture provides no automatic candidate-extraction trigger on Video registration or collection.
- This architecture provides no automated Naver person-information retrieval.

## 11. Architectural Invariants

- INV-01: Every Channel Import Row has an independent result; failure of one row does not undo another row's successful registration.
- INV-02: Re-registering a Channel never deletes its Videos or resets its collection state.
- INV-03: Every Video page successfully returned during collection remains recorded even if a later request in the same attempt fails.
- INV-04: Registering or collecting a Video never starts Gemini Candidate Extraction.
- INV-05: Every Gemini Candidate Extraction is associated with an explicit Operator command and a selected Video.
- INV-06: A successful extraction attempt creates new candidate records and does not alter records from prior attempts.
- INV-07: Every Filmography Candidate from video extraction remains associated with its source Video and Actor Candidate.
- INV-08: NamuWiki and Naver sources never create or alter Actor-Video relationships.
- INV-09: Extracted NamuWiki values cannot replace existing Actor Candidate values until an Operator selects and saves them.
- INV-10: Actor Candidates are grouped under one Actor Identity only through explicit Operator confirmation.
- INV-11: Matching names alone never cause Actor Identity association.
- INV-12: Candidate descriptive and evidence fields may be empty without removing the candidate's internal identity or source context.
- INV-13: A Filmography Candidate is Pending until C-13 records an Operator decision.
- INV-14: Only Review Status Approved is eligible for public display.
- INV-15: C-04 is the sole writer of Channel and Video catalog records.
- INV-16: C-11 is the sole writer of Actor Candidate, Actor Identity, Filmography Candidate, Candidate Source, and review records.

## 12. Resolved Decisions

- D-01 Spreadsheet failure scope: process rows independently and report row-level results; confirmed by the Operator to allow valid rows to succeed when another row fails.
- D-02 Duplicate Channel handling: upsert an existing Channel, including duplicate rows in the same spreadsheet, while preserving Videos and collection state; confirmed by the Operator.
- D-03 Partial Channel collection: preserve every successfully collected Video even when a later request fails; confirmed by the Operator to maximize retained Video data.
- D-04 Channel collection trigger: start collection for newly registered Channels and continue recurring collection independently of Video Candidate Extraction; confirmed by the Operator.
- D-05 Video Candidate Extraction trigger: require an explicit Operator command for each selected Video; newly registered or collected Videos do not trigger extraction automatically; confirmed by the Operator.
- D-06 Repeated extraction: a successful repeated extraction creates a separate candidate set and does not merge with or replace earlier candidate sets; confirmed by the Operator.
- D-07 NamuWiki role: use its content only to suggest Actor Candidate information; never use it to create or modify an Actor-Video relation; confirmed by the Operator.
- D-08 NamuWiki field conflicts: show extracted values as suggestions and do not overwrite existing Actor Candidate values until the Operator chooses and saves them; confirmed by the Operator.
- D-09 Actor Identity association: require explicit Operator confirmation and never merge by name alone; confirmed by the Operator.
- D-10 Empty candidate fields: allow all descriptive and evidence values to be absent while preserving candidate identity and its source context; confirmed by the Operator.
- D-11 Naver handling: retain only Operator-entered person information and its source URL; do not retrieve or process Naver results automatically; confirmed by the Operator.
- D-12 Filmography Candidate review: keep each new video-derived relation Pending until the Operator approves or rejects it; source of Review Status requirements in PRODUCT_SPEC.md.
