# PRODUCT_SPEC.md

## Purpose

- The product enables an Operator to build a catalog of Actors who appear in Korean YouTube web-drama and sketch-comedy Videos.
- The product enables an Operator to register YouTube Channels, collect their Videos, extract Actors from selected Videos, store Actor profile information, and see which Videos each Actor appears in.
- The product retains Channel, Video, Actor, and appearance records across Operator sessions.
- The product lets Visitors browse the public catalog without signing in.
- The product lets Contributors add and edit Channels, Videos, and Actors after self-registration.

Terms:

- Visitor: an unauthenticated person who views the public landing and catalog.
- Contributor: a self-registered, non-administrator User who submits Contributions for Channels, Videos, and Actors.
- Operator: an administrator who uses the product to collect and maintain the catalog.
- Channel: a YouTube channel registered by an Operator.
- Video: a YouTube video published by a Channel.
- Actor: a person who appears in one or more Videos. An Actor has a Name, an optional Activity Name, Birth Date, Birthplace, Education, Debut, Agency, Nationality, zero or more Actor Reference URLs, and optional Profile Fields for other information.
- Profile Field: an optional named Actor value not covered by the fixed Actor fields, such as an Instagram handle or a YouTube channel. Profile Fields may be added or removed as the Operator collects data.
- Actor Reference URL: a NamuWiki, Naver, or other reference URL supplied by an Operator for an Actor, stored as a separate actor_reference_urls record. An Actor may have none or many.
- Actor Video Appearance: the association between one Actor and one Video.
- Gemini Actor Extraction: an Operator-started operation that uses a Video to extract Actors and associate them with that Video.
- Owner: the User who owns a catalog record. Catalog records are owned by an Administrator for now; Actor and agency ownership is deferred.
- Contribution: a Contributor's pending proposal to create or change a Channel, Video, Actor, or Actor-Video association. A Contribution has a JSON payload, review status, reviewer, review time, and review note.
- Public Record: a Channel, Video, or Actor that has been approved by an Administrator and has `is_public = true`.
- Draft Record: a Contributor-created Channel, Video, or Actor that has not yet been approved and is hidden from the public catalog.
- YouTube Channel Spreadsheet: an Excel file with a `channel` column containing YouTube channel identifiers, handles, or supported channel URLs.

## Problem

- YouTube videos do not provide structured cast credits for the product's target videos.
- The product needs a simple way for an Operator to accumulate Videos, extract Actors, and see which Videos each Actor appears in without reviewing a second layer of filmography records.
- Catalog maintenance is limited to administrators, so people who watch these Videos cannot contribute Channel, Video, or Actor information.
- The catalog is not presented to the public.

## Product Goals

- Enable a Visitor to browse the public catalog without signing in.
- Enable a Contributor to self-register and add or edit Channels, Videos, and Actors.
- Present the product as a public service from the first screen.
- Enable an Operator to register YouTube Channels from a YouTube Channel Spreadsheet.
- Enable an Operator to see registered Channels and their collected Videos.
- Enable an Operator to collect available Videos published by registered Channels.
- Enable an Operator to extract Actors from a collected Video with Gemini.
- Enable an Operator to store and edit Actor profile information and their reference URLs.
- Enable an Operator to see, add, and remove the Videos an Actor appears in.
- Enable an Operator to merge duplicate Actors.
- Keep the Operator workflow simple: no separate candidate, identity, filmography, source, or review records are exposed.

## Users

- Visitor: an unauthenticated person who can view the public landing and catalog.
- Contributor: a self-registered, non-administrator User who can add Channels, Videos, and Actors and edit any Channel, Video, or Actor record. A Contributor cannot delete records, merge Actors, run Gemini Actor Extraction, manage Sync Errors, or manage Users.
- Administrator: a User with administrator access. Only Administrators may access the Operator interface. An Administrator can be created or promoted with the administrator provisioning command.
- Operator: an Administrator who registers Channels, reviews collection results, edits Actors, links Videos, and merges duplicate Actors.
- Self-registration creates a Contributor. Email verification is deferred and is not required.
- The current product has no Requester interface.

## Inputs

- Owner: the User who owns a catalog record. Catalog records are owned by an Administrator for now; Actor and agency ownership is deferred.
- Contribution: a Contributor's pending proposal to create or change a Channel, Video, Actor, or Actor-Video association. A Contribution has a JSON payload, review status, reviewer, review time, and review note.
- Public Record: a Channel, Video, or Actor that has been approved by an Administrator and has `is_public = true`.
- Draft Record: a Contributor-created Channel, Video, or Actor that has not yet been approved and is hidden from the public catalog.
- YouTube Channel Spreadsheet: an Excel file with extension `.xlsx`, `.xls`, or `.csv` and a `channel` column. Each value is a Channel ID, an `@handle`, or a URL whose path begins with `/channel/`, `/@`, or `/user/`.
- Video source: a Video collected from a registered Channel.
- Actor values: Name, Activity Name, Birth Date, Birthplace, Education, Debut, Agency, Nationality, Actor Reference URLs, and additional Profile Field name/value pairs.
- Actor Reference URLs: zero or more NamuWiki, Naver, or other reference URLs supplied by an Operator or Contributor.
- Channel reference: a Channel ID, an `@handle`, or a supported Channel URL entered by a Contributor.
- Video reference: a YouTube video URL entered by a Contributor.

## Outputs

- Channel import results for each spreadsheet row.
- Registered Channel records.
- Video records associated with registered Channels.
- Actor records extracted from Videos or edited by an Operator.
- Actor Video Appearance records linking Actors to Videos.
- Actor merge results.
- Contributor-created and Contributor-edited Channel, Video, and Actor records.

