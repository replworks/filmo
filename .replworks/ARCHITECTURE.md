# ARCHITECTURE.md

## 1. Purpose

- This document defines the internal responsibilities, information flows, failure boundaries, and invariants for the Operator-facing Actor and YouTube Video catalog.
- This document also defines the public catalog presentation and the Contributor workflow for adding and editing catalog records.
- The architecture keeps the Operator workflow simple: Actors, the Videos they appear in, and duplicate-Actor merging. It has no separate candidate, identity, filmography, source, or review layer.

## 2. Core Concepts

- Visitor: an unauthenticated person who reads the public catalog.
- Contributor: a self-registered, non-administrator User who adds and edits Channels, Videos, and Actors.
- Public Catalog: the read-only Channel, Video, and Actor presentation available without authentication.
- Channel: a registered source of YouTube Videos.
- Video: a YouTube video associated with one Channel.
- Channel Import Row: one Channel reference read from an Operator-supplied spreadsheet.
- Actor: a person who appears in one or more Videos. An Actor has a required Name and optional Activity Name, Birth Date, Birthplace, Education, Debut, Agency, Nationality, Actor Reference URLs, and Profile Fields.
- Profile Field: an optional named Actor value for information outside the fixed Actor fields, such as an Instagram handle or a YouTube channel. Fixed Actor fields are stored as Actor columns.
- Actor Reference URL: a NamuWiki, Naver, or other reference URL stored as a separate actor_reference_urls record for an Actor. An Actor may have none or many.
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
- The Operator edits Actor fields and Profile Fields, opens supplied Actor Reference URLs, adds or removes Video associations, and merges duplicate Actors.
- A merged Actor is hidden and its Video associations move to the surviving Actor.
- A Visitor reads the Public Catalog without authentication.
- A Contributor registers an account and receives Contributor access.
- A Contributor submits a Channel reference, a video URL, Actor values, and Actor-Video associations; the Contributor Interface delegates those commands to the responsible registry or catalog.
- A Contributor cannot delete records, merge Actors, start Gemini Actor Extraction, or manage Sync Errors.

## 4. Components

- C-01 Operator Interface
- C-02 Spreadsheet Import Coordinator
- C-03 Channel Registry
- C-04 Channel, Video, and Sync Error Catalog
- C-05 Channel Collection Coordinator
- C-06 YouTube Connector
- C-07 Actor Extraction Coordinator
- C-08 Gemini Connector
- C-09 Actor Registry
- C-10 Actor Merge Manager
- C-11 Administrator Provisioning
- C-12 Public Interface
- C-13 Contributor Interface
- C-14 Account Access

## 5. Component Responsibilities

### C-01 Operator Interface

- Responsibilities: display Channel, Video, Sync Error, Actor, and appearance records; accept spreadsheet imports, manual extraction commands, package record edits and deletes, Actor edits, Video link changes, and merge commands; show operation results.
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
- Inputs: a normalized Channel reference from C-02 or C-13; resolution results from C-06.
- Outputs: Channel changes to C-04; initial collection requests to C-05; registration results to C-02 or C-13.
- Ownership boundary: owns Channel identity resolution and registration rules; C-04 owns the persisted Channel record.

### C-04 Channel, Video, and Sync Error Catalog

- Responsibilities: retain Channel, Video, and Sync Error records, their associations, availability state, collection status, and returned data; accept explicit Operator creates, edits, and deletes and Contributor creates and edits for Channels and Videos; accept explicit Operator deletes for Sync Errors; provide reads to Operator-facing, Contributor-facing, and public components.
- Inputs: Channel upserts from C-03; Video and collection-state upserts from C-05; Sync Error records from C-05; Operator create, edit, and delete commands from C-01; Contributor create and edit commands from C-13.
- Outputs: persisted package records and read results to C-01, C-03, C-05, C-07, C-12, and C-13.
- Ownership boundary: is the sole owner and writer of persisted Channel, Video, Sync Error, and package data. It deletes dependent Videos and Actor-Video associations when a Channel is deleted, and dependent Actor-Video associations when a Video is deleted. It does not edit extraction state, extraction error, or extraction time.

