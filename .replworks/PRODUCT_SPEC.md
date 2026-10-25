# PRODUCT_SPEC.md

## Purpose

- The product enables an Operator to build a filmography catalog for actors appearing in Korean YouTube web-drama and sketch-comedy videos.
- The product enables an Operator to register YouTube channels, collect their videos, identify actor candidates, collect actor information from reference pages, and review candidates.
- The product retains catalog and review records across Operator sessions.

Terms:

- Operator: a person who uses the product to collect and review catalog data.
- Channel: a YouTube channel registered by an Operator.
- Video: a YouTube video published by a Channel.
- Gemini Candidate Extraction: an Operator-started operation that uses a Video to produce Actor Candidates and Filmography Candidates.
- Actor Candidate: a person record that has not been approved by an Operator.
- Actor Identity: an Operator-confirmed association of Actor Candidates that identify the same person.
- Filmography Candidate: a proposed record that an Actor appears in a Video.
- Candidate Source: a source associated with an Actor Candidate or Filmography Candidate.
- Review Status: the state of a Filmography Candidate. Values are Pending, Approved, and Rejected.
- Approved Entry: a Filmography Candidate with Review Status Approved.
- YouTube Channel Spreadsheet: an Excel file with a `channel` column containing YouTube channel identifiers, handles, or supported channel URLs.
- NamuWiki URL: a URL identifying a NamuWiki page supplied by an Operator.
- Naver Person Information URL: a URL identifying a Naver person information page supplied by an Operator.

## Problem

- YouTube videos do not provide structured cast credits for the product's target videos.
- The product needs a way for an Operator to accumulate videos and actor-filmography candidates and review them before approval.

## Product Goals

- Enable an Operator to register YouTube Channels from a YouTube Channel Spreadsheet.
- Enable an Operator to see registered Channels and their collected Videos.
- Enable an Operator to collect available Videos published by registered Channels.
- Enable an Operator to obtain Actor Candidates and Filmography Candidates from collected Videos.
- Enable an Operator to start candidate extraction for a selected Video.
- Enable an Operator to confirm that Actor Candidates identify the same Actor.
- Enable an Operator to provide a NamuWiki URL and obtain Actor Candidate information from its content.
- Enable an Operator to record Naver person information as a manually researched Candidate Source.
- Enable an Operator to review Filmography Candidates and approve or reject them.
- Keep unapproved candidates separate from Approved Entries.

## Users

- Operator: registers Channels, reviews collection results, supplies reference URLs, and reviews candidates.
- The current product has no Viewer interface.
- The current product has no Requester interface.

## Inputs

- YouTube Channel Spreadsheet: an Excel file with extension `.xlsx`, `.xls`, or `.csv` and a `channel` column. Each value is a Channel ID, an `@handle`, or a URL whose path begins with `/channel/`, `/@`, or `/user/`.
- Video source: a Video collected from a registered Channel.
- NamuWiki URL: a URL supplied as a source of Actor Candidate information.
- Naver Person Information URL: a URL supplied by an Operator as a manual research source.
- Filmography Candidate fields: Activity Name, Name, Related Link, Video title, Channel name, published date, YouTube video URL, Review Status, Candidate Source URL, video timecode, collection method, confidence value, model identifier, prompt identifier, reviewer, review time, and review note.

## Outputs

- Channel import results for each spreadsheet row.
- Registered Channel records.
- Video records associated with registered Channels.
- Actor Candidates extracted from collected Videos.
- Actor Identities confirmed by an Operator.
- Filmography Candidates extracted from collected Videos.
- Actor Candidate information extracted from NamuWiki content.
- Candidate Source records associated with candidate data.
- Review results showing Approved or Rejected Filmography Candidates.

## Constraints

- Operator-facing interface language: None stated.
- Supported browsers: None stated.
- Supported window widths: None stated.
- Performance: None stated.
- Accessibility: None stated.
- Offline behavior: None stated.
- Data retention: Channel, Video, Actor Candidate, Actor Identity, Filmography Candidate, Candidate Source, and review records persist across Operator sessions.

## External Systems [UNVERIFIED]

