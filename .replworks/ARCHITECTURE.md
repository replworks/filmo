# ARCHITECTURE.md

## 1. Purpose

- This document defines the internal responsibilities, information flows, failure boundaries, and invariants for the Operator-facing Actor and YouTube Video catalog.
- The architecture keeps the Operator workflow simple: Actors, the Videos they appear in, and duplicate-Actor merging. It has no separate candidate, identity, filmography, source, or review layer.

## 2. Core Concepts

- Channel: a registered source of YouTube Videos.
- Video: a YouTube video associated with one Channel.
- Channel Import Row: one Channel reference read from an Operator-supplied spreadsheet.
- Actor: a person who appears in one or more Videos. An Actor has a required Name and optional Activity Name, Actor URL, and Profile Fields.
- Profile Field: an optional named Actor value, stored without a fixed set of columns so fields can be added or removed as data is collected.
- Actor URL: a NamuWiki, Naver, or other reference URL stored for an Actor and opened by the Operator.
- Actor Video Appearance: the association between one Actor and one Video.
- Collection Attempt: one processing attempt to collect the available Videos for a Channel.
- Gemini Actor Extraction: one Operator-started analysis of a selected Video that associates extracted Actors with that Video.
- Actor Merge: one Operator-confirmed operation that combines two or more Actors into a surviving Actor.

## 3. System Flow

- The Operator submits a spreadsheet of Channel references.
- The Spreadsheet Import Coordinator processes each row independently and reports that row's result.
- The Channel Registry resolves each accepted reference and upserts its Channel record.
- Duplicate Channel references update the same Channel record without changing its Videos or collection state.
- Newly registered Channels enter the Channel Collection Coordinator's initial collection work.
- The Channel Collection Coordinator continues recurring collection attempts for registered Channels.
- Each successful page of Video data is upserted into the Channel and Video Catalog immediately.
- A later collection failure does not discard Videos collected earlier in the same attempt.
- The Operator selects a collected Video and starts Gemini Actor Extraction manually.
- The Actor Extraction Coordinator submits the selected Video information to the Gemini Connector.
- A successful extraction associates the extracted Actors with the selected Video.
- The Operator edits Actor fields and Profile Fields, opens a supplied Actor URL, adds or removes Video associations, and merges duplicate Actors.
- A merged Actor is hidden and its Video associations move to the surviving Actor.

## 4. Components

- C-01 Operator Interface
- C-02 Spreadsheet Import Coordinator
- C-03 Channel Registry
- C-04 Channel and Video Catalog
- C-05 Channel Collection Coordinator
- C-06 YouTube Connector
- C-07 Actor Extraction Coordinator
- C-08 Gemini Connector
- C-09 Actor Registry
- C-10 Actor Merge Manager

## 5. Component Responsibilities

### C-01 Operator Interface

- Responsibilities: display Channel, Video, Actor, and appearance records; accept spreadsheet imports, manual extraction commands, Actor edits, Video link changes, and merge commands; show operation results.
- Inputs: Operator actions and results from C-02, C-03, C-05, C-07, C-09, and C-10.
- Outputs: commands to the responsible coordinator or registry; rendered operation results.
- Ownership boundary: owns interaction state only; owns no catalog, Actor, appearance, external request, or business rule.

### C-02 Spreadsheet Import Coordinator

- Responsibilities: read the Channel column; process rows independently; return one result per processed row; continue after an individual row failure.
- Inputs: a YouTube Channel Spreadsheet from C-01.
- Outputs: one Channel registration command per valid row to C-03; row-level results to C-01.
- Ownership boundary: owns the import operation and its row results; owns no Channel identity or persistent catalog data.

### C-03 Channel Registry

- Responsibilities: resolve a Channel reference; upsert the resulting Channel; preserve existing Video records and collection state on repeat registration; request initial collection for a newly registered Channel.
- Inputs: a normalized Channel reference from C-02; resolution results from C-06.
- Outputs: Channel changes to C-04; initial collection requests to C-05; registration results to C-02.
- Ownership boundary: owns Channel identity resolution and registration rules; C-04 owns the persisted Channel record.

### C-04 Channel and Video Catalog

- Responsibilities: retain Channel and Video records, their association, availability state, collection status, and returned data; provide reads to Operator-facing components.
- Inputs: Channel upserts from C-03; Video and collection-state upserts from C-05.
- Outputs: persisted catalog records and read results to C-01, C-03, C-05, and C-07.
- Ownership boundary: is the sole owner and writer of persisted Channel and Video data.

### C-05 Channel Collection Coordinator