### C-05 Channel Collection Coordinator

- Responsibilities: start initial collection for newly registered Channels; schedule recurring collection attempts; retrieve every available uploads page; persist each successful page before proceeding; record per-Channel progress, failure, and quota-paused state.
- Inputs: initial collection requests from C-03; recurring triggers; page results and failures from C-06.
- Outputs: collection requests to C-06; Video and collection-state upserts to C-04; collection results to C-01.
- Ownership boundary: owns collection scheduling and attempt state; performs no Actor extraction.

### C-06 YouTube Connector

- Responsibilities: resolve Channel references and retrieve Channel and Video information from YouTube; resolve a Contributor-supplied video URL to its Video and Channel; expose page results, quota exhaustion, and request failures.
- Inputs: Channel references and collection requests from C-03 and C-05; video URLs from C-13.
- Outputs: Channel resolution results, Video pages, Video lookup results, quota state, or failure results.
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

- Responsibilities: retain Actors, Profile Fields, Actor Reference URLs, and Actor-Video appearances; accept explicit Operator Actor creates, deletes, and Video appearances and Contributor Actor creates and edits and Video appearances; provide Actor reads and writes to Operator-facing, Contributor-facing, and public components; retain records across sessions.
- Inputs: Actor and appearance commands from C-07; Actor create, edit, delete, and Video link commands from C-01; Actor create, edit, and Video link commands from C-13; merge commands from C-10.
- Outputs: persisted Actor and appearance state and read results to C-01, C-07, C-10, C-12, and C-13.
- Ownership boundary: is the sole owner and writer of persistent Actor, Profile Field, Actor Reference URL, and appearance data.

### C-10 Actor Merge Manager

- Responsibilities: merge selected Actors into one surviving Actor only after explicit Operator confirmation; move Actor-Video appearances to the survivor; hide merged Actors; reject merges of fewer than two Actors; never infer a merge from matching names.
- Inputs: selected Actor IDs, survivor choice, and explicit confirmation from C-01; Actor data from C-09.
- Outputs: appearance and merge-state commands to C-09; merge results to C-01.
- Ownership boundary: owns merge decisions; does not edit Actor fields, extract Actors, or merge by name.

### C-11 Administrator Provisioning

- Responsibilities: create a new administrator or grant administrator access to an existing User from an explicit command-line operation; validate the email, name, and password; set the administrator flag.
- Inputs: an administrator email, optional name and password, and explicit command invocation.
- Outputs: persisted User administrator state to the authentication boundary.
- Ownership boundary: owns administrator provisioning; creates no Actors, Channels, Videos, or appearances; does not authenticate Users.

### C-12 Public Interface

- Responsibilities: present the service landing and the Public Catalog; show Channel, Video, and Actor lists and detail; provide entry points to Account Access and the Contributor Interface.
- Inputs: read results from C-04 and C-09; session state from C-14.
- Outputs: read requests to C-04 and C-09; navigation to C-13 and C-14.
- Ownership boundary: owns public presentation and navigation state only; performs no writes and owns no catalog, Actor, or appearance data.

### C-13 Contributor Interface

- Responsibilities: accept Channel references, video URLs, Actor values, Channel and Video edits, and Actor-Video association commands from a Contributor; authorize every write; show operation results.
- Inputs: Contributor actions; reference resolution from C-03 and C-06; read results from C-04 and C-09.
- Outputs: registration commands to C-03; Video upserts to C-04; Actor, reference URL, and appearance commands to C-09.
- Ownership boundary: owns Contributor interaction and command delivery; starts no Gemini Actor Extraction; performs no deletion, merge, or Sync Error management; owns no persistent catalog or Actor data.

### C-14 Account Access

- Responsibilities: register a Visitor as a Contributor; authenticate and sign out Users; request and complete password resets; expose the signed-in User's role.
- Inputs: registration, sign-in, sign-out, and password reset values from C-12 and C-13.
- Outputs: persisted non-administrator User records and session state to C-12 and C-13.
- Ownership boundary: owns account registration and session authentication; grants no access to C-01; creates no catalog, Actor, or appearance records.