- YouTube Data API: the product obtains Channel information and available Video information for registered Channels.
- Gemini: the product submits collected Video information for Actor Candidate and Filmography Candidate extraction.
- NamuWiki: the product obtains page content for a NamuWiki URL supplied by an Operator and extracts Actor Candidate information from that content. NamuWiki content does not create or update Actor-Video relationships.
- Naver person information: the Operator opens a Naver Person Information URL and manually records relevant information as candidate data. The product does not automatically collect or process Naver search results.

## User Interface [UNVERIFIED]

### SCR-001 Channel Import

- ID: SCR-001
- Purpose: the Operator registers Channels from a YouTube Channel Spreadsheet.
- Elements:
  - EL-001 Spreadsheet File Input: accepts one YouTube Channel Spreadsheet. User actions: select a file.
  - EL-002 Import Control: imports the selected file. User actions: activate.
  - EL-003 Import Results: shows a result for each processed spreadsheet row. User actions: none.
  - EL-004 Channel List Link: opens SCR-002. User actions: select.
- Arrangement: EL-001 and EL-002 are grouped. EL-003 follows the import controls. EL-004 follows EL-003.
- States:
  - Initial: EL-001 and EL-004 are shown. EL-002 is disabled until a file is selected. EL-003 is not shown.
  - Importing: EL-001 and EL-002 are disabled. EL-003 shows import progress.
  - Completed: EL-001, EL-002, EL-003, and EL-004 are shown. EL-003 shows the result for each processed row.
  - Error: EL-003 shows the import failure and any row-level failures.
- Navigation:
  - Select EL-004 -> SCR-002.
- Interactions:
  - Select a file in EL-001 -> EL-002 is enabled.
  - Activate EL-002 -> the product processes the spreadsheet and shows the result for each row in EL-003.
  - Import a Channel already registered -> the product updates that Channel record and retains its collected Videos and synchronization state.

### SCR-002 Channel and Video Catalog

- ID: SCR-002
- Purpose: the Operator inspects registered Channels and their collected Videos.
- Elements:
  - EL-005 Channel List: lists registered Channels. User actions: select a Channel.
  - EL-006 Channel Item: shows a Channel name, handle when recorded, synchronization state, most recent synchronization time, and collected Video count. User actions: select.
  - EL-007 Video List: lists Videos associated with the selected Channel. User actions: none.
  - EL-008 Video Item: shows a Video title, published date, availability state, and YouTube video URL. User actions: select.
  - EL-009 Import Channels Link: opens SCR-001. User actions: select.
  - EL-010 Candidate Review Link: opens SCR-004. User actions: select.
  - EL-029 Gemini Candidate Extraction Control: starts Gemini Candidate Extraction for the selected Video. User actions: activate.
  - EL-030 Extraction Status Display: shows the selected Video's extraction state. User actions: none.
- Arrangement: EL-005 and EL-006 form the Channel group. EL-007 and EL-008 appear below the selected Channel. EL-029 and EL-030 appear with the selected Video. EL-009 and EL-010 appear as navigation controls.
- States:
  - Empty: EL-005 shows an empty list. EL-007 is not shown.
  - Populated: EL-005 and EL-006 are shown. Selecting a Channel with Videos shows EL-007 and EL-008.
  - Loading: the list being loaded shows a loading indicator.
  - Error: the affected list shows an error state.
  - Extraction in progress: EL-030 shows that Gemini Candidate Extraction is in progress for the selected Video.
  - Extraction completed: EL-030 shows that Gemini Candidate Extraction completed for the selected Video.
  - Extraction failed: EL-030 shows that Gemini Candidate Extraction failed for the selected Video.
- Navigation:
  - Select EL-009 -> SCR-001.
  - Select EL-010 -> SCR-004.
- Interactions:
  - Select EL-006 -> EL-007 shows the Videos collected for that Channel.
  - A newly registered Channel -> the product collects its available Videos without another spreadsheet import.
  - A Video that is no longer returned as available by YouTube -> the product marks the Video unavailable and retains its record.
  - A Video that becomes available again -> the product clears its unavailable state.
  - Activate EL-029 -> the product starts Gemini Candidate Extraction for the selected Video and updates EL-030.
  - Register or collect a Video -> Gemini Candidate Extraction does not start automatically.

### SCR-003 Actor Candidate Sources