- Responsibilities: start initial collection for newly registered Channels; schedule recurring collection attempts; retrieve every available uploads page; persist each successful page before proceeding; record per-Channel progress, failure, and quota-paused state.
- Inputs: initial collection requests from C-03; recurring triggers; page results and failures from C-06.
- Outputs: collection requests to C-06; Video and collection-state upserts to C-04; collection results to C-01.
- Ownership boundary: owns collection scheduling and attempt state; performs no Actor extraction.

### C-06 YouTube Connector

- Responsibilities: resolve Channel references and retrieve Channel and Video information from YouTube; expose page results, quota exhaustion, and request failures.
- Inputs: Channel references and collection requests from C-03 and C-05.
- Outputs: Channel resolution results, Video pages, quota state, or failure results.
- Ownership boundary: owns communication with YouTube; persists no Channel, Video, or Actor data.

### C-07 Actor Extraction Coordinator

- Responsibilities: accept only an explicit Operator command for a selected Video; obtain the Video record; request Actor extraction; associate each extracted Actor with the selected Video; avoid duplicating an existing Actor-Video appearance; record extraction status and failure.
- Inputs: a selected Video and explicit extraction command from C-01; extraction results from C-08.
- Outputs: extraction requests to C-08; Actor and appearance commands to C-09; extraction status to C-01.
- Ownership boundary: owns extraction attempts; starts no extraction from Video registration or collection; owns no external communication or persisted Actor record.

### C-08 Gemini Connector

- Responsibilities: submit selected Video information for Actor extraction; return extracted Actor values or an extraction failure.
- Inputs: Video information from C-07.
- Outputs: extracted Actor values or a failure result to C-07.
- Ownership boundary: owns communication with Gemini; decides no Actor identity or merge.

### C-09 Actor Registry

- Responsibilities: retain Actors, Profile Fields, Actor URLs, and Actor-Video appearances; provide Actor reads and writes; retain records across sessions.
- Inputs: Actor and appearance commands from C-07; Actor field and Video link commands from C-01; merge commands from C-10.
- Outputs: persisted Actor and appearance state and read results to C-01, C-07, and C-10.
- Ownership boundary: is the sole owner and writer of persistent Actor, Profile Field, URL, and appearance data.

### C-10 Actor Merge Manager

- Responsibilities: merge selected Actors into one surviving Actor only after explicit Operator confirmation; move Actor-Video appearances to the survivor; hide merged Actors; reject merges of fewer than two Actors; never infer a merge from matching names.
- Inputs: selected Actor IDs, survivor choice, and explicit confirmation from C-01; Actor data from C-09.
- Outputs: appearance and merge-state commands to C-09; merge results to C-01.
- Ownership boundary: owns merge decisions; does not edit Actor fields, extract Actors, or merge by name.

## 6. Responsibility Boundaries

- C-01 owns interaction and command delivery, while coordinators and managers own operation rules.
- C-02 owns spreadsheet row processing; C-03 owns Channel registration semantics; C-04 owns persistent Channel and Video data.
- C-05 owns initial and recurring collection; C-06 owns the YouTube external boundary.
- C-07 owns manually initiated Actor extraction; C-08 owns the Gemini external boundary.
- C-09 owns Actor, Profile Field, URL, and appearance data; C-10 owns explicit Actor merging.
- C-04 is the only writer of Channel and Video records; C-09 is the only writer of Actor and appearance records.
- There is no separate Actor Candidate, Actor Identity, Filmography Candidate, Candidate Source, or Review Status record.
- NamuWiki and Naver references are stored as an Actor URL and opened by the Operator; no connector retrieves or parses them.

## 7. Data Flow

- Channel import: C-01 -> C-02 -> C-03 -> C-06 for reference resolution -> C-04 for Channel upsert -> C-05 for initial collection -> C-06 for Video pages -> C-04 for each successful Video page.
- Recurring Video collection: cycle trigger -> C-05 -> C-06 -> C-04; C-01 reads Channel status and Videos from C-04.
- Actor extraction: C-01 selected-Video command -> C-07 -> C-04 Video read -> C-08 -> C-07 -> C-09 for Actors and appearances -> C-01 extraction result.
- Actor editing: C-01 field values -> C-09.
- Actor Video association: C-01 add or remove command -> C-09.
- Actor merge: C-01 selected Actors and confirmation -> C-10 -> C-09 -> C-01 merge result.

## 8. Architectural Rules