## 6. Responsibility Boundaries

- C-01 owns interaction and command delivery, while coordinators and managers own operation rules.
- The Operator interface is available only to Users with administrator access; non-administrator Users are denied before any Operator command is accepted.
- The Public Interface is read-only and available without authentication.
- The Contributor Interface is available to Contributors and Administrators; every Contributor write is authorized before it reaches C-04 or C-09.
- Account Access creates only non-administrator Users and never grants access to C-01.
- C-02 owns spreadsheet row processing; C-03 owns Channel registration semantics; C-04 owns persistent Channel and Video data.
- C-05 owns initial and recurring collection; C-06 owns the YouTube external boundary.
- C-07 owns manually initiated Actor extraction; C-08 owns the Gemini external boundary.
- C-09 owns Actor, Profile Field, Actor Reference URL, and appearance data; C-10 owns explicit Actor merging.
- C-04 is the only writer of Channel, Video, and Sync Error records; C-09 is the only writer of Actor and appearance records.
- C-11 owns administrator provisioning; C-01 allows only Users with the administrator flag.
- Assertion of the administrator flag grants C-01 access; the absence of the flag grants only C-13 access after C-14 authentication.
- C-04 accepts package creates and edits for Channels and Videos from explicit Operator or Contributor commands and deletes for Channels, Videos, and Sync Errors only from explicit Operator commands; Sync Errors are never created or edited in the Operator interface; a Video created from a Channel detail belongs to that Channel; package sync may overwrite edited values.
- Creating a Channel starts its initial Video collection.
- Creating a Video does not start Gemini Actor Extraction.
- There is no separate Actor Candidate, Actor Identity, Filmography Candidate, Candidate Source, or Review Status record.
- NamuWiki and Naver references are stored as Actor Reference URL records and opened by the Operator, a Contributor, or a Visitor; no connector retrieves or parses them.

## 7. Data Flow

- Channel import: C-01 -> C-02 -> C-03 -> C-06 for reference resolution -> C-04 for Channel upsert -> C-05 for initial collection -> C-06 for Video pages -> C-04 for each successful Video page.
- Package data edit or delete: C-01 -> C-04; a Channel delete also removes its Videos and Actor-Video associations; a Video delete removes its Actor-Video associations.
- Sync Error collection: C-05 -> C-04 for each recorded Sync Error.
- Recurring Video collection: cycle trigger -> C-05 -> C-06 -> C-04; C-01 reads Channel status and Videos from C-04.
- Actor extraction: C-01 selected-Video command -> C-07 -> C-04 Video read -> C-08 -> C-07 -> C-09 for Actors and appearances -> C-01 extraction result.
- Actor editing: C-01 or C-13 field values -> C-09.
- Actor Video association: C-01 or C-13 add or remove command -> C-09.
- Actor merge: C-01 selected Actors and confirmation -> C-10 -> C-09 -> C-01 merge result.
- Public catalog: C-12 -> C-04 and C-09 reads -> C-12.
- Account access: C-12 or C-13 registration, sign-in, sign-out, or password reset -> C-14 -> session state to C-12 and C-13.
- Contributor Channel add: C-13 -> C-03 -> C-06 for reference resolution -> C-04 for Channel upsert -> C-05 for initial collection -> C-06 for Video pages -> C-04.
- Contributor Video add: C-13 -> C-06 for video URL lookup -> C-04 for Video upsert associated with its Channel.
- Contributor Actor create or edit: C-13 -> C-09.
- Contributor Actor-Video association: C-13 -> C-09.

## 8. Architectural Rules