- ID: SCR-003
- Purpose: the Operator reviews an Actor Candidate and supplies a reference source for Actor Candidate information or manual research.
- Elements:
  - EL-011 Actor Candidate List: lists Actor Candidates. User actions: select one or more candidates.
  - EL-012 Actor Candidate Fields: shows editable Activity Name, Name, and Related Link fields. User actions: edit field values.
  - EL-013 NamuWiki URL Input: holds a NamuWiki URL. User actions: enter, edit, or clear a URL.
  - EL-014 Extract Actor Information Control: requests Actor Candidate information extraction from the supplied NamuWiki URL. User actions: activate.
  - EL-015 Naver Person Information URL Input: holds a Naver Person Information URL. User actions: enter, edit, or clear a URL.
  - EL-016 Save Candidate Control: saves candidate field values and Candidate Sources. User actions: activate.
  - EL-017 Extraction Result Area: shows extraction progress, suggested Actor Candidate information, or an error. User actions: none.
  - EL-018 Video Candidate Review Link: opens SCR-004. User actions: select.
  - EL-031 Confirm Same Actor Control: associates selected Actor Candidates with one Actor Identity after Operator confirmation. User actions: activate.
- Arrangement: EL-011 appears first. EL-012 follows the selected Actor Candidate. EL-013 and EL-014 are grouped. EL-015 and EL-016 follow the extraction controls. EL-017 follows EL-014. EL-018 and EL-031 are controls for navigation and identity confirmation.
- States:
  - Empty: EL-011 shows no Actor Candidates. EL-012 through EL-017 are not shown.
  - Populated: selecting one Actor Candidate shows EL-012 through EL-016. Selecting two or more Actor Candidates enables EL-031.
  - Extracting: EL-013 and EL-014 are disabled. EL-017 shows progress.
  - Extraction Completed: EL-017 shows suggested Actor Candidate information.
  - Error: EL-017 shows the extraction failure. The supplied URL and candidate field values are retained.
- Navigation:
  - Select EL-018 -> SCR-004.
- Interactions:
  - Enter a NamuWiki URL in EL-013 -> EL-014 is enabled.
  - Activate EL-014 -> the product extracts Actor Candidate information and shows it as suggestions in EL-017. Existing Actor Candidate values are not overwritten.
  - Review suggested Actor Candidate information in EL-017 -> the Operator may edit EL-012 and save selected values with EL-016.
  - Extracted Actor Candidate information MUST NOT create or update a Filmography Candidate or an Actor-Video relationship.
  - Activate EL-031 with two or more Actor Candidates selected -> the product associates them with one Actor Identity.
  - Actor Candidates with matching Activity Names -> the product does not associate them with one Actor Identity without EL-031 activation.
  - Enter a Naver Person Information URL in EL-015 -> the Operator can open that URL and manually enter researched information in EL-012.
  - Activate EL-016 -> the product retains the Actor Candidate fields and supplied Candidate Sources.
  - Save or extract candidate data -> all candidate data fields may remain empty.
  - Extracted Actor Candidate information -> the information remains candidate data until the Operator reviews it.

### SCR-004 Filmography Candidate Review

- ID: SCR-004
- Purpose: the Operator reviews Filmography Candidates and records an approval or rejection decision.
- Elements:
  - EL-019 Candidate List: lists Pending Filmography Candidates. User actions: select a candidate.
  - EL-020 Candidate Details: shows the candidate's Actor, Video, Channel, published date, source, extracted fields, and review fields. User actions: none.
  - EL-021 Candidate Field Editor: edits candidate fields. User actions: edit field values.
  - EL-022 Candidate Source Links: lists Candidate Source URLs. User actions: open a source URL.
  - EL-023 Approve Control: approves the selected Filmography Candidate. User actions: activate.
  - EL-024 Reject Control: rejects the selected Filmography Candidate. User actions: activate.
  - EL-025 Review Note Input: holds an optional review note. User actions: enter, edit, or clear a note.
  - EL-026 Review Result Area: shows the result of an approval or rejection. User actions: none.
  - EL-027 Channel Catalog Link: opens SCR-002. User actions: select.
  - EL-028 Actor Candidate Sources Link: opens SCR-003. User actions: select.