## Constraints

- Operator-facing interface language: None stated.
- Supported browsers: None stated.
- Supported window widths: None stated.
- Performance: None stated.
- Accessibility: None stated.
- Offline behavior: None stated.
- Data retention: Channel, Video, Actor, and appearance records persist across Operator, Contributor, and Visitor sessions.

## External Systems

- YouTube Data API: the product obtains Channel information and available Video information for registered Channels. The product also resolves Channel and Video references entered by a Contributor.
- Gemini: the product submits collected Video information and obtains Actors appearing in that Video.
- NamuWiki and Naver person information: the Operator supplies Actor Reference URLs. The product stores and opens each URL as a reference. The product does not automatically retrieve or process NamuWiki or Naver content.

## User Interface

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
  - EL-007 Video List: lists Videos associated with the selected Channel. User actions: select a Video.
  - EL-008 Video Item: shows a Video title, published date, availability state, and YouTube video URL. User actions: select.
  - EL-009 Import Channels Link: opens SCR-001. User actions: select.
  - EL-010 Actor Catalog Link: opens SCR-003. User actions: select.
  - EL-029 Actor Extraction Control: starts Gemini Actor Extraction for the selected Video. User actions: activate.
  - EL-030 Extraction Status Display: shows the selected Video's extraction state. User actions: none.
  - EL-031 Channel Edit Control: edits the selected Channel's package fields. User actions: activate.
  - EL-032 Channel Delete Control: deletes the selected Channel after confirmation. User actions: activate.
  - EL-033 Video and Sync Error Catalog Link: opens SCR-004. User actions: select.
  - EL-034 Channel Create Control: creates a Channel from entered package fields. User actions: activate.
  - EL-039 Channel Add Video Control: creates a Video under the selected Channel. User actions: activate.
- Arrangement: EL-005 and EL-006 form the Channel group. EL-007 and EL-008 appear below the selected Channel. EL-029 and EL-030 appear with the selected Video. EL-031 and EL-032 appear with the selected Channel. EL-009, EL-010, and EL-033 are navigation controls. EL-034 appears with the Channel list. EL-039 appears with the selected Channel's Video list.
- States:
  - Empty: EL-005 shows an empty list. EL-007 is not shown.
  - Populated: EL-005 and EL-006 are shown. Selecting a Channel with Videos shows EL-007 and EL-008.
  - Loading: the list being loaded shows a loading indicator.
  - Error: the affected list shows an error state.
  - Extraction in progress: EL-030 shows that Gemini Actor Extraction is in progress for the selected Video.
  - Extraction completed: EL-030 shows that Gemini Actor Extraction completed for the selected Video.
  - Extraction failed: EL-030 shows that Gemini Actor Extraction failed for the selected Video.
- Navigation:
  - Select EL-009 -> SCR-001.
  - Select EL-010 -> SCR-003.
  - Select EL-033 -> SCR-004.
- Interactions:
  - Select EL-006 -> EL-007 shows the Videos collected for that Channel.
  - Activate EL-034 -> the Operator creates a Channel and its initial Video collection starts.
  - Activate EL-039 -> the Operator creates a Video under the selected Channel.
  - Activate EL-031 -> the Operator edits and saves the selected Channel's package fields.
  - Activate EL-032 -> the Operator confirms and deletes the selected Channel; its Videos and Actor-Video associations are deleted with it.
  - A newly registered Channel -> the product collects its available Videos without another spreadsheet import.
  - A Video that is no longer returned as available by YouTube -> the product marks the Video unavailable and retains its record.
  - A Video that becomes available again -> the product clears its unavailable state.
  - Activate EL-029 -> the product starts Gemini Actor Extraction for the selected Video and updates EL-030.
  - Register or collect a Video -> Gemini Actor Extraction does not start automatically.
  - A successful extraction -> each extracted Actor is associated with the selected Video.
  - A Draft Record does not appear in SCR-005 or SCR-006 until an Administrator approves it.

### SCR-003 Actor Catalog

- ID: SCR-003
- Purpose: the Operator inspects and edits Actors and the Videos they appear in.
- Elements:
  - EL-011 Actor List: lists Actors. User actions: select one or more Actors.
  - EL-012 Actor Fields: shows editable Name, Activity Name, Birth Date, Birthplace, Education, Debut, Agency, Nationality, and Profile Field name/value rows. User actions: edit field values, add or remove Profile Fields.
  - EL-013 Actor Reference URL List: lists the Actor's reference URLs. User actions: add, edit, or remove a URL.
  - EL-014 Save Actor Control: saves Actor fields, Actor Reference URLs, and Profile Fields. User actions: activate.
  - EL-015 Actor Video List: lists the Videos associated with the selected Actor. User actions: none.
  - EL-016 Actor Video Item: shows a Video title, Channel name, published date, and YouTube video URL. User actions: select.
  - EL-017 Add Actor Video Control: links the selected Actor to a collected Video. User actions: activate.
  - EL-018 Remove Actor Video Control: removes the link between the selected Actor and a Video. User actions: activate.
  - EL-019 Merge Actors Control: merges two or more selected Actors into one Actor. User actions: activate.
  - EL-020 Channel Catalog Link: opens SCR-002. User actions: select.
  - EL-037 Actor Create Control: creates an Actor from entered fields. User actions: activate.
  - EL-038 Actor Delete Control: deletes the selected Actor after confirmation. User actions: activate.