- Spreadsheet rows are processed independently; one invalid row does not roll back successful rows.
- Channel registration is an upsert; repeated references never delete associated Videos or reset collection state.
- Video collection persists each successful page as it arrives; a later failure does not roll back successful Video upserts.
- Channel collection starts for a newly registered Channel and recurs for registered Channels.
- Package records may be edited from the explicit package data screens or the Contributor Interface and deleted only from the explicit package data screens; a later YouTube collection may overwrite edits.
- Deleting a Channel deletes its Videos and their Actor-Video associations.
- Deleting a Video deletes its Actor-Video associations.
- Extraction state, extraction error, and extraction time are read-only in package data screens.
- Gemini Actor Extraction starts only from an explicit Operator command for a selected Video.
- Extraction associates Actors with the selected Video; it does not create a separate filmography or review record.
- A repeated extraction does not duplicate an existing Actor-Video appearance.
- An Actor has a required Name; Activity Name, Birth Date, Birthplace, Education, Debut, Agency, Nationality, Actor Reference URLs, and Profile Fields are optional.
- Birth Date, Birthplace, Education, Debut, Agency, and Nationality are stored as Actor columns, not as Profile Fields.
- Profile Fields store only additional information outside the fixed Actor fields.
- Actor Reference URLs are stored and opened only; they are never retrieved or parsed by the product.
- Actor-Video appearances are added and removed explicitly by the Operator or a Contributor.
- Actors are merged only after explicit Operator confirmation; name matching alone never merges Actors.
- Merged Actors are hidden; their appearances move to the surviving Actor.
- All optional Actor values may be absent without removing the Actor or its appearances.
- The public catalog is readable without authentication and never accepts writes.
- The public catalog excludes Sync Errors, extraction errors, and Administrator controls.
- Merged Actors never appear in the public catalog.
- Every Contributor write is authorized before it reaches C-04 or C-09.
- A Contributor may edit any Channel, Video, or Actor but may not delete, merge Actors, start extraction, or manage Sync Errors.
- Creating a Channel from the Contributor Interface starts its initial Video collection.
- Creating a Video from the Contributor Interface associates it with its Channel and never starts Gemini Actor Extraction.
- A failed Channel or video reference resolution leaves stored data unchanged.

## 9. Failure Boundaries

- Spreadsheet parse or Channel resolution failure: C-02 reports failure for that row and continues processing other rows.
- Duplicate Channel row: the row resolves to the existing Channel and upserts it; existing Videos and collection state remain unchanged.
- YouTube request failure after successful pages: C-05 records the Channel failure and C-04 retains all successful Video upserts.
- YouTube quota exhaustion: C-05 records the paused state and stops further requests until collection can resume.
- Gemini failure: C-07 reports a failed extraction; no Actor or appearance from that failed attempt is created; existing Actors, appearances, and the Video remain unchanged.
- Actor save failure: C-09 retains its prior persisted state and C-01 or C-13 shows the failure with the entered values retained.
- Actor-Video add or remove failure: C-09 retains the prior appearance state and C-01 or C-13 shows the failure.
- Merge failure: C-09 retains prior Actor and appearance state; no partial merge is exposed.
- Actor Registry or Catalog failure: operations that did not commit leave their prior state intact.
- Channel reference resolution failure: C-13 reports the failure and C-04 retains its prior state.
- Video URL resolution failure: C-13 reports the failure and C-04 retains its prior state.
- Sign-up, sign-in, or password reset failure: C-14 creates no session and C-12 or C-13 shows the failure.
- Unauthorized write: the command is rejected before C-04 or C-09; stored data is unchanged.

## 10. Non-Goals