- Arrangement: EL-019 appears first. Selecting a candidate shows EL-020, EL-021, and EL-022. EL-025 appears before EL-023 and EL-024. EL-026 follows the decision controls. EL-027 and EL-028 are navigation controls.
- States:
  - Empty: EL-019 shows no Pending Filmography Candidates.
  - Populated: EL-019 lists Pending Filmography Candidates.
  - Loading: the selected candidate's details show a loading indicator.
  - Error: the affected list or detail area shows an error state.
  - Reviewed: EL-026 shows the review result and the candidate no longer appears in the Pending list.
- Navigation:
  - Select EL-027 -> SCR-002.
  - Select EL-028 -> SCR-003.
  - Open a URL in EL-022 -> the URL opens in a new browser tab.
- Interactions:
  - Select a candidate in EL-019 -> EL-020 through EL-025 show its candidate data and review controls.
  - Activate EL-023 -> Review Status becomes Approved and the candidate is no longer Pending.
  - Activate EL-024 -> Review Status becomes Rejected and the candidate is no longer Pending.
  - Approve a candidate -> the resulting Approved Entry is the only kind of Filmography Entry eligible for public display.
  - Candidate fields with no supplied value -> remain empty and do not prevent saving, extraction, or review.

## Functional Requirements

Channel registration and video collection

- FR-001: SCR-001 MUST accept an Excel file with extension `.xlsx`, `.xls`, or `.csv` containing a `channel` column.
- FR-002: The `channel` column MUST accept a Channel ID, an `@handle`, or a URL with a path beginning `/channel/`, `/@`, or `/user/`.
- FR-003: Importing a spreadsheet MUST report a result for each processed row.
- FR-004: Importing a Channel that is already registered or appears more than once in a spreadsheet MUST upsert its Channel record and MUST retain its existing Videos and synchronization state.
- FR-005: The product MUST collect available Videos for registered Channels without requiring another spreadsheet import.
- FR-006: The product MUST collect every page of available uploads returned for each registered Channel.
- FR-007: The product MUST retain collected Video records across Operator sessions.
- FR-008: The product MUST record each Video's ID, title, description, published date, duration, thumbnail URL, available statistics, and availability state when those values are returned.
- FR-009: The product MUST associate each collected Video with its Channel.
- FR-010: A Video no longer returned as available MUST remain recorded and MUST be marked unavailable.
- FR-011: A Video returned as available after being marked unavailable MUST have its unavailable state cleared.
- FR-012: A Channel collection failure MUST be recorded and shown to the Operator.
- FR-013: When YouTube quota is exhausted, the product MUST show that collection is paused due to quota exhaustion.

Actor and filmography candidate collection

- FR-014: The product MUST run Actor Candidate and Filmography Candidate extraction as a process separate from Channel Video collection.
- FR-015: The product MUST use Gemini to extract Actor Candidates and Filmography Candidates from collected Videos.
- FR-016: Gemini-extracted Filmography Candidates MUST have Review Status Pending.
- FR-017: The product MUST allow an Operator to associate a NamuWiki URL with an Actor Candidate.
- FR-018: The product MUST extract Actor Candidate information from the supplied NamuWiki URL and present it as suggestions for the selected Actor Candidate.
- FR-019: The product MUST allow an Operator to associate a Naver Person Information URL with candidate data.
- FR-020: Naver person information MUST be entered by the Operator as manual candidate data.
- FR-021: The product MUST NOT automatically collect or process Naver search results.
- FR-022: The product MUST retain the source URL associated with extracted or manually entered candidate data.
- FR-023: The product MUST allow the Operator to edit Actor Candidate and Filmography Candidate fields.
- FR-024: The product MUST allow all proposed candidate and evidence fields to have no value.
- FR-025: Candidate fields with no value MUST NOT prevent saving candidate data.

Candidate review