- Arrangement: EL-011 appears first. EL-012 through EL-014 follow the selected Actor. EL-015 and EL-016 follow EL-014. EL-017 and EL-018 appear with EL-015. EL-019, EL-037, and EL-038 appear with EL-011. EL-020 is a navigation control.
- States:
  - Empty: EL-011 shows no Actors. EL-012 through EL-018 are not shown.
  - Populated: selecting one Actor shows EL-012 through EL-018. Selecting two or more Actors enables EL-019.
  - Saving: EL-014 is disabled and the save result is shown.
  - Error: the affected area shows the failure while entered values are retained.
- Navigation:
  - Select EL-020 -> SCR-002.
  - Select an Actor Reference URL -> the URL opens in a new browser tab.
- Interactions:
  - Select an Actor in EL-011 -> EL-012 through EL-018 show that Actor's fields and Videos.
  - Activate EL-014 -> the product saves the Actor fields and Profile Fields.
  - Activate EL-017 -> the product links the Actor to the selected Video.
  - Activate EL-018 -> the product removes the link between the Actor and the Video.
  - Activate EL-037 -> the Operator creates an Actor.
  - Activate EL-038 -> after confirmation, the Actor and its Actor-Video associations are deleted.
  - Activate EL-019 with two or more Actors selected -> the product merges them into one Actor.
  - Actors with matching names -> the product does not merge them without EL-019 activation.
  - All Actor fields except Name may be empty and the Actor can still be saved.
  - A saved Actor Reference URL is retained and can be opened again.

### SCR-004 Video and Sync Error Catalog

- ID: SCR-004
- Purpose: the Operator inspects Videos and Sync Errors, corrects and deletes Videos, and deletes Sync Errors.
- Elements:
  - EL-021 Video Catalog List: lists Videos across registered Channels. User actions: select a Video.
  - EL-022 Video Catalog Item: shows a Video title, Channel, published date, availability state, and extraction state. User actions: select.
  - EL-023 Video Edit Control: edits the selected Video's package fields and saves them. User actions: activate.
  - EL-024 Video Delete Control: deletes the selected Video after confirmation. User actions: activate.
  - EL-025 Sync Error List: lists Sync Errors. User actions: select a Sync Error.
  - EL-026 Sync Error Item: shows a Channel, reason, HTTP code, message, and time. User actions: select.
  - EL-028 Sync Error Delete Control: deletes the selected Sync Error after confirmation. User actions: activate.
  - EL-035 Video Create Control: creates a Video from entered package fields. User actions: activate.
  - EL-040 Video Add Actor Control: associates an existing Actor with the selected Video. User actions: activate.
  - EL-041 Video Remove Actor Control: removes the association between the selected Video and an Actor. User actions: activate.
- Arrangement: EL-021 and EL-022 form the Video group. EL-023 and EL-024 appear with the selected Video. EL-025 and EL-026 form the Sync Error group. EL-028 appears with the selected Sync Error. EL-035 appears with the Video list. EL-040 and EL-041 appear with the selected Video's Actor list.
- States:
  - Empty: EL-021 or EL-025 shows an empty list.
  - Populated: EL-021 and EL-022 are shown; selecting a Video shows EL-023 and EL-024. EL-025 and EL-026 are shown; selecting a Sync Error shows EL-028.
  - Saving: the active edit control is disabled and the save result is shown.
  - Deleting: the delete control is disabled and the delete result is shown.
  - Error: the affected area shows the failure while entered values are retained.
- Navigation:
  - SCR-002 <-> SCR-004 through EL-033.
  - A Video's Channel name opens SCR-002 for that Channel.
  - A Sync Error's Channel name opens SCR-002 for that Channel.
- Interactions:
  - Activate EL-035 -> the Operator creates a Video.
  - Activate EL-040 -> the Operator associates an existing Actor with the selected Video.
  - Activate EL-041 -> the Operator removes an Actor from the selected Video.
  - Activate EL-023 -> the Operator edits the Video's package fields and saves them.
  - Activate EL-024 -> after confirmation, the Video is deleted and its Actor-Video associations are deleted with it.
  - Activate EL-028 -> after confirmation, the Sync Error is deleted. Sync Errors are read-only otherwise.
  - Video extraction state, extraction error, and extraction time are read-only in SCR-004.
  - A later YouTube collection may overwrite edited package fields.
  - Deleting a Channel in SCR-002 deletes its Videos and their Actor-Video associations.

### SCR-005 Service Landing

- ID: SCR-005
- Purpose: presents the service to a Visitor and routes the Visitor to the public catalog or account access.
- Elements:
  - EL-042 Service Introduction: describes the catalog service. User actions: none.
  - EL-043 Featured Actor List: shows recently added Actors. User actions: select an Actor.
  - EL-044 Featured Video List: shows recently collected Videos. User actions: select a Video.
  - EL-045 Sign In Link: opens SCR-007. User actions: select.
  - EL-046 Sign Up Link: opens SCR-007. User actions: select.
  - EL-047 Contributor Workspace Link: opens SCR-008. User actions: select.
- Arrangement: EL-042 appears first. EL-043 and EL-044 follow EL-042. EL-045 and EL-046 appear with EL-042. EL-047 appears only while a User is signed in.
- States:
  - Visitor: EL-042 through EL-046 are shown. EL-047 is not shown.
  - Contributor signed in: EL-047 is shown.
  - Empty: EL-043 or EL-044 shows an empty state when no matching record exists.
  - Error: the affected list shows an error state.
- Navigation:
  - Select EL-043 or EL-044 -> SCR-006.
  - Select EL-045 or EL-046 -> SCR-007.
  - Select EL-047 -> SCR-008.
- Interactions:
  - Select EL-045 -> SCR-007 shows the sign-in form.
  - Select EL-046 -> SCR-007 shows the sign-up form.

### SCR-006 Public Catalog

