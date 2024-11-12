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
