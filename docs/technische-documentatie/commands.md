# Woo Platform

<!-- TOC -->
- [Woo Platform](#woo-platform)
  - [Woo platform console commands](#woo-platform-console-commands)
    - [Production check](#production-check)
    - [Data / Document generation](#data--document-generation)
    - [Index management](#index-management)
    - [Ingestion](#ingestion)
    - [User management](#user-management)
    - [Document location](#document-location)
    - [Woo-index](#woo-index)
    - [File-storage check](#file-storage-check)
    - [BatchDownload refresh](#batchdownload-refresh)
<!-- TOC -->

## Woo platform console commands

This is a list of all console commands available for the Woo platform.

### Production check

```shell
bin/console woopie:check:production
```

This command checks if the current environment is ready for production. It will mainly check
installed tools and extensions that are needed to run the project.

### Data / Document generation

```shell
bin/console woopie:generate:documents
```

This command will generate a large set of documents (database only) for testing purposes.

### Index management

```shell
bin/console woopie:index:alias <index> <alias>
bin/console woopie:index:create <index> <version-number>
bin/console woopie:index:delete <index>
```

Creates or deletes an elasticsearch index. It is also possible to create an alias for an index.

### Ingestion

```shell
bin/console woopie:ingest:document <documentNr>
bin/console woopie:ingest:dossier <dossierNr>
```

Starts the ingestion of a single document or dossier. The document or dossier number is required.
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

### BatchDownload refresh

```shell
bin/console woopie:batchdownload:refresh
```

All BatchDownloads for woo-decisions and inquiries will be refreshed. Any existing batches will be marked as outdated and generation of a new archive is triggered for each one.
The actual generation of the archives will be executed async in message queue workers.

### Upload cleanup

```shell
bin/console woopie:cron:clean-uploads
```

Cleans up outdated or orphaned upload entities and files. Should be executed at least daily.

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