- ID: SCR-006
- Purpose: lets a Visitor browse public Channels, Videos, and Actors without signing in.
- Elements:
  - EL-048 Public Channel List: lists Channels. User actions: select a Channel.
  - EL-049 Public Channel Item: shows a Channel name, handle when recorded, and collected Video count. User actions: select.
  - EL-050 Public Channel Detail: shows the selected Channel and its Videos. User actions: select a Video.
  - EL-051 Public Video List: lists Videos. User actions: select a Video.
  - EL-052 Public Video Item: shows a Video title, Channel name, published date, and YouTube video URL. User actions: select.
  - EL-053 Public Video Detail: shows the selected Video's title, Channel, published date, YouTube video URL, and associated Actors. User actions: select an Actor.
  - EL-054 Public Actor List: lists Actors. User actions: select an Actor.
  - EL-055 Public Actor Item: shows an Actor's Name and Activity Name. User actions: select.
  - EL-056 Public Actor Detail: shows the selected Actor's Name, Activity Name, Birth Date, Birthplace, Education, Debut, Agency, Nationality, Actor Reference URLs, and associated Videos. User actions: select a Video or open a reference URL.
  - EL-057 Sign In Link: opens SCR-007. User actions: select.
  - EL-058 Contributor Workspace Link: opens SCR-008. User actions: select.
- Arrangement: EL-048, EL-051, and EL-054 form the Channel, Video, and Actor groups. EL-050 appears with the selected Channel. EL-053 appears with the selected Video. EL-056 appears with the selected Actor. EL-057 appears for a Visitor. EL-058 appears while a User is signed in.
- States:
  - Empty: the affected list shows an empty state.
  - Populated: the affected list shows its items and the selected detail.
  - Loading: the affected area shows a loading indicator.
  - Error: the affected area shows an error state.
- Navigation:
  - Select EL-049 -> EL-050 shows the Channel and its Videos.
  - Select EL-052 or a Video in EL-050 -> EL-053 shows the Video and its Actors.
  - Select EL-055 or an Actor in EL-053 -> EL-056 shows the Actor and their Videos.
  - Select EL-057 -> SCR-007.
  - Select EL-058 -> SCR-008.
  - An Actor Reference URL opens in a new browser tab.
- Interactions:
  - A merged Actor does not appear in EL-054.
  - Sync Errors do not appear in SCR-006.
  - Administrator controls do not appear in SCR-006.
  - Package data may be read in SCR-006 but cannot be edited there.

### SCR-007 Account Access

- ID: SCR-007
- Purpose: lets a Visitor create a Contributor account, sign in, or reset a password.
- Elements:
  - EL-059 Sign Up Form: accepts a name, an email address, and a password. User actions: edit values.
  - EL-060 Sign Up Control: creates the account. User actions: activate.
  - EL-061 Sign In Form: accepts an email address and a password. User actions: edit values.
  - EL-062 Sign In Control: signs in. User actions: activate.
  - EL-063 Password Reset Form: accepts an email address. User actions: edit values.
  - EL-064 Password Reset Control: requests a password reset. User actions: activate.
  - EL-065 Catalog Link: opens SCR-006. User actions: select.
- Arrangement: EL-059 and EL-060 form the sign-up group. EL-061 and EL-062 form the sign-in group. EL-063 and EL-064 form the password reset group. EL-065 is a navigation control.
- States:
  - Initial: EL-059, EL-060, EL-061, EL-062, EL-063, EL-064, and EL-065 are shown.
  - Submitting: the activated control is disabled and the result is shown.
  - Error: the affected group shows the failure while entered values are retained.
  - Signed in: EL-059 through EL-064 are not shown; EL-065 and a Contributor Workspace link are shown.
- Navigation:
  - Successful sign-up or sign-in -> SCR-005.
  - Select EL-065 -> SCR-006.
- Interactions:
  - Activate EL-060 -> the product creates a non-administrator User and signs the User in.
  - Activate EL-062 -> the product signs the User in.
  - Activate EL-064 -> the product requests a password reset and shows the result.
  - Email verification is not required after sign-up.
  - A signed-in Contributor cannot access the Filament panel.

### SCR-008 Contributor Workspace

- ID: SCR-008
- Purpose: lets a Contributor add Channels, Videos, and Actors and edit any Channel, Video, or Actor record.
- Elements:
  - EL-066 Channel Reference Input: accepts a Channel ID, an `@handle`, or a supported Channel URL. User actions: edit the value.
  - EL-067 Add Channel Control: adds the referenced Channel. User actions: activate.
  - EL-068 Video URL Input: accepts a YouTube video URL. User actions: edit the value.
  - EL-069 Add Video Control: adds the referenced Video. User actions: activate.
  - EL-070 Channel Edit Control: edits and saves a Channel's package fields. User actions: activate.
  - EL-071 Video Edit Control: edits and saves a Video's package fields. User actions: activate.
  - EL-072 Actor Form: accepts a Name and optional Activity Name, Birth Date, Birthplace, Education, Debut, Agency, Nationality, and Profile Field name/value rows. User actions: edit values, add or remove Profile Fields.
  - EL-073 Actor Reference URL List: lists the Actor's reference URLs. User actions: add, edit, or remove a URL.
  - EL-074 Add Actor Control: creates an Actor. User actions: activate.
  - EL-075 Actor Edit Control: edits and saves an Actor. User actions: activate.
  - EL-076 Actor Video Association Control: adds or removes the association between an Actor and a Video. User actions: activate.
  - EL-077 Operation Result Display: shows add, edit, and association results. User actions: none.
  - EL-078 Public Catalog Link: opens SCR-006. User actions: select.