- This architecture provides no public catalog search.
- This architecture provides no Contributor deletion, Actor merge, Gemini Actor Extraction, or Sync Error management.
- This architecture provides no Channel discovery process; Channel references enter through Operator-supplied spreadsheets or Contributor-supplied references.
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
- INV-11: C-09 is the sole writer of Actor, Profile Field, Actor Reference URL, and appearance records.
- INV-12: There is no separate review state; an appearance exists or it does not.
- INV-13: Deleting a Channel deletes its Videos and their Actor-Video associations.
- INV-14: Deleting a Video deletes its Actor-Video associations.
- INV-15: Package data screens do not edit extraction state, extraction error, or extraction time.
- INV-16: Creating a Channel starts its initial Video collection; creating a Video does not start Gemini Actor Extraction.
- INV-17: Deleting an Actor deletes its Actor-Video associations.
- INV-19: A Video created from a Channel detail is associated with that Channel.
- INV-20: A Video-Actor association can be added or removed from the Video detail screen and appears on the Actor detail screen.
- INV-21: Only Users with administrator access can access the Operator interface; Visitors can read only the public catalog; Contributors can write only through the Contributor Interface.
- INV-22: An administrator can be provisioned only through the explicit provisioning command or the local administrator seeder.
- INV-23: The public catalog is read-only and never accepts writes.
- INV-24: The public catalog never exposes Sync Errors, extraction errors, or Administrator controls.
- INV-25: Merged Actors never appear in the public catalog.
- INV-26: A Contributor write never deletes a record, merges Actors, starts extraction, or manages Sync Errors.
- INV-27: Self-registration creates a non-administrator User and grants no Operator access.
- INV-28: A Contributor-added Video is associated with its Channel and never starts Gemini Actor Extraction.
- INV-29: A denied or failed write leaves stored data unchanged.
- INV-18: Birth Date, Birthplace, Education, Debut, Agency, and Nationality are Actor columns; Profile Fields store only additional information.

## 12. Resolved Decisions

- D-01 Spreadsheet failure scope: process rows independently and report row-level results; confirmed by the Operator.
- D-02 Duplicate Channel handling: upsert an existing Channel, including duplicate rows, while preserving Videos and collection state; confirmed by the Operator.
- D-03 Partial Channel collection: preserve every successfully collected Video even when a later request fails; confirmed by the Operator.
- D-04 Channel collection trigger: start collection for newly registered Channels and continue recurring collection independently of Actor extraction; confirmed by the Operator.
- D-05 Actor extraction trigger: require an explicit Operator command for each selected Video; newly registered or collected Videos do not trigger extraction automatically; confirmed by the Operator.
- D-06 Actor-Video appearance: extraction associates Actors with the selected Video without creating separate filmography or review records; confirmed by the Operator.
- D-07 Actor profile: keep Name required, store Birth Date, Birthplace, Education, Debut, Agency, and Nationality as fixed Actor columns, and use Profile Fields only for additional information outside that set; confirmed by the Operator.
- D-08 Actor Reference URLs: store zero or more NamuWiki, Naver, or other reference URLs as separate actor_reference_urls records, move them during Actor merges, and open them without automated retrieval; confirmed by the Operator.
- D-09 Actor merge: require explicit Operator confirmation and move appearances to a surviving Actor; matching names alone never merge Actors; confirmed by the Operator.
- D-10 No review workflow: remove separate candidate, identity, filmography, source, and review records to keep the Operator workflow simple; confirmed by the Operator.
- D-11 Package data administration: expose Channels and Videos with explicit create, edit, and delete controls, and Sync Errors with list, view, and delete controls only; protect extraction fields and show cascade-confirmation messages; confirmed by the Operator.
- D-12 Actor administration: allow explicit Actor creation and deletion, with appearance deletion and no automatic name matching; confirmed by the Operator.
- D-13 Detail-screen associations: allow Video creation from a Channel detail and Actor association from a Video detail; confirmed by the Operator.
- D-14 Administrator access: require an administrator flag on the User; non-administrator Users cannot authenticate to or access the Operator interface; confirmed by the Operator.
- D-15 Administrator provisioning: provide an explicit command that creates or promotes an administrator, validates the email and password, and never grants access through self-service; confirmed by the Operator.
- D-16 Public catalog: present a public landing and a read-only Channel, Video, and Actor catalog without authentication; confirmed by the Operator.
- D-17 Contributor access: allow self-registration as a non-administrator Contributor who may add and edit any Channel, Video, or Actor and manage Actor-Video associations, but may not delete, merge Actors, extract Actors, or manage Sync Errors; confirmed by the Operator.
- D-18 Email verification: defer email verification; Contributor access does not require it until a separate decision; confirmed by the Operator.
- D-19 Reference-based addition: resolve Contributor-supplied Channel references and video URLs through YouTube and associate a new Video with its Channel; confirmed by the Operator.
