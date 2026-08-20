# Woo Publication Platform

<!-- TOC -->
- [Woo Publication Platform](#woo-publication-platform)
  - [Applications](#applications)
  - [Tenants](#tenants)
  - [Configuration layering](#configuration-layering)
  - [The Shared domain](#the-shared-domain)
    - [Publication](#publication)
    - [Ingest](#ingest)
    - [Search](#search)
    - [Files and storage](#files-and-storage)
    - [Other domains](#other-domains)
  - [Services outside the domain layer](#services-outside-the-domain-layer)
  - [Message queues](#message-queues)
  - [Adding more ingesters / file formats](#adding-more-ingesters--file-formats)
  - [Monitoring endpoints](#monitoring-endpoints)
<!-- TOC -->

This document describes how the code is organised. For the publication domain specifically,
[dossier-types.md](dossier-types.md) is more detailed and is the better starting point when adding a publication type.

## Applications

The platform is not a single application. `composer.json` maps six PSR-4 roots:

| Namespace         | Path                        | Contents                                                               |
|-------------------|-----------------------------|------------------------------------------------------------------------|
| `Shared\`         | `src/`                      | The domain model, and most of the controllers, forms and templates     |
| `Admin\`          | `apps/admin/src/`           | Mostly the admin API (`Api/`), plus authentication and user management |
| `Public\`         | `apps/public/src/`          | Empty; the public controllers live in `src/Controller/Public`          |
| `PublicationApi\` | `apps/publication_api/src/` | The API Platform application for external parties                      |
| `Worker\`         | `apps/worker/src/`          | The message queue consumers                                            |
| `WooMinVWS\`      | `tenants/minvws/src/`       | minvws-specific code, including all audit logging                      |

A running instance is exactly **one** of these applications, selected by the `APP_ID` environment variable (see
[environment-settings.md](environment-settings.md)) or `--id` on the command line (see [commands.md](commands.md)). This
replaced the old `APP_MODE` setting. `apps/shared/` holds configuration only and has no `src/`.

The practical consequence: a service, route or console command defined under `apps/<app>/` only exists when that
application is booted.

**The split is not what the directory names suggest.** `apps/` is much smaller than `src/`, and the web-facing code has
largely stayed in `Shared\`:

- the admin dossier wizard — its controllers (`src/Controller/Admin/Dossier/`), forms (`src/Form/Dossier/`) and
  templates — is in `Shared\`, not in `apps/admin/`. `apps/admin/src/Controller` holds only four controllers (index,
  login, user management and organisation switching);
- the public site's controllers are in `src/Controller/Public/`, and `apps/public/src/` is empty;
- what `apps/admin/` really contains is the admin API used by the editor front-end, in `apps/admin/src/Api/Admin/`.

So when looking for a page, start in `src/Controller/`. Only the Publication API is genuinely self-contained in its own
application.

## Tenants

The platform is multi-tenant. `Shared\TenantId` enumerates them: `minvws`, `minfin` and `minbuza`.

For web requests, `Shared\TenantResolver` maps the incoming `HTTP_HOST` to a tenant using the
`HTTP_HOST_TO_TENANT_MAPPING` environment variable. On the command line the tenant comes from the mandatory `--tenant`
option.

Per-tenant overrides live in `tenants/<tenant>/`, which can contain `config/`, `assets/`, `translations/`, `src/` and
`tests/`. Only `minvws` currently has `src/`, which is why some behaviour — audit logging in particular, see
[logging.md](logging.md) — exists for that tenant alone.

## Configuration layering

`Shared\Kernel` compiles a separate container per (tenant, application, environment) and loads bundles, services and
routes from three directories in order:

1. `config/` — shared
2. `apps/<application>/config/` — application-specific
3. `tenants/<tenant>/config/` — tenant-specific

Later layers override earlier ones. The kernel also exposes `kernel.application_id` and `kernel.tenant_id` as container
parameters, and writes cache, build and log directories per tenant and application, so `var/log/minvws/admin/` and
`var/log/minfin/worker/` are separate.

## The Shared domain

`src/Domain/` is organised by domain rather than by technical role. There is no `src/Entity`, `src/Message` or
`src/MessageHandler`; entities, commands and handlers sit next to the domain logic that owns them.

### Publication

`src/Domain/Publication/` is the core of the system:

| Namespace        | Contents                                                                         |
|------------------|----------------------------------------------------------------------------------|
| `Dossier`        | `AbstractDossier`, the wizard, workflows, voters, delete strategies, view models |
| `Dossier/Type/*` | One namespace per publication type (see [dossier-types.md](dossier-types.md))    |
| `MainDocument`   | `AbstractMainDocument` and its commands, handlers and events                     |
| `Attachment`     | `AbstractAttachment` and its commands, handlers and events                       |
| `BatchDownload`  | Generating ZIP archives of a dossier or inquiry                                  |
| `History`        | The change history shown in the admin                                            |
| `Subject`        | Subject labels                                                                   |

See [doctrine.md](doctrine.md) for the entity landscape.

### Ingest

Ingest is the process of (re-)building all derived data for a publication: indexing into Elasticsearch, extracting
content, generating thumbnails, running OCR. It must be able to restore everything from the database and file storage
alone.

`src/Domain/Ingest/` has two halves:

- `Ingest/Process/` — one namespace per ingest step, each holding an `Ingest*Command` and its `Ingest*Handler`:
  `Dossier`, `Pdf`, `PdfPage`, `MetadataOnly`, `TikaOnly` and `SubType`. `Process/Dossier` also holds `DossierIngester`
  and the per-type strategies behind `DossierIngestStrategyInterface`.
- `Ingest/Content/` — content extraction, with `Extractor/Tika` and `Extractor/Tesseract` behind
  `ContentExtractorInterface`, keyed by `ContentExtractorKey`.

A PDF is ingested by `IngestPdfCommand`, which dispatches an `IngestPdfPageCommand` per page.

### Search

`src/Domain/Search/`:

| Namespace            | Contents                                                                       |
|----------------------|--------------------------------------------------------------------------------|
| `Index`              | `ElasticDocument`, `ElasticDocumentType`, the per-entity indexers and updaters |
| `Index/ElasticIndex` | `ElasticIndexManager`, which creates, deletes and aliases indices              |
| `Index/Rollover`     | Rolling over to a new mapping version                                          |
| `Index/Schema`       | The mapping-facing helpers (`ElasticPath`, `ElasticHighlights`)                |
| `Result`             | Search result view models, per dossier type                                    |

See [elastic_index.md](elastic_index.md) for the index itself.

### Files and storage

| Namespace            | Contents                                                            |
|----------------------|---------------------------------------------------------------------|
| `Domain/Upload`      | Chunked uploads, validation and virus scanning (`Upload/AntiVirus`) |
| `Domain/FileStorage` | Checking storage against the database, finding orphaned files       |
| `Domain/S3`          | S3 / MinIO specifics                                                |
| `Service/Storage`    | Flysystem-backed storage services, local or S3                      |

### Other domains

`Domain/Content` (editable content pages), `Domain/Department`, `Domain/Organisation`, `Domain/WooIndex` (the DiWoo
sitemap), `Domain/Sitemap`, `Domain/Robots` (see [robots.md](robots.md)) and `Domain/ArchiveExtractor`.

## Services outside the domain layer

Some services still live under `src/Service/`:

| Class or namespace                                                              | Purpose                                                                                 |
|---------------------------------------------------------------------------------|-----------------------------------------------------------------------------------------|
| `Encryption/EncryptionService`                                                  | Encrypt and decrypt data at rest with libsodium. Used for MFA tokens and recovery codes |
| `Elastic/ElasticClientFactory`                                                  | Builds a fully configured Elasticsearch client                                          |
| `Elastic/ElasticService`                                                        | Indexes documents. Retrieval goes through `Service/Search`                              |
| `Storage`                                                                       | Abstracts file storage, local or remote, and can copy a file locally for processing     |
| `Search`                                                                        | Query building and execution for the public search                                      |
| `Security`                                                                      | `User`, voters, the auth matrix and application-ID checks                               |
| `Inventory`, `Inquiry`                                                          | Reading production report spreadsheets and inquiry link imports                         |
| `DocumentService`, `DossierService`, `AttachmentService`, `MainDocumentService` | Entity-level operations used by the admin and API                                       |

## Message queues

Messenger is configured in `config/packages/messenger.yaml`. Besides `sync`, there are five queued transports, listed in
priority order: `high`, `esupdater`, `ingestor`, `global` and `api_documents`. Each maps to its own RabbitMQ queue
through a `<TENANT>_*_TRANSPORT_DSN` environment variable (see
[environment-settings.md](environment-settings.md)).

Commands are routed to a transport in the `routing` section. Roughly: user-facing work that must happen promptly goes to
`high`, ingest steps to `ingestor`, index updates to `esupdater`, archive generation to `global`, and document uploads
from the Publication API to `api_documents`.

The consumers run in the `worker` application, one container per tenant.

## Adding more ingesters / file formats

To add support for another file format:

1. Create a command and handler in a new namespace under `Shared\Domain\Ingest\Process\`. Follow an existing pair such
   as `Process/Pdf/IngestPdfCommand` and `IngestPdfHandler`. The handler does the work, or delegates to a processor
   class in the same namespace as `PdfPageProcessor` does.
2. If the format needs its own content extraction, implement `ContentExtractorInterface` in
   `Shared\Domain\Ingest\Content\Extractor\` and add a case to `ContentExtractorKey`.
3. Route the new command in `config/packages/messenger.yaml`, under the `ingestor` transport alongside the existing
   `Ingest*Command` entries.
4. If a dossier type needs to ingest extra relationships or files, implement `DossierIngestStrategyInterface` and add it
   to the mapping in `DossierIngester`. See `WooDecisionIngestStrategy` for an example.
5. Restart the workers so they pick up the new handler.

## Monitoring endpoints

Both are defined in `src/Controller/Public/StatsController.php`.

- `/prometheus` (`app_prometheus`) exposes three counters as plain text: the number of documents, the number of dossiers
  and the total page count.
- `/health` (`app_health`) returns JSON reporting reachability of PostgreSQL, Redis, Elasticsearch, RabbitMQ and both the
  document and thumbnail storage, with a `503` status when any of them is down.
