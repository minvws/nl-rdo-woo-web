# Doctrine entities

<!-- TOC -->
- [Doctrine entities](#doctrine-entities)
  - [Global notes](#global-notes)
  - [Where entities live](#where-entities-live)
  - [Dossiers](#dossiers)
    - [Main documents and attachments](#main-documents-and-attachments)
    - [WooDecision-specific entities](#woodecision-specific-entities)
  - [Organisation, department and subject](#organisation-department-and-subject)
  - [User](#user)
  - [Inquiry](#inquiry)
  - [Supporting entities](#supporting-entities)
<!-- TOC -->

## Global notes

- IDs are `Symfony\Component\Uid\Uuid` values generated in PHP, **not** by the database. Two strategies are in use:
  - most entities assign `Uuid::v6()` in their constructor;
  - `User`, `LoginActivity`, `Department`, `Organisation`, `DocumentPrefix`, `Inquiry`, `InquiryInventory`,
    `ProductionReportProcessRun` and `History` use `#[ORM\CustomIdGenerator(class: UuidGenerator::class)]`, which
    yields UUIDv7 because `config/packages/uid.yaml` sets `default_uuid_version: 7`.
- Most entities carry `createdAt` / `updatedAt` through `Shared\Doctrine\TimestampableTrait`, which refreshes
  `updatedAt` on `preUpdate` unless an explicit override is set.
- `encrypted_string` and `encrypted_array` column types are encrypted at rest with symmetric encryption, implemented in
  `Shared\Doctrine\EncryptedString` / `EncryptedArray` and wired up by `Shared\Kernel`.

## Where entities live

There is no `src/Entity` directory. Entities live next to the domain logic that owns them, almost all of them under
`src/Domain/` in the `Shared\` namespace (`src/` is mapped to `Shared\`, see `composer.json`). The exceptions are
`Shared\Service\Security\User` and `Shared\Service\Security\LoginActivity`.

This document maps the entity landscape rather than listing every column, because there are over a hundred entity
classes and per-field tables cannot be kept accurate. Read the entity class for the authoritative field list. To find
all of them:

```shell
grep -rl 'ORM\\Entity' src apps tenants
```

## Dossiers

`Shared\Domain\Publication\Dossier\AbstractDossier` is the base class for every publication type. It uses
[single table inheritance](https://www.doctrine-project.org/projects/doctrine-orm/en/3.3/reference/inheritance-mapping.html#single-table-inheritance)
on the `dossier` table with a `type` discriminator column:

| Discriminator value    | Entity                | Namespace under `src/Domain/Publication/Dossier/Type/` |
|------------------------|-----------------------|--------------------------------------------------------|
| `woo-decision`         | `WooDecision`         | `WooDecision`                                          |
| `covenant`             | `Covenant`            | `Covenant`                                             |
| `annual-report`        | `AnnualReport`        | `AnnualReport`                                         |
| `investigation-report` | `InvestigationReport` | `InvestigationReport`                                  |
| `disposition`          | `Disposition`         | `Disposition`                                          |
| `complaint-judgement`  | `ComplaintJudgement`  | `ComplaintJudgement`                                   |
| `other-publication`    | `OtherPublication`    | `OtherPublication`                                     |
| `advice`               | `Advice`              | `Advice`                                               |
| `request-for-advice`   | `RequestForAdvice`    | `RequestForAdvice`                                     |
| `draft-decision`       | `DraftDecision`       | `DraftDecision`                                        |

See [dossier-types.md](dossier-types.md) for how to add a new type. The exact discriminator values come from the
`Shared\Domain\Publication\Dossier\Type\DossierType` enum; check that enum rather than this table if a value matters.
Note there is also an unrelated `Shared\Domain\Publication\Dossier\ViewModel\DossierType` view model class.

Properties shared by all types are defined on `AbstractDossier`. Only type-specific properties belong on the
subclasses, so a `Covenant` has no documents while a `WooDecision` does.

| Field               | Type               | Description                                                                  |
|---------------------|--------------------|------------------------------------------------------------------------------|
| `id`                | UUID               | Internal ID                                                                  |
| `externalId`        | `ExternalId`       | Identifier assigned by an external system, unique per organisation. Nullable |
| `dossierNumber`     | string             | Dossier number, unique in combination with `documentPrefix`                  |
| `title`             | `DossierTitle`     | Title of the publication                                                     |
| `status`            | `DossierStatus`    | `new`, `concept`, `scheduled`, `preview`, `published` or `deleted`           |
| `dateFrom`          | `PlainDate`        | Start of the period this dossier covers. Nullable                            |
| `dateTo`            | `PlainDate`        | End of the period this dossier covers. Nullable                              |
| `summary`           | text               | Summary shown on the public detail page                                      |
| `documentPrefix`    | string             | Prefix that scopes `dossierNumber` and document numbers                      |
| `publicationDate`   | `PlainDate`        | Date of publication. Nullable                                                |
| `completed`         | bool               | Whether the wizard has been completed at least once                          |
| `internalReference` | string             | Free-text reference for internal use, not shown publicly                     |
| `departments`       | m:n `Department`   | Via the `dossier_department` table                                           |
| `organisation`      | m:1 `Organisation` | Owning organisation                                                          |
| `subject`           | m:1 `Subject`      | Optional subject label. Nullable                                             |

Note that `publicationReason`, `decision` and `decisionDate` are **not** properties of `AbstractDossier`. They belong to
`WooDecision` (as `PublicationReason`, `DecisionType` and `PlainDate` values), which is exactly the split this class
hierarchy exists to express.

### Main documents and attachments

Two more single-table hierarchies follow the same pattern, one subclass per dossier type that supports them:

- `Shared\Domain\Publication\MainDocument\AbstractMainDocument` — for example `AnnualReportMainDocument`,
  `WooDecisionMainDocument`. Entities that own one implement `EntityWithMainDocument`.
- `Shared\Domain\Publication\Attachment\Entity\AbstractAttachment` — for example `AnnualReportAttachment`,
  `WooDecisionAttachment`. Entities that own them implement `EntityWithAttachments`.

Not every type has both. `ComplaintJudgement` has a main document but no attachments; `WooDecision` has both plus
documents.

### WooDecision-specific entities

| Entity                       | Description                                                                |
|------------------------------|----------------------------------------------------------------------------|
| `Document`                   | An individual published document belonging to a `WooDecision`              |
| `ProductionReport`           | The uploaded spreadsheet listing the documents and their metadata          |
| `ProductionReportProcessRun` | One processing run of a production report, including its errors and status |
| `Inventory`                  | The generated, downloadable document list ("inventarislijst")              |
| `DocumentFileSet`            | A set of uploaded document files being processed for a dossier             |
| `DocumentFileUpdate`         | A pending change to a single document within a `DocumentFileSet`           |
| `DocumentFileUpload`         | One uploaded file within a `DocumentFileSet`                               |
| `Inquiry`                    | See [Inquiry](#inquiry) below                                              |
| `InquiryInventory`           | The generated document list for an inquiry                                 |

`Document` and `Inquiry` have an m:n relation through `inquiry_document`; `Inquiry` and `WooDecision` through
`inquiry_dossier`. `Document` relates to dossiers through `document_dossier`.

## Organisation, department and subject

`Organisation` is the tenancy boundary within a tenant: users, dossiers, inquiries, subjects and document prefixes all
belong to one. Most authorization filters are expressed as "same organisation only" (see
[access-roles.md](access-roles.md)).

| Entity           | Key fields                                                                                                                          |
|------------------|-------------------------------------------------------------------------------------------------------------------------------------|
| `Organisation`   | `name`; owns `users`, `dossiers`, `inquiries`, `subjects`, `documentPrefixes`; m:n `departments`                                    |
| `Department`     | `name`, `shortTag`, `slug`, `public`, plus landing-page content (`landingPageTitle`, `feedbackContent`, `responsibilityContent`, …) |
| `Subject`        | `name`, `organisation`, `dossiers`                                                                                                  |
| `DocumentPrefix` | The prefix used in dossier and document numbers, scoped to an organisation                                                          |

## User

A `User` can log in to the admin (balie) and manage dossiers and/or other users depending on their roles.

| Field             | Type                 | Description                                                         |
|-------------------|----------------------|---------------------------------------------------------------------|
| `id`              | UUID                 | Internal ID                                                         |
| `createdAt`       | DateTimeImmutable    | When this record was created                                        |
| `updatedAt`       | DateTimeImmutable    | When this record was last updated                                   |
| `email`           | string               | Email address, unique                                               |
| `roles`           | jsonb                | Roles assigned to this user                                         |
| `password`        | string               | Hashed password                                                     |
| `name`            | string               | Full name, for display purposes                                     |
| `mfaToken`        | encrypted string[^1] | TOTP secret, used to generate the QR code for the authenticator app |
| `mfaRecovery`     | encrypted array[^1]  | Recovery codes                                                      |
| `enabled`         | bool                 | Whether the user may sign in                                        |
| `changepwd`       | bool                 | Whether the user must change their password on next sign-in         |
| `organisation`    | m:1 `Organisation`   | The organisation this user belongs to                               |
| `loginActivities` | 1:n `LoginActivity`  | Successful sign-ins, written after a valid OTP                      |

[^1]: Encrypted values are encrypted at rest in the database with symmetric encryption.

## Inquiry

An `Inquiry` ("zaak") groups documents and WooDecisions that are relevant to one information request. It lives in the
WooDecision namespace, because only that dossier type relates to inquiries.

| Field           | Type                   | Description                                            |
|-----------------|------------------------|--------------------------------------------------------|
| `id`            | UUID                   | Internal ID                                            |
| `inquiryNumber` | string                 | The inquiry ("case") number                            |
| `token`         | string                 | Token used to access the inquiry                       |
| `documents`     | m:n `Document`         | Documents relevant to this inquiry                     |
| `dossiers`      | m:n `WooDecision`      | Dossiers relevant to this inquiry                      |
| `inventory`     | 1:1 `InquiryInventory` | The generated document list for this inquiry. Nullable |
| `organisation`  | m:1 `Organisation`     | Owning organisation                                    |

There is no `casenr` field. `casenr` and `case` do survive as accepted column headers in imported spreadsheets — see
[terminology.md](terminology.md).

## Supporting entities

| Entity            | Namespace                                    | Description                                            |
|-------------------|----------------------------------------------|--------------------------------------------------------|
| `ContentPage`     | `Domain\Content\Page`                        | Editable static pages, ensured by `woopie:post-deploy` |
| `History`         | `Domain\Publication\History`                 | Audit trail of changes shown in the admin              |
| `NoticeNotPublic` | `Domain\Publication\Dossier\NoticeNotPublic` | Explanation shown when something is not public         |
| `BatchDownload`   | `Domain\Publication\BatchDownload`           | A generated ZIP archive of a dossier or inquiry        |
| `UploadEntity`    | `Domain\Upload`                              | A chunked upload in progress                           |
| `WooIndexSitemap` | `Domain\WooIndex`                            | A generated DiWoo sitemap                              |
| `RolloverCounter` | `Domain\Search\Index\Rollover`               | Progress counter for an Elasticsearch index rollover   |
| `LoginActivity`   | `Service\Security\LoginActivity`             | One successful sign-in for a user                      |