- Arrangement: EL-066 through EL-069 form the add records group. EL-070 and EL-071 appear with a selected Channel or Video. EL-072 through EL-075 form the Actor group. EL-076 appears with a selected Actor and Video. EL-077 follows the active operation. EL-078 is a navigation control.
- States:
  - Initial: the add controls are shown. Record edit controls appear after a record is selected.
  - Submitting: the activated control is disabled and EL-077 shows progress.
  - Completed: EL-077 shows the result and the affected record is updated.
  - Error: EL-077 shows the failure while entered values are retained.
- Navigation:
  - Select EL-078 -> SCR-006.
- Interactions:
  - Activate EL-067 -> the product resolves the Channel and creates a pending Contribution plus a Draft Channel. Initial collection starts only after approval.
  - Activate EL-069 -> the product resolves the video URL and creates a pending Contribution plus a Draft Video. Gemini Actor Extraction does not start.
  - Activate EL-074 -> the product creates a pending Contribution plus a Draft Actor.
  - Activate EL-075 -> the product creates a pending Contribution for the Actor; the canonical Actor is unchanged until approval.
  - Activate EL-070 or EL-071 -> the product creates a pending Contribution for the Channel or Video; the canonical record is unchanged until approval.
  - Activate EL-076 -> the product creates a pending Contribution for the Actor-Video association.
  - A second pending Contribution for the same Contributor and record shows a conflict instead of replacing the existing pending Contribution.
  - The Contributor can edit or delete their own pending Contribution.
  - A Contributor cannot delete a canonical record, merge Actors, start Gemini Actor Extraction, or manage Sync Errors in SCR-008.
  - All Actor fields except Name may be empty in a Contribution.

### SCR-009 Contribution Review

- ID: SCR-009
- Purpose: lets an Administrator review pending Contributions and approve or reject them.
- Elements:
  - EL-079 Pending Contribution List: lists pending Contributions. User actions: select a Contribution.
  - EL-080 Contribution Detail: shows the Contributor, affected record, JSON payload, and status. User actions: none.
  - EL-081 Approve Contribution Control: approves the Contribution and applies the payload. User actions: activate.
  - EL-082 Reject Contribution Control: rejects the Contribution. User actions: activate.
  - EL-083 Review Note Input: records an optional note. User actions: edit.
  - EL-084 Operation Result Display: shows the approval or rejection result. User actions: none.
- Arrangement: EL-079 and EL-080 form the review list and detail. EL-081 through EL-084 appear with the selected Contribution.
- States:
  - Empty: EL-079 shows an empty list.
  - Populated: EL-079 and EL-080 are shown.
  - Saving: the active decision control is disabled and EL-084 shows progress.
  - Error: EL-084 shows the failure while the selected Contribution remains pending.
- Navigation:
  - Select EL-079 -> EL-080 shows the Contribution.
- Interactions:
  - Approve -> the payload is applied to the canonical record, the record becomes public, and the Contribution is marked approved.
  - Reject -> the canonical record is unchanged and the Contribution is marked rejected.
  - Approving a Channel starts its initial Video collection.
  - Approving a Video never starts Gemini Actor Extraction.
  - The review page is an Administrator-only Filament page, not a resource.

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

Actor extraction and catalog

- FR-014: The product MUST run Gemini Actor Extraction as a process separate from Channel Video collection.
- FR-015: The product MUST use Gemini to extract Actors from a collected Video.
- FR-016: Gemini Actor Extraction MUST start only from an explicit Operator command for a selected Video. Registering or collecting a Video MUST NOT start extraction.
- FR-017: A successful extraction MUST associate each extracted Actor with the selected Video. A repeated extraction MUST NOT duplicate an existing Actor-Video association.
- FR-018: An Actor MUST have a Name. Activity Name, Birth Date, Birthplace, Education, Debut, Agency, Nationality, Actor Reference URLs, and Profile Fields MAY be empty.
- FR-019: The Operator MUST be able to edit and save Actor Name, Activity Name, Birth Date, Birthplace, Education, Debut, Agency, Nationality, Actor Reference URLs, and Profile Fields. Birth Date, Birthplace, Education, Debut, Agency, and Nationality MUST be stored as Actor columns; Actor Reference URLs MUST be stored as separate records; Profile Fields MUST store only additional information outside that fixed set.
- FR-020: The product MUST retain each Actor Reference URL and MUST open it as a reference. The product MUST NOT automatically retrieve or process NamuWiki or Naver content.
- FR-021: The Operator MUST be able to add and remove the association between an Actor and a collected Video.
- FR-022: The Operator MUST be able to merge two or more Actors into one Actor. Merging MUST move their Actor-Video associations to the surviving Actor and MUST NOT associate Actors automatically by matching names.
- FR-023: Actor and Actor-Video association records MUST persist across Operator sessions.
- FR-024: EL-015 MUST list the Videos associated with the selected Actor in descending order of published date, 50 Videos per page.
- FR-025: The merged Actors MUST no longer appear in EL-011 while the surviving Actor retains the associations.

Package data management

