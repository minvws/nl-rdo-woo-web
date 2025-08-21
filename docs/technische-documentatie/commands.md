# Woo Platform commands

<!-- TOC -->
- [Woo Platform commands](#woo-platform-commands)
  - [Cron commands](#cron-commands)
    - [Upload cleanup](#upload-cleanup)
    - [Archives cleanup](#archives-cleanup)
    - [Document fileset cleanup](#document-fileset-cleanup)
    - [Inventory processrun cleanup](#inventory-processrun-cleanup)
    - [Publish scheduled dossiers](#publish-scheduled-dossiers)
  - [Console commands](#console-commands)
    - [Production check](#production-check)
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
    - [Cleanup worker status](#cleanup-worker-status)
    - [Inventory refresh](#inventory-refresh)
    - [Generate database key](#generate-database-key)
    - [Generate auditlog key](#generate-auditlog-key)
    - [Normalize document grounds](#normalize-document-grounds)
  - [Development commands](#development-commands)
    - [SQL dump](#sql-dump)
    - [Clean sheet](#clean-sheet)
    - [Content extraction](#content-extraction)
<!-- TOC -->

## Cron commands

### Upload cleanup

```shell
bin/console woopie:cron:clean-uploads
```

Cleans up outdated or orphaned upload entities and files. Should be executed at least daily.

### Archives cleanup

```shell
bin/console woopie:cron:clean-archives
```

Cleans up expired archives for woo-decisions. Should be executed at least daily.

### Document fileset cleanup

```shell
bin/console woopie:cron:clean-document-file-sets
```

Cleans up DocumentFileSet entities and related files that are no longer needed. Should be executed at least daily.

### Inventory processrun cleanup

```shell
bin/console woopie:cron:clean-inventory-process-run
```

Marks expired inventory process runs as failed. Should be executed at least daily.

### Publish scheduled dossiers

```shell
bin/console woopie:cron:publisher
```

Publish dossiers when their publication date is reached. Should be executed at least daily, shortly after midnight.

## Console commands

This is a list of all console commands available for the Woo platform.

### Production check

```shell
bin/console woopie:check:production
```

This command checks if the current environment is ready for production. It will mainly check
installed tools and extensions that are needed to run the project.

### Page check

```shell
bin/console woopie:page:check
```

Checks if there are pages that are not yet indexed in ElasticSearch.

### Index management

```shell
bin/console woopie:index:alias <index> <alias>
bin/console woopie:index:create <index> <version-number>
bin/console woopie:index:delete <index>
```

Creates or deletes an elasticsearch index. It is also possible to create an alias for an index.

### Ingestion

```shell
bin/console woopie:ingest:dossier <dossierNr>
```

Starts the ingestion of a dossier. The dossier number input is required.
In order for this to run, you must have the workers/consumers running. This can be done with

```shell
bin/console messenger:consume -d
```

### User management

```shell
bin/console woopie:user:create "<email>" "<fullname>" --admin
```

Creates a new user with the given email address and name. When the `--admin` flag is given, the user
is automatically granted the admin role.

This command will generate a random password, 2fa token and 2fa recovery tokens. The password must be
changed on first login and is only visible during this creation period.

```shell
bin/console woopie:user:view  <email>
```

This command will view the details of a user with the given email address. Everything EXCEPT
the password can be viewed.

```shell
bin/console woopie:user:reset  <email>
```

This will reset the password, 2fa token and 2fa recovery codes for the given user. The user must
change the password after the first login. Note that it is NOT possible to view the password once
created.

### Document location

```shell
bin/console woopie:where <url>
```

If you enter a URL, this command will return the location of the given file in the local storage.
This is needed since there is no direct relation between the URL and the local storage location.

Example:

```shell
$ bin/console woopie:where https://localhost:8000/dossier/VWS-534-3444/document/TEST111-5034
Matched /dossier/VWS-534-3444/document/TEST111-5034 to app_document_detail
Document : 1ee069e4-70b6-6a54-b3a9-95eaef3bc6c6
Filename : 1729902-208789-PG Nota voor brief aan RIVM betreft opdrachtbrief wetenschappelijk adviespanel COVID vaccin.pdf.pdf
Path     : /dd/19ba3eda7104b4041da826c5a8f9562abd548b3aa1968ca30112d4ebdc2006/5034.pdf
```

### Woo-index

```shell
bin/console woo-index:generate
```

Generates a new Woo-index in `/var/woo_index` on local/dev environments. This will be changed to an S3 bucket once we implement minio locally.
Will not cleanup old woo-indexes, for that you need to add `--cleanup`.

### File-storage check

```shell
bin/console woopie:check:storage
```

Checks if files in storage can be matched to the database. Outputs number of files and used storage per entity type.
Also reports details for missing files (entities that should have a file, but the file could not be found)

When executed in verbose mode (`-v` flag) the orphaned files will all be listed, otherwise just the count and total size.
Depending on the environment this might result in a lot of output.

### Post deploy

```shell
bin/console woopie:post-deploy
```

To be executed after each deployment. Currently only ensures all required `ContentPage` records exist, but more actions will be added in the future.

### BatchDownload refresh

```shell
bin/console woopie:batchdownload:refresh
```

All BatchDownloads for woo-decisions and inquiries will be refreshed. Any existing batches will be marked as outdated and generation of a new archive is triggered for each one.
The actual generation of the archives will be executed async in message queue workers.

### Move orphaned files

```shell
bin/console woopie:move-orphaned-files
```

Moves orphaned files into a separate ("trash") bucket. Orphaned files are files in storage that are no longer related to any of the existing entities.
Asks for the name of the destination bucket during execution.

### Cleanup worker status

```shell
bin/console woopie:cron:clean-worker-status
```

Removes all WorkerStats records with a `created_at` more than one week old.

### Inventory refresh

```shell
bin/console woopie:inventory:refresh
```

Regenerates all inventories for woo-decisions and inquiries. This command only dispatches the commands, the actual execution will be done using the workers and might take some time to complete.

### Generate database key

```shell
bin/console generate:database-key
```

Creates a new key to encrypt database entries.

### Generate auditlog key

```shell
bin/console woopie:auditlog:generate-keys
```

Creates a new keypair for auditlog encryption.

### Normalize document grounds

```shell
bin/console woopie:normalize-document-grounds
```

Normalize the 'grounds' values for woo-decision documents based on an Excel input file as mapping.

## Development commands

### SQL dump

```shell
bin/console woopie:sql:dump
```

Converts doctrine migrations (PHP code) into plain SQL files

### Clean sheet

```shell
bin/console woopie:dev:clean-sheet
```

Resets data from search index, database, file storage and message queue.

### Content extraction

```shell
bin/console woopie:dev:extract-content
```

Extracts content for an entity using Tika and Tesseract.