- FR-026: The product MUST provide the Operator with a list of Pending Filmography Candidates.
- FR-027: The product MUST show the selected candidate's recorded data and Candidate Sources.
- FR-028: The Operator MUST be able to approve a Pending Filmography Candidate.
- FR-029: The Operator MUST be able to reject a Pending Filmography Candidate.
- FR-030: Approving a Filmography Candidate MUST set its Review Status to Approved.
- FR-031: Rejecting a Filmography Candidate MUST set its Review Status to Rejected.
- FR-032: Only an Approved Entry MUST be eligible for public display.
- FR-033: A Pending or Rejected Filmography Candidate MUST NOT be eligible for public display.
- FR-034: The product MUST retain the Operator's review decision and review time.
- FR-035: The product MUST retain an optional review note.
- FR-036: NamuWiki content MUST NOT create or update a Filmography Candidate or an Actor-Video relationship.
- FR-037: The Operator MUST manually start Gemini Candidate Extraction for a selected Video.
- FR-038: Registering or collecting a Video MUST NOT automatically start Gemini Candidate Extraction.
- FR-039: The Operator MUST be able to associate two or more Actor Candidates with one Actor Identity after confirming they identify the same person.
- FR-040: The product MUST NOT associate Actor Candidates with one Actor Identity based only on matching names.
- FR-041: Extracting Actor Candidate information from a NamuWiki URL MUST NOT overwrite existing Actor Candidate values.
- FR-042: Each Gemini Candidate Extraction run MUST create new candidate records and MUST NOT automatically merge with or replace candidates from another run.
- FR-043: Videos successfully collected before a later collection failure MUST remain recorded.
- FR-044: A review decision MUST be final: an Approved Filmography Candidate MUST NOT become Pending or Rejected, and a Rejected Filmography Candidate MUST NOT become Pending or Approved.
- FR-045: EL-007 MUST list the Videos of the selected Channel in descending order of published date, 50 Videos per page.
- FR-046: EL-019 MUST list Pending Filmography Candidates in descending order of the Video published date, 50 candidates per page.

## User Flows

UF-001 Register Channels and collect Videos

- Select a YouTube Channel Spreadsheet in EL-001 -> EL-002 is enabled.
- Activate EL-002 -> the product processes the Channel rows and shows row results in EL-003.
- Open SCR-002 -> registered Channels are listed in EL-005.
- Select a Channel in EL-006 -> collected Videos for that Channel are shown in EL-007.
- Continue registering Channels with another spreadsheet -> the product retains existing Channel and Video records and processes the new rows.

UF-002 Extract Actor Candidates from Videos

- Select a collected Video in SCR-002 -> EL-029 is available.
- Activate EL-029 -> Gemini Candidate Extraction starts for the selected Video.
- Extraction completes -> Actor Candidates and Pending Filmography Candidates are available.
- Open SCR-003 -> Actor Candidates are listed in EL-011.
- Select an Actor Candidate -> its fields and source controls are shown.

UF-003 Update Actor Candidate information from NamuWiki

- Select an Actor Candidate in EL-011 -> SCR-003 shows the candidate.
- Enter a NamuWiki URL in EL-013 -> EL-014 is enabled.
- Activate EL-014 -> the product extracts Actor Candidate information and shows suggestions in EL-017 without overwriting EL-012.
- Review suggestions in EL-017 -> the Operator may edit EL-012 and save selected values with EL-016.

UF-004 Use Naver person information as a manual source

- Select an Actor Candidate in EL-011 -> SCR-003 shows the candidate.
- Enter a Naver Person Information URL in EL-015 -> the Operator can open the source page.
- Manually enter researched information in EL-012 -> candidate values are shown for editing.
- Activate EL-016 -> the product retains the candidate values and source URL.

UF-005 Review a Filmography Candidate

- Open SCR-004 -> Pending Filmography Candidates are listed in EL-019.
- Select a candidate -> candidate data and source URLs are shown.
- Edit candidate fields or enter a review note -> the values are shown in EL-021 or EL-025.
- Activate EL-023 -> Review Status becomes Approved.
- Activate EL-024 -> Review Status becomes Rejected.

UF-006 Confirm Actor Identity

- Select two or more Actor Candidates in EL-011 -> EL-031 is enabled.
- Confirm that the selected Actor Candidates identify the same person -> activate EL-031.
- Activate EL-031 -> the product associates the selected Actor Candidates with one Actor Identity.

## Error Conditions