- FR-026: SCR-004 MUST list Videos across registered Channels.
- FR-027: The Operator MUST be able to edit and save a Video's package fields: Channel, video ID, title, description, published date, duration, thumbnail URL, available statistics, and unavailable state.
- FR-028: The Operator MUST be able to delete a Video after explicit confirmation. Deleting a Video MUST delete its Actor-Video associations.
- FR-029: SCR-004 MUST list Sync Errors with their Channel, reason, HTTP code, message, and time.
- FR-030: The Operator MUST be able to delete Sync Errors. Sync Errors MUST NOT be created or edited in the Operator interface.
- FR-031: The Operator MUST be able to edit and delete Channels.
- FR-032: Deleting a Channel MUST delete its Videos and their Actor-Video associations.
- FR-033: Video extraction state, extraction error, and extraction time MUST be shown but MUST NOT be editable in the package data screens.
- FR-034: SCR-004 MUST show that a later YouTube collection may overwrite edited package fields.
- FR-035: The Operator MUST be able to create a Channel from package fields. Creating a Channel MUST start its initial Video collection.
- FR-036: The Operator MUST be able to create a Video from package fields. Creating a Video MUST NOT start Gemini Actor Extraction.
- FR-038: The Operator MUST be able to create and delete Actors. Deleting an Actor MUST delete its Actor-Video associations.
- FR-039: The Operator MUST be able to create a Video from a Channel's detail screen; the Video MUST be associated with that Channel.
- FR-040: The Operator MUST be able to associate an existing Actor with a Video from the Video's detail screen and remove that association.
- FR-041: Only Users with administrator access MUST be able to authenticate to and access the Operator interface. Non-administrator Users MUST be denied access.
- FR-042: The product MUST provide a command-line administrator provisioning operation that creates a new administrator or grants administrator access to an existing User. A new administrator MUST have a name, a valid email address, and a password of at least eight characters.

Public access and contribution

- FR-043: A Visitor MUST be able to view SCR-005 and SCR-006 without authentication.
- FR-044: A Visitor MUST be able to self-register in SCR-007. A registered User MUST be a non-administrator Contributor and MUST NOT gain access to the Operator interface.
- FR-045: Email verification MUST NOT be required for a Contributor to use SCR-008.
- FR-046: A Contributor MUST be able to submit a Channel Contribution by Channel ID, `@handle`, or supported Channel URL. The product MUST resolve the reference through YouTube. The Channel MUST remain a Draft Record and initial collection MUST start only after Administrator approval.
- FR-047: A Contributor MUST be able to submit a Video Contribution by YouTube video URL. The product MUST retrieve the available Video data, keep the Video as a Draft Record, and associate it with its Channel after approval. A Video Contribution MUST NOT start Gemini Actor Extraction.
- FR-048: A Contributor MUST be able to submit an Actor Contribution with a Name and optional Activity Name, Birth Date, Birthplace, Education, Debut, Agency, Nationality, Actor Reference URLs, and Profile Fields. The Actor MUST remain a Draft Record until approval.
- FR-049: A Contributor MUST be able to submit a Contribution to change any Channel, Video, or Actor record. The canonical record MUST remain unchanged until approval.
- FR-050: A Contributor MUST be able to submit a Contribution to add or remove an Actor-Video association. The association MUST remain unchanged until approval.
- FR-051: A Contributor MUST NOT delete a Channel, Video, Actor, or Sync Error; merge Actors; start Gemini Actor Extraction; or manage Users.
- FR-052: SCR-006 MUST list Channels, Videos, and Actors and MUST show detail for a selected Channel, Video, or Actor.
- FR-053: SCR-006 MUST NOT show Sync Errors, extraction errors, or Administrator controls.
- FR-054: A merged Actor MUST NOT appear in SCR-006.
- FR-055: A write request without the required access MUST be denied without changing stored data.

Ownership and contributions

- FR-056: Every Channel, Video, and Actor MUST have an Owner `user_id`. Existing records MUST be backfilled to an Administrator Owner.
- FR-057: A Public Record MUST have `is_public = true`; a Draft Record MUST have `is_public = false` and MUST NOT appear in SCR-005 or SCR-006.
- FR-058: A Contributor write MUST create a pending Contribution and MUST NOT directly change canonical catalog data.
- FR-059: A Contribution MUST store its Contributor, polymorphic affected record, JSON payload, status, reviewer, review time, and review note.
- FR-060: A Contributor MUST have at most one pending Contribution per affected record. A second submission MUST report a conflict and allow the Contributor to manage the existing pending Contribution.
- FR-061: An Administrator MUST be able to approve or reject a pending Contribution with an optional review note.
- FR-062: Approving a Contribution MUST apply its payload to the canonical record and make the record public. Rejecting MUST leave canonical data unchanged. Approving a Channel MUST start initial collection; approving a Video MUST NOT start Gemini Actor Extraction.
- FR-063: Contributor names MUST NOT appear in SCR-005 or SCR-006.
- FR-064: Existing catalog records MUST be backfilled to an Administrator Owner and `is_public = true`.

## User Flows

UF-001 Register Channels and collect Videos

- Select a YouTube Channel Spreadsheet in EL-001 -> EL-002 is enabled.
- Activate EL-002 -> the product processes the Channel rows and shows row results in EL-003.
- Open SCR-002 -> registered Channels are listed in EL-005.
- Select a Channel in EL-006 -> collected Videos for that Channel are shown in EL-007.
- Continue registering Channels with another spreadsheet -> the product retains existing Channel and Video records and processes the new rows.

UF-002 Extract Actors from a Video

- Select a collected Video in SCR-002 -> EL-029 is available.
- Activate EL-029 -> Gemini Actor Extraction starts for the selected Video.
- Extraction completes -> the extracted Actors are associated with the selected Video.
- Open SCR-003 -> Actors are listed in EL-011.
- Select an Actor -> the Actor fields and associated Videos are shown.

UF-003 Edit Actor information

- Select an Actor in EL-011 -> SCR-003 shows the Actor.
- Edit Name, Activity Name, Birth Date, Birthplace, Education, Debut, Agency, Nationality, or additional Profile Fields in EL-012, and add, edit, or remove Actor Reference URLs in EL-013.
- Activate EL-014 -> the product saves the values.
- Add a NamuWiki or Naver URL in EL-013 -> the Operator can open the URL as a reference.

