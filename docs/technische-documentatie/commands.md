# Woo Publication Platform commands

<!-- TOC -->
- [Woo Publication Platform commands](#woo-publication-platform-commands)
  - [Selecting a tenant and an application](#selecting-a-tenant-and-an-application)
  - [Cron commands](#cron-commands)
    - [Upload cleanup](#upload-cleanup)
    - [Archives cleanup](#archives-cleanup)
    - [Document fileset cleanup](#document-fileset-cleanup)
    - [Inventory processrun cleanup](#inventory-processrun-cleanup)
    - [Publish scheduled dossiers](#publish-scheduled-dossiers)
  - [Console commands](#console-commands)
    - [Platform check](#platform-check)
    - [Page check](#page-check)
    - [Index management](#index-management)
    - [Ingestion](#ingestion)
    - [User management](#user-management)
    - [Document location](#document-location)
    - [Woo-index](#woo-index)
    - [File-storage check](#file-storage-check)
    - [Post deploy](#post-deploy)
    - [BatchDownload refresh](#batchdownload-refresh)
    - [Move orphaned files](#move-orphaned-files)
    - [Export revoked urls](#export-revoked-urls)
    - [Inventory refresh](#inventory-refresh)
    - [Generate database key](#generate-database-key)
    - [Generate auditlog key](#generate-auditlog-key)
    - [Normalize document grounds](#normalize-document-grounds)
    - [Generate publication context](#generate-publication-context)
  - [Development commands](#development-commands)
    - [SQL dump](#sql-dump)
    - [Clean sheet](#clean-sheet)
    - [Content extraction](#content-extraction)
<!-- TOC -->

## Selecting a tenant and an application

`bin/console` takes two options that decide which kernel is booted:

| Option           | Required | Values                                                   | Falls back to             |
|------------------|----------|----------------------------------------------------------|---------------------------|
| `--tenant`, `-T` | yes      | `minvws`, `minfin`, `minbuza`                            | nothing — it is mandatory |
| `--id`, `-i`     | no       | `admin`, `public`, `publication_api`, `worker`, `shared` | `APP_ID`, then `shared`   |

`--tenant` is mandatory; the command exits with an error if it is missing.

`--id` selects the application. It matters because `Shared\Kernel` compiles a separate container per (tenant,
application, environment) and loads `apps/<id>/config` for that application only, so **a command defined inside an
application only exists for that application**:

- commands under `src/` are shared and available whatever the application is. They live in `src/Command/` and its
  subdirectories, plus `src/Domain/WooIndex/Command/`;
- commands under `apps/<app>/src/Command/` belong to that application alone. Today that is the user-management commands
  (admin) and `woopie:post-deploy`, which is registered once per application and does different work for each.

In practice you rarely have to pass `--id`, because every service container sets `APP_ID` to its own application and
`bin/console` falls back to it. `task shell` opens the `admin` container, where `APP_ID=ADMIN`, so the admin commands
work there as written.

You do need `--id` when the command belongs to a different application than the container you are in. From the worker
container, for example:

```shell
# There are no commands defined in the "woopie:user" namespace.
bin/console --tenant=minvws woopie:user:view foo@example.org

# Works
bin/console --tenant=minvws --id=admin woopie:user:view foo@example.org
```

The examples below assume you are in the container the command belongs to, and pass `--id` only where the application is
the point.

## Cron commands

### Upload cleanup

```shell
bin/console --tenant=minvws woopie:cron:clean-uploads
```

Cleans up outdated or orphaned upload entities and files. Should be executed at least daily.

### Archives cleanup

```shell
bin/console --tenant=minvws woopie:cron:clean-archives
```

Cleans up expired archives for woo-decisions. Should be executed at least daily.

### Document fileset cleanup

```shell
bin/console --tenant=minvws woopie:cron:clean-document-file-sets
```

Cleans up DocumentFileSet entities and related files that are no longer needed. Should be executed at least daily.

### Inventory processrun cleanup

```shell
bin/console --tenant=minvws woopie:cron:clean-inventory-process-run
```

Marks expired inventory process runs as failed. Should be executed at least daily.

### Publish scheduled dossiers

```shell
bin/console --tenant=minvws woopie:cron:publisher
```

Publish dossiers when their publication date is reached. Should be executed at least daily, shortly after midnight.

## Console commands

This is a list of all console commands available for the Woo Publication Platform.

### Platform check

```shell
bin/console --tenant=minvws woopie:check:platform
```

Checks if the current platform is ready for running. It will mainly check installed tools and extensions that are
needed to run the project. The former name `woopie:check:production` still works as an alias.

### Page check

```shell
bin/console --tenant=minvws woopie:page:check
```

Checks if there are pages that are not yet indexed in ElasticSearch.

### Index management

```shell
bin/console --tenant=minvws woopie:index:alias <index> <alias>
bin/console --tenant=minvws woopie:index:create <name> <version> [--read] [--write]
bin/console --tenant=minvws woopie:index:delete <index>
```

Creates or deletes an elasticsearch index. It is also possible to create an alias for an index.

For `woopie:index:create`, `<version>` is the mapping version to use (see [elastic_index.md](elastic_index.md)) or
`latest` for the newest one. Pass `--read` and/or `--write` to point the read and write aliases at the new index
straight away, which is what the local setup does:

```shell
bin/console --tenant=minvws woopie:index:create minvws-initial latest --read --write
```

### Ingestion

```shell
bin/console --tenant=minvws woopie:ingest:dossier <prefix> <dossierNumber> [--force-refresh]
```

Starts the ingestion of a dossier. Both the document prefix and the dossier number are required. Add `--force-refresh`
to skip any caching.

In order for this to run, you must have the workers/consumers running. This can be done with

```shell
bin/console --tenant=minvws messenger:consume -d
```

### User management

These three commands belong to the admin application. They work as written inside the `admin` container (`task shell`);
from anywhere else add `--id=admin`, see
[Selecting a tenant and an application](#selecting-a-tenant-and-an-application).

```shell
bin/console --tenant=minvws woopie:user:create "<email>" "<fullname>" [--super-admin]
```

Creates a new user with the given email address and name. When the `--super-admin` (`-s`) flag is given, the user is
granted the super-admin role.

This command will generate a random password, 2fa token and 2fa recovery tokens. The password must be
changed on first login and is only visible during this creation period.

```shell
bin/console --tenant=minvws woopie:user:view <email>
```

Retrieves the 2fa token of the user with the given email address, and displays it as a QR code when `qrencode` is
installed. Everything EXCEPT the password can be viewed.

```shell
bin/console --tenant=minvws woopie:user:reset <email>
```

This will reset the password, 2fa token and 2fa recovery codes for the given user. The user must
change the password after the first login. Note that it is NOT possible to view the password once
created.

### Document location

```shell
bin/console --tenant=minvws woopie:where <url>
```

If you enter a URL, this command will return the location of the given file in the local storage.
This is needed since there is no direct relation between the URL and the local storage location.

Example:

```shell
$ bin/console --tenant=minvws woopie:where https://localhost:8000/dossier/VWS-534-3444/document/TEST111-5034
Matched /dossier/VWS-534-3444/document/TEST111-5034 to app_document_detail
Document : 1ee069e4-70b6-6a54-b3a9-95eaef3bc6c6
Filename : 1729902-208789-PG Nota voor brief aan RIVM betreft opdrachtbrief wetenschappelijk adviespanel COVID vaccin.pdf.pdf
Path     : /dd/19ba3eda7104b4041da826c5a8f9562abd548b3aa1968ca30112d4ebdc2006/5034.pdf
```

### Woo-index

```shell
bin/console --tenant=minvws woo-index:generate
```

Generates a new Woo-index in `/var/woo_index` on local/dev environments. This will be changed to an S3 bucket once we implement minio locally.
Will not cleanup old woo-indexes, for that you need to add `--cleanup`.

### File-storage check

```shell
bin/console --tenant=minvws woopie:check:storage
```

Checks if files in storage can be matched to the database. Outputs number of files and used storage per entity type.
Also reports details for missing files (entities that should have a file, but the file could not be found)

When executed in verbose mode (`-v` flag) the orphaned files will all be listed, otherwise just the count and total size.
Depending on the environment this might result in a lot of output.

### Post deploy

```shell
bin/console --tenant=minvws --id=admin woopie:post-deploy
```

To be executed after each deployment, **once per application**. The command name is registered separately in the admin,
publication API and worker applications, and each one runs its own post-deploy actions, so the `--id` decides what
happens. Locally, `task refresh` runs it for `worker`, `publication_api` and `admin` in turn; `task postdeploy
APPLICATION_ID=<id>` runs a single one.

For the admin application this currently only ensures all required `ContentPage` records exist, but more actions will be
added in the future.

### BatchDownload refresh

```shell
bin/console --tenant=minvws woopie:batchdownload:refresh
```

All BatchDownloads for woo-decisions and inquiries will be refreshed. Any existing batches will be marked as outdated and generation of a new archive is triggered for each one.
The actual generation of the archives will be executed async in message queue workers.

### Move orphaned files

```shell
bin/console --tenant=minvws woopie:move-orphaned-files
```

Moves orphaned files into a separate ("trash") bucket. Orphaned files are files in storage that are no longer related to any of the existing entities.
Asks for the name of the destination bucket during execution.

### Export revoked urls

```shell
bin/console --tenant=minvws woo:export-revoked-urls
```

Exports the urls of revoked documents, meaning documents that have been withdrawn or suspended. Useful for feeding a
cache or CDN purge.

### Inventory refresh

```shell
bin/console --tenant=minvws woopie:inventory:refresh
```

Regenerates all inventories for woo-decisions and inquiries. This command only dispatches the commands, the actual execution will be done using the workers and might take some time to complete.

### Generate database key

```shell
bin/console --tenant=minvws generate:database-key
```

Creates a new key to encrypt database entries.

### Generate auditlog key

```shell
bin/console --tenant=minvws woopie:auditlog:generate-keys
```

Creates a new keypair for auditlog encryption.

### Normalize document grounds

```shell
bin/console --tenant=minvws woopie:normalize-document-grounds <mapping> [--dry-run]
```

Normalize the 'grounds' values for woo-decision documents based on an Excel input file as mapping. The path to that
spreadsheet is required. Use `--dry-run` (`-d`) to see what would change without writing anything.

### Generate publication context

```shell
bin/console --tenant=minvws woopie:document:generate-document-publication-context [--dry-run]
```

Generate the publicationContext of documents that do not have one yet. Use `--dry-run` (`-d`) to preview the effect.

## Development commands

### SQL dump

```shell
bin/console --tenant=minvws woopie:sql:dump
```

Converts doctrine migrations (PHP code) into plain SQL files

### Clean sheet

```shell
bin/console --tenant=minvws woopie:dev:clean-sheet --index <index> [--force] [--users] [--keep-prefixes] [--keep-subjects]
```

Resets data from search index, database, file storage and message queue. `--index` names the Elasticsearch index to
reset. `--force` skips the confirmation prompt, `--users` also resets users, and `--keep-prefixes` / `--keep-subjects`
preserve those. Locally, prefer `task cleansheet`, which passes sensible defaults and clears the MinIO buckets too.

### Content extraction

```shell
bin/console --tenant=minvws woopie:dev:extract-content
```

Extracts content for an entity using Tika and Tesseract.