- ERR-001: A spreadsheet row contains an unsupported Channel value -> EL-003 identifies the row and reports that it was not imported.
- ERR-002: Spreadsheet processing fails -> SCR-001 shows the failure and preserves row-level results available at the time of failure.
- ERR-003: YouTube collection fails for a Channel -> SCR-002 shows the Channel collection failure and retains previously collected Videos and Videos successfully collected before the failure.
- ERR-004: YouTube quota is exhausted -> SCR-002 shows that collection is paused due to quota exhaustion.
- ERR-005: Gemini video extraction fails -> SCR-002 shows the failure and retains the source Video and existing candidate data.
- ERR-006: NamuWiki content cannot be retrieved or processed -> EL-017 shows the extraction failure and retains the supplied NamuWiki URL and existing Actor Candidate information.
- ERR-007: Saving candidate data fails -> SCR-003 or SCR-004 shows the failure and retains the Operator's entered values.
- ERR-008: Saving a review decision fails -> EL-026 shows the failure and the candidate remains Pending.

## Non-Goals

- Public Viewer search, Actor detail, and Video detail screens are deferred.
- Correction Requests are excluded from the current product.
- Opt-Out Requests are excluded from the current product.
- Automatic approval of Actor Candidates or Filmography Candidates.
- Automatic association of Actor Candidates based only on matching names.
- Automatic collection or processing of Naver search results or Naver person information.
- Displaying Pending or Rejected Filmography Candidates to Viewers.
- A screen that lists or edits Actor Identities.
- A screen that lists Approved Entries outside the Pending review list.

## Acceptance Criteria

- AC-001: Importing a spreadsheet with supported Channel IDs, handles, and URLs registers those Channels and reports a result for every row (FR-001, FR-002, FR-003, SCR-001).
- AC-002: Importing an already registered Channel retains its collected Videos and synchronization state (FR-004, SCR-001).
- AC-003: A registered Channel's available uploads are collected across every returned page without another spreadsheet import (FR-005, FR-006, SCR-002).
- AC-004: Collected Videos are associated with their Channel and their returned metadata is retained across Operator sessions (FR-007, FR-008, FR-009, SCR-002).
- AC-005: A Video that is no longer returned as available remains recorded as unavailable, and a later available response clears that state (FR-010, FR-011, SCR-002).
- AC-006: A YouTube collection failure and quota exhaustion are shown to the Operator (FR-012, FR-013, SCR-002).
- AC-007: The Operator starts video-based extraction separately from Channel Video collection, and the extraction creates Actor Candidates and Pending Filmography Candidates (FR-014, FR-015, FR-016, FR-037, FR-038, SCR-002).
- AC-008: Supplying a NamuWiki URL shows Actor Candidate information suggestions without overwriting existing values or creating or updating a Filmography Candidate or Actor-Video relationship (FR-017, FR-018, FR-036, FR-041, SCR-003).
- AC-009: A Naver Person Information URL and manually entered candidate data are retained without automatic Naver result collection (FR-019, FR-020, FR-021, FR-022, SCR-003).
- AC-010: Every proposed candidate and evidence field can be empty while candidate data is saved (FR-023, FR-024, FR-025, SCR-003, SCR-004).
- AC-011: The Operator can view, edit, approve, and reject a Pending Filmography Candidate (FR-026, FR-027, FR-028, FR-029, SCR-004).
- AC-012: Approving a candidate sets Review Status to Approved and makes it eligible for public display; rejecting a candidate sets Review Status to Rejected and keeps it ineligible for public display (FR-030, FR-031, FR-032, FR-033, SCR-004).
- AC-013: Review decisions, review times, and optional review notes are retained (FR-034, FR-035, SCR-004).
- AC-014: Importing duplicate Channels upserts the existing Channel record and retains its Videos and synchronization state (FR-004, SCR-001).
- AC-015: Registering or collecting a Video does not start Gemini Candidate Extraction; activating EL-029 starts extraction for the selected Video (FR-037, FR-038, SCR-002).
- AC-016: The Operator can associate selected Actor Candidates with one Actor Identity, and matching names alone never cause the association (FR-039, FR-040, SCR-003).
- AC-017: A new Gemini Candidate Extraction run creates separate candidates and does not merge with or replace candidates from earlier runs (FR-042, SCR-002).
- AC-018: Videos successfully collected before a later Channel collection failure remain recorded (FR-043, ERR-003, SCR-002).