UF-004 Merge Actors

- Select two or more Actors in EL-011 -> EL-019 is enabled.
- Confirm that the selected Actors are the same person -> activate EL-019.
- The product selects the surviving Actor, moves the Actor-Video associations, and removes the merged Actors from EL-011.

UF-005 Add or remove an Actor Video

- Select an Actor in EL-011 -> EL-015 shows the associated Videos.
- Activate EL-017 -> the product links a collected Video to the Actor.
- Activate EL-018 on an associated Video -> the product removes that association.

UF-006 Edit or delete package data

- Open SCR-004 -> Videos and Sync Errors are listed.
- Select a Video -> EL-023 and EL-024 show the Video's edit and delete controls.
- Edit package fields and save -> the Video record is updated.
- Activate delete and confirm -> the Video and its Actor-Video associations are deleted.
- Select a Sync Error -> EL-027 and EL-028 show the Sync Error's edit and delete controls.
- Edit or delete the Sync Error -> the Sync Error record is updated or deleted.
- Edit or delete a Channel in SCR-002 -> the Channel is updated or deleted with its Videos and Actor-Video associations.

UF-007 Browse the public catalog

- Open SCR-005 without signing in -> EL-042 and the featured lists are shown.
- Open SCR-006 -> Channels, Videos, and Actors are listed.
- Select an Actor -> EL-056 shows the Actor's fields, reference URLs, and Videos.
- Select a Video -> EL-053 shows the Video's details and Actors.
- Select a Channel -> EL-050 shows the Channel and its Videos.

UF-008 Create a Contributor account

- Select EL-046 in SCR-005 -> SCR-007 shows the sign-up form.
- Enter a name, email address, and password, then activate EL-060 -> the product creates a Contributor account and signs the User in.
- Return to SCR-005 -> EL-047 is shown.

UF-009 Add a Channel and a Video

- Open SCR-008 -> EL-066 and EL-068 are shown.
- Enter a Channel reference and activate EL-067 -> the Channel is created or updated and initial collection starts.
- Enter a YouTube video URL and activate EL-069 -> the Video is created or updated and associated with its Channel.
- Gemini Actor Extraction does not start.

UF-010 Add and edit an Actor

- Open SCR-008 -> EL-072 is shown.
- Enter a Name and optional fields, then activate EL-074 -> the Actor is created.
- Select the Actor and activate EL-075 -> the Actor's fields and reference URLs are saved.
- Activate EL-076 -> the Actor is associated with or dissociated from a Video.
- Open SCR-006 -> the Actor appears in EL-054.

## Error Conditions

- ERR-001: A spreadsheet row contains an unsupported Channel value -> EL-003 identifies the row and reports that it was not imported.
- ERR-002: Spreadsheet processing fails -> SCR-001 shows the failure and preserves row-level results available at the time of failure.
- ERR-003: YouTube collection fails for a Channel -> SCR-002 shows the Channel collection failure and retains previously collected Videos.
- ERR-004: YouTube quota is exhausted -> SCR-002 shows that collection is paused due to quota exhaustion.
- ERR-005: Gemini Actor Extraction fails -> SCR-002 shows the failure and retains the source Video and existing Actor associations.
- ERR-006: Saving Actor data fails -> SCR-003 shows the failure and retains the Operator's entered values.
- ERR-007: Merging Actors fails -> SCR-003 shows the failure and leaves all Actor associations unchanged.
- ERR-008: Saving or deleting a Video fails -> SCR-004 shows the failure and leaves the Video and its associations unchanged.
- ERR-009: Deleting a Sync Error fails -> SCR-004 shows the failure and leaves the Sync Error unchanged.
- ERR-010: Saving or deleting a Channel fails -> SCR-002 shows the failure and leaves the Channel and its associations unchanged.
- ERR-011: Creating a Channel or Video fails -> the affected screen shows the failure and retains the entered values.
- ERR-012: Creating or deleting an Actor fails -> SCR-003 shows the failure and leaves the Actor and its associations unchanged.
- ERR-013: A Channel reference cannot be resolved -> SCR-008 shows the failure and retains the entered reference.
- ERR-014: A video URL cannot be resolved -> SCR-008 shows the failure and retains the entered URL.
- ERR-015: Sign-up, sign-in, or a password reset request fails -> SCR-007 shows the failure and retains the entered values.
- ERR-016: A write request lacks the required access -> the product changes no stored data and reports the failure.

## Non-Goals

- Public catalog search is deferred.
- Email verification is deferred.
- Correction Requests are excluded from the current product.
- Opt-Out Requests are excluded from the current product.
- Contributor deletion, Actor merging, Gemini Actor Extraction, Sync Error management, and User administration are excluded.
- Separate Actor Candidate, Actor Identity, Filmography Candidate, Candidate Source, and Review Status records.
- Approving or rejecting extracted records.
- Automatic Actor merging based only on matching names.
- Automatic collection or processing of NamuWiki or Naver content.
- A Channel discovery process; Channel references enter through an Operator-supplied spreadsheet.
- An Actor registration import file; Actors enter through Video extraction or Operator creation.
- Direct database access from the Operator interface.
- Manual creation or editing of Sync Errors; Sync Errors enter through the collection flow and can only be deleted in the Operator interface.

## Acceptance Criteria