- Spreadsheet rows are processed independently; one invalid row does not roll back successful rows.
- Channel registration is an upsert; repeated references never delete associated Videos or reset collection state.
- Video collection persists each successful page as it arrives; a later failure does not roll back successful Video upserts.
- Channel collection starts for a newly registered Channel and recurs for registered Channels.
- Gemini Actor Extraction starts only from an explicit Operator command for a selected Video.
- Extraction associates Actors with the selected Video; it does not create a separate filmography or review record.
- A repeated extraction does not duplicate an existing Actor-Video appearance.
- An Actor has a required Name; Activity Name, Actor URL, and Profile Fields are optional.
- Profile Fields are stored as flexible name/value data so fields can be added or removed without changing the Actor identity.
- An Actor URL is stored and opened only; it is never retrieved or parsed by the product.
- Actor-Video appearances are added and removed explicitly by the Operator.
- Actors are merged only after explicit Operator confirmation; name matching alone never merges Actors.
- Merged Actors are hidden; their appearances move to the surviving Actor.
- All optional Actor values may be absent without removing the Actor or its appearances.

## 9. Failure Boundaries

- Spreadsheet parse or Channel resolution failure: C-02 reports failure for that row and continues processing other rows.
- Duplicate Channel row: the row resolves to the existing Channel and upserts it; existing Videos and collection state remain unchanged.
- YouTube request failure after successful pages: C-05 records the Channel failure and C-04 retains all successful Video upserts.
- YouTube quota exhaustion: C-05 records the paused state and stops further requests until collection can resume.
- Gemini failure: C-07 reports a failed extraction; no Actor or appearance from that failed attempt is created; existing Actors, appearances, and the Video remain unchanged.
- Actor save failure: C-09 retains its prior persisted state and C-01 shows the failure with the Operator's inputs retained.
- Actor-Video add or remove failure: C-09 retains the prior appearance state and C-01 shows the failure.
- Merge failure: C-09 retains prior Actor and appearance state; no partial merge is exposed.
- Actor Registry or Catalog failure: operations that did not commit leave their prior state intact.

## 10. Non-Goals

- This architecture provides no public Viewer query or presentation path.
- This architecture provides no Channel discovery process; Channel references enter through Operator-supplied spreadsheets.
- This architecture provides no separate Actor Candidate, Actor Identity, Filmography Candidate, Candidate Source, or Review Status entity.
- This architecture provides no approval or rejection workflow.
- This architecture provides no automatic identity resolution or name-based Actor merge.
- This architecture provides no automated NamuWiki or Naver retrieval.

## 11. Architectural Invariants

- INV-01: Every Channel Import Row has an independent result; failure of one row does not undo another row's successful registration.
- INV-02: Re-registering a Channel never deletes its Videos or resets its collection state.
- INV-03: Every Video page successfully returned during collection remains recorded even if a later request fails.
- INV-04: Registering or collecting a Video never starts Gemini Actor Extraction.
- INV-05: Every Gemini Actor Extraction is associated with an explicit Operator command and a selected Video.
- INV-06: Every extracted Actor is associated with the selected Video; a repeated extraction does not duplicate that association.
- INV-07: Merged Actors are hidden and their Actor-Video appearances belong to the surviving Actor.
- INV-08: Actors are merged only through explicit Operator confirmation; matching names alone never merge Actors.
- INV-09: All optional Actor values may be absent without removing the Actor's identity or appearances.
- INV-10: C-04 is the sole writer of Channel and Video records.
- INV-11: C-09 is the sole writer of Actor, Profile Field, Actor URL, and appearance records.
- INV-12: There is no separate review state; an appearance exists or it does not.

## 12. Resolved Decisions

- D-01 Spreadsheet failure scope: process rows independently and report row-level results; confirmed by the Operator.
- D-02 Duplicate Channel handling: upsert an existing Channel, including duplicate rows, while preserving Videos and collection state; confirmed by the Operator.
- D-03 Partial Channel collection: preserve every successfully collected Video even when a later request fails; confirmed by the Operator.
- D-04 Channel collection trigger: start collection for newly registered Channels and continue recurring collection independently of Actor extraction; confirmed by the Operator.
- D-05 Actor extraction trigger: require an explicit Operator command for each selected Video; newly registered or collected Videos do not trigger extraction automatically; confirmed by the Operator.
- D-06 Actor-Video appearance: extraction associates Actors with the selected Video without creating separate filmography or review records; confirmed by the Operator.
- D-07 Actor profile: keep Name required and store additional fields as flexible Profile Fields, because the useful field set is unknown until data is collected; confirmed by the Operator.
- D-08 Actor URL: store one NamuWiki, Naver, or other reference URL and open it without automated retrieval; confirmed by the Operator.
- D-09 Actor merge: require explicit Operator confirmation and move appearances to a surviving Actor; matching names alone never merge Actors; confirmed by the Operator.
- D-10 No review workflow: remove separate candidate, identity, filmography, source, and review records to keep the Operator workflow simple; confirmed by the Operator.