- AC-001: Importing a spreadsheet with supported Channel IDs, handles, and URLs registers those Channels and reports a result for every row (FR-001, FR-002, FR-003, SCR-001).
- AC-002: Importing an already registered Channel retains its collected Videos and synchronization state (FR-004, SCR-001).
- AC-003: A registered Channel's available uploads are collected across every returned page without another spreadsheet import (FR-005, FR-006, SCR-002).
- AC-004: Collected Videos are associated with their Channel and their returned metadata is retained across Operator sessions (FR-007, FR-008, FR-009, SCR-002).
- AC-005: A Video that is no longer returned as available remains recorded as unavailable, and a later available response clears that state (FR-010, FR-011, SCR-002).
- AC-006: A YouTube collection failure and quota exhaustion are shown to the Operator (FR-012, FR-013, SCR-002).
- AC-007: Starting Gemini Actor Extraction for a selected Video associates the extracted Actors with that Video; registering or collecting a Video never starts extraction (FR-014, FR-015, FR-016, FR-017, SCR-002).
- AC-008: Actor Name, Activity Name, Birth Date, Birthplace, Education, Debut, Agency, Nationality, and additional Profile Fields can be edited and saved; all fields except Name may be empty (FR-018, FR-019, SCR-003).
- AC-009: Supplied Actor Reference URLs are retained, can be added or removed, and can be opened; no NamuWiki or Naver content is collected automatically (FR-020, SCR-003).
- AC-010: An Operator can add and remove an association between an Actor and a collected Video (FR-021, SCR-003).
- AC-011: An Operator can merge selected Actors; their Video associations move to the surviving Actor, and matching names alone never merge Actors (FR-022, FR-025, SCR-003).
- AC-012: A repeated extraction does not duplicate an existing Actor-Video association (FR-017, SCR-002).
- AC-013: Actor and Actor-Video association records persist across Operator sessions (FR-023, SCR-003).
- AC-014: EL-015 lists an Actor's Videos 50 per page in descending published-date order (FR-024, SCR-003).
- AC-015: SCR-004 lists Videos across Channels and shows each Video's Channel, published date, availability, and extraction state (FR-026, SCR-004).
- AC-016: A Video's package fields can be edited and saved, and deleting a Video removes its Actor-Video associations (FR-027, FR-028, SCR-004).
- AC-017: Sync Errors are listed with their Channel, reason, HTTP code, message, and time, and can be deleted; they cannot be created or edited (FR-029, FR-030, SCR-004).
- AC-018: A Channel can be edited and deleted; deleting it removes its Videos and Actor-Video associations (FR-031, FR-032, SCR-002).
- AC-019: Video extraction state, error, and time are visible but not editable in SCR-004 (FR-033).
- AC-020: SCR-004 shows the package-overwrite notice (FR-034).
- AC-021: An Operator can create a Channel, and its initial Video collection starts (FR-035, SCR-002).
- AC-022: An Operator can create a Video without starting Gemini Actor Extraction (FR-036, SCR-004).
- AC-024: An Operator can create and delete an Actor; deleting it removes its Actor-Video associations (FR-038, SCR-003).
- AC-025: An Operator can create a Video from a Channel's detail screen, and the Video is associated with that Channel (FR-039, SCR-002).
- AC-026: An Operator can associate an existing Actor with a Video and remove the association from the Video's detail screen (FR-040, SCR-004).
- AC-027: An Administrator can authenticate to and access the Operator interface; a non-administrator User is denied access to the Operator interface (FR-041).
- AC-028: The administrator provisioning command creates a new administrator and promotes an existing User; it rejects an invalid email or a password shorter than eight characters (FR-042).
- AC-029: A Visitor can view the service landing and the public catalog without signing in (FR-043, SCR-005, SCR-006).
- AC-030: Self-registration creates a Contributor who can use SCR-008 and cannot access the Operator interface (FR-044, SCR-007).
- AC-031: A Contributor can submit a Channel Contribution by ID, handle, and URL, and initial collection starts only after approval (FR-046, FR-058, FR-062, SCR-008).
- AC-032: A Contributor can submit a Video Contribution by URL; the Video is associated with its Channel after approval and no extraction starts (FR-047, FR-058, FR-062, SCR-008).
- AC-033: A Contributor can submit an Actor Contribution with only a Name; the Actor appears in the public catalog only after approval (FR-048, FR-057, FR-062, SCR-006).
- AC-034: A Contributor can submit a Contribution to change any Channel, Video, or Actor record; canonical data changes only after approval (FR-049, FR-058, FR-061, SCR-008).
- AC-035: A Contributor can submit an Actor-Video association Contribution; the association changes only after approval (FR-050, FR-058, FR-062, SCR-008).
- AC-036: A Contributor is offered no delete, merge, extraction, or Sync Error control, and a denied write request changes no data (FR-051, FR-055).
- AC-037: SCR-006 shows Channel, Video, and Actor detail, hides merged Actors and Sync Errors, and shows no Administrator controls (FR-052, FR-053, FR-054).
- AC-038: Existing records have an Administrator Owner and `is_public = true` (FR-056, FR-064).
- AC-039: Draft Records and draft associations never appear in SCR-005 or SCR-006 (FR-057, SCR-005, SCR-006).
- AC-040: A Contributor write creates a pending Contribution without changing canonical data (FR-058, SCR-008).
- AC-041: A Contribution stores a polymorphic record, Contributor, JSON payload, status, reviewer, review time, and note (FR-059).
- AC-042: A second pending Contribution for the same Contributor and record reports a conflict and preserves the existing pending Contribution (FR-060, SCR-008).
- AC-043: An Administrator can approve a Contribution, applying its payload and publishing the record (FR-061, FR-062, SCR-009).
- AC-044: An Administrator can reject a Contribution without changing canonical data (FR-061, SCR-009).
- AC-045: Approving a Channel starts initial collection; approving a Video does not start Gemini Actor Extraction (FR-062, SCR-009).
