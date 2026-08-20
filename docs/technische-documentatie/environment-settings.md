# Environment settings

<!-- TOC -->
- [Environment settings](#environment-settings)
  - [Tenants and applications](#tenants-and-applications)
  - [Environment variables](#environment-variables)
    - [Global settings](#global-settings)
    - [Tenant routing](#tenant-routing)
    - [Database settings](#database-settings)
    - [Messenger settings](#messenger-settings)
    - [Elastic settings](#elastic-settings)
    - [Redis settings](#redis-settings)
    - [Storage settings](#storage-settings)
    - [Publication API settings](#publication-api-settings)
    - [Feature flags](#feature-flags)
    - [Audit logger settings settings](#audit-logger-settings-settings)
    - [Miscellaneous settings](#miscellaneous-settings)
    - [Development settings](#development-settings)
<!-- TOC -->

This document describes the environment settings that are used by the application.

## Tenants and applications

Many settings exist once per tenant, prefixed with the tenant ID in upper case. In the tables below these are written as
`<TENANT>_`, where `<TENANT>` is one of:

| `<TENANT>` | Tenant ID | Started locally                  |
|------------|-----------|----------------------------------|
| `MINVWS`   | `minvws`  | yes                              |
| `MINFIN`   | `minfin`  | yes                              |
| `MINBUZA`  | `minbuza` | no — built and tested in CI only |

`minbuza` is part of `ALL_TENANTS` but not `LOCAL_TENANTS` in `Taskfile.dist.yml`, so `task up` does not start it.

Separately, one instance runs as exactly one **application**, selected with `APP_ID` (or `--id` on the command line, see
[commands.md](commands.md)). Valid values are `admin`, `public`, `publication_api`, `worker` and `shared`. This replaced
the old `APP_MODE` setting, which no longer exists.

## Environment variables

The following environment variables are used by the application:

### Global settings

| Variable                   | Description                                                   | Default value              |
|----------------------------|---------------------------------------------------------------|----------------------------|
| `APP_ENV`                  | The application environment.                                  | `prod`                     |
| `APP_DEBUG`                | Whether the application is in debug mode.                     | `false`                    |
| `APP_ID`                   | Which application this instance runs as (see above).          | `shared`                   |
| `<TENANT>_APP_SECRET`      | Unique secret for creating signatures (rememberme, CSRF etc). | `null`                     |
| `<TENANT>_SITE_NAME`       | The name of the site. Used only for displaying purposes.      | het Woo Publicatieplatform |
| `<TENANT>_COOKIE_NAME`     | The name of session cookie to use.                            | `<TENANT>_WOOPID`          |
| `SESSION_COOKIE_LIFETIME`  | The lifetime of session cookies.                              | `86400`                    |
| `<TENANT>_TOTP_ISSUER`     | Issuer of the TOTP tokens, used in 2fa for the totp URI       | `localhost`                |
| `<TENANT>_PUBLIC_BASE_URL` | The url of the PUBLIC site                                    | see below                  |
| `PIWIK_ANALYTICS_ID`       | Identification number for Piwik analytics                     | `0`                        |

Cookie names should be prefixed with `__Host-` when running on HTTPS. However, this will break the application when running on
HTTP, for instance, during development.

`<TENANT>_PUBLIC_BASE_URL` is used to build absolute urls where there is no request to derive them from, such as the
sitemap and the Woo-index. It is resolved per tenant into the `public_base_url` container parameter in
`tenants/<tenant>/config/services.yaml`. Its local value comes from the dotenv files, so check `.env` / `.env.dev` for
what your setup uses, and [development_install.md](development_install.md) for how the site is reached.

### Tenant routing

A web request is mapped to a tenant by its host name, in `Shared\TenantResolver`.

| Variable                      | Description                                                                       | Default value |
|-------------------------------|-----------------------------------------------------------------------------------|---------------|
| `HTTP_HOST_TO_TENANT_MAPPING` | Comma-separated list of `host=tenantId` pairs. **Required** for web requests.     | `null`        |
| `<TENANT>_TRUSTED_HOSTS`      | Host names this tenant will serve, passed to Symfony's `trusted_hosts`.           | `null`        |
| `<TENANT>_PUBLIC_HOST`        | Host name of the public site for this tenant, used by the docker setup.           | `null`        |
| `<TENANT>_ADMIN_HOST`         | Host name of the admin (balie) for this tenant, used by the docker setup.         | `null`        |
| `TRUSTED_PROXIES`             | Symfony's `trusted_proxies`, for running behind a reverse proxy or load balancer. | `null`        |

If the incoming `HTTP_HOST` is not present in `HTTP_HOST_TO_TENANT_MAPPING`, the request fails with an explicit error
naming the host, so this is usually the first thing to check when a new environment refuses every request.

### Database settings

| Variable                           | Description                                                                                             | Default value |
|------------------------------------|---------------------------------------------------------------------------------------------------------|---------------|
| `<TENANT>_DATABASE_URL`            | The DSN of the database connection.                                                                     | `null`        |
| `<TENANT>_DATABASE_ENCRYPTION_KEY` | Database at-rest encryption key (generated with `bin/console --tenant=<tenant> generate:database-key`). | `null`        |

### Messenger settings

This is a list of DSNs for the different queues that are used by the application. The default values are for a local RabbitMQ instance, but messenger
can also be configured to use the database, redis or any other
storage system.

| Variable                               | Description                            | Default value                                         |
|----------------------------------------|----------------------------------------|-------------------------------------------------------|
| `<TENANT>_HIGH_TRANSPORT_DSN`          | DSN for high priority work             | `amqp://guest:guest@localhost:5672/%2f/high`          |
| `<TENANT>_INGESTOR_TRANSPORT_DSN`      | DSN for ingesting documents            | `amqp://guest:guest@localhost:5672/%2f/ingestor`      |
| `<TENANT>_ESUPDATER_TRANSPORT_DSN`     | DSN for updates on the elastic search  | `amqp://guest:guest@localhost:5672/%2f/es_updates`    |
| `<TENANT>_GLOBAL_TRANSPORT_DSN`        | DSN for global household functionality | `amqp://guest:guest@localhost:5672/%2f/global`        |
| `<TENANT>_API_DOCUMENTS_TRANSPORT_DSN` | DSN for API documents functionality    | `amqp://guest:guest@localhost:5672/%2f/api_documents` |

These settings are in order of priority. So if there is a message in the high priority queue, it will be processed before any other messages.

### Elastic settings

Settings to connect to the elastic search cluster. If user/pass/mtls settings are empty, no authentication will be used.

| Variable                                | Description                              | Default value           |
|-----------------------------------------|------------------------------------------|-------------------------|
| `<TENANT>_ELASTICSEARCH_HOST`           | Url to cluster                           | `http://127.0.0.1:9200` |
| `<TENANT>_ELASTICSEARCH_USER`           | Username for authentication (if any)     | `null`                  |
| `<TENANT>_ELASTICSEARCH_PASS`           | Password for authentication (if any)     | `null`                  |
| `<TENANT>_ELASTICSEARCH_MTLS_CERT_PATH` | Certificate path for mTLS authentication | `null`                  |
| `<TENANT>_ELASTICSEARCH_MTLS_KEY_PATH`  | Key path for mTLS authentication         | `null`                  |
| `<TENANT>_ELASTICSEARCH_MTLS_CA_PATH`   | CA path for mTLS authentication          | `null`                  |

### Redis settings

Redis is used for storing cached information about documents. This is used for example for the document content, so that it doesn't have to be
extracted when ingesting the same document multiple times. It will also store the sessions of the users.

| Variable                        | Description                              | Default value            |
|---------------------------------|------------------------------------------|--------------------------|
| `<TENANT>_REDIS_URL`            | URL to redis                             | `redis://localhost:6379` |
| `<TENANT>_REDIS_TLS_CAFILE`     | CA path for mTLS authentication          | `null`                   |
| `<TENANT>_REDIS_TLS_LOCAL_CERT` | Certificate path for mTLS authentication | `null`                   |
| `<TENANT>_REDIS_TLS_LOCAL_PK`   | Key path for mTLS authentication         | `null`                   |

Locally this is served by KeyDB rather than Redis itself; see [keydb-mirror-manual.md](keydb-mirror-manual.md).

### Storage settings

Storage settings defines how documents, pages and thumbnails are stored and retrieved. There are two options: local and aws, but it is also possible
to create your own storage adapter since internally it will be using flysystem.

| Variable                                  | Description                                                           | Default value      |
|-------------------------------------------|-----------------------------------------------------------------------|--------------------|
| `STORAGE_DOCUMENT_ADAPTER`                | Which adapter to use for document storage (`aws` or `local`)          | `local`            |
| `STORAGE_BATCH_ADAPTER`                   | Which adapter to use for archive storage (`aws` or `local`)           | `local`            |
| `STORAGE_WOO_INDEX_ADAPTER`               | Which adapter to use for WooIndex sitemaps storage (`aws` or `local`) | `local`            |
| `STORAGE_UPLOAD_ADAPTER`                  | Which adapter to use for upload storage (`aws` or `local`)            | `local`            |
| `STORAGE_ASSETS_ADAPTER`                  | Which adapter to use for assets storage (`aws` or `local`)            | `local`            |
| `<TENANT>_STORAGE_MINIO_REGION`           | The AWS/Minio region to use                                           | `eu-west-1`        |
| `<TENANT>_STORAGE_MINIO_ENDPOINT`         | The AWS/Minio endpoint                                                | ``                 |
| `<TENANT>_STORAGE_MINIO_ACCESS_KEY`       | The AWS/Minio access key                                              | ``                 |
| `<TENANT>_STORAGE_MINIO_SECRET_KEY`       | The AWS/Minio secret key                                              | ``                 |
| `<TENANT>_STORAGE_MINIO_DOCUMENT_BUCKET`  | Bucket for document storage                                           | `doc-bucket`       |
| `<TENANT>_STORAGE_MINIO_BATCH_BUCKET`     | Bucket for archive storage                                            | `batch-bucket`     |
| `<TENANT>_STORAGE_MINIO_WOO_INDEX_BUCKET` | Bucket for WooIndex sitemap storage                                   | `woo-index-bucket` |
| `<TENANT>_STORAGE_MINIO_UPLOAD_BUCKET`    | Bucket for temporary upload storage                                   | `upload-bucket`    |
| `<TENANT>_STORAGE_MINIO_ASSETS_BUCKET`    | Bucket for assets storage (like the department logo)                  | `assets-bucket`    |

Note that we are using Minio as a S3 compatible storage system. This means that you can also use AWS S3 as a storage system.

### Publication API settings

The Publication API authenticates its clients with mTLS. See [logging.md](logging.md) for the audit events these
settings can cause.

| Variable                                               | Description                                                       | Default value |
|--------------------------------------------------------|-------------------------------------------------------------------|---------------|
| `<TENANT>_PUBLICATION_API_SSL_ORGANIZATION_IDENTIFIER` | Organisation identifier the client certificate must present.      | `null`        |
| `<TENANT>_PUBLICATION_API_SSL_USERNAME_WHITELIST`      | Client certificate common names that are allowed to authenticate. | `null`        |

### Feature flags

| Variable                                               | Description                                                            | Default value |
|--------------------------------------------------------|------------------------------------------------------------------------|---------------|
| `HAS_FEATURE_PUBLICATION_V1_API`                       | Enables the Publication API (`apps/publication_api`).                  | `false`       |
| `HAS_FEATURE_DRAFT_DECISION`                           | Enables the DraftDecision publication type.                            | `false`       |
| `HAS_FEATURE_WOO_GPT`                                  | Enables the `/woo-gpt` pages on the public site.                       | `false`       |
| `ENABLE_UPDATE_PUBLISHED_DOSSIER_VIA_API`              | Allows the Publication API to update already-published dossiers.       | `false`       |
| `ENABLE_UPLOAD_DOCUMENT_FOR_PUBLISHED_DOSSIER_VIA_API` | Allows the Publication API to upload documents for published dossiers. | `false`       |

See [dossier-types.md](dossier-types.md) for how a new publication type is developed behind a feature flag.

### Audit logger settings settings

There settings configure the audit logger system. It is used to log all actions that are performed by users. It is possible
to configure multiple loggers, but you cannot enable/disable them directly through the env vars (this is mostly a symfony limitation).

The audit logger system is configured to use the following loggers:

- PSR/Monolog logger: any PSR compatible logger can be used to send out audit logs, for instance: monolog.
- Doctrine logger: logs audit logs to the database.
- RabbitMQ logger: logs audit logs to rabbitmq.
- File logger: logs audit logs to a file.

In order to configure them, you have to change the `config/packages/audit_logger.yaml` file.

To generate the keys for the encryption, you can use the following command:

```bash
  php bin/console --tenant=minvws woopie:auditlog:generate-keys
```

and copy the output to the env vars.

> Note: in order to log to rabbitMQ, you need to have rabbitMQ configured through the `<TENANT>_RABBITMQ_URL` env var
> (see [Miscellaneous settings](#miscellaneous-settings)). The rabbitMQ sink is disabled in the `dev` and `test`
> environments; see [logging.md](logging.md) for which sinks are active where.

The following settings are available:

| Variable                        | Description                                      | Default value                 |
|---------------------------------|--------------------------------------------------|-------------------------------|
| `AUDITLOG_ENCRYPTION_PUB_KEY`   | The public key for encrypting audit data         | `null`                        |
| `AUDITLOG_ENCRYPTION_PRIV_KEY`  | The private key for encrypting audit data        | `null`                        |
| `AUDITLOG_PSR_ENCRYPTED`        | True when PSR logging should encrypted           | `false`                       |
| `AUDITLOG_PSR_LOG_PII`          | True when PII data should be logged as well      | `false`                       |
| `AUDITLOG_DOCTRINE_ENCRYPTED`   | True when database logging is encrypted          | `false`                       |
| `AUDITLOG_DOCTRINE_LOG_PII`     | True when PII data should be logged as well      | `false`                       |
| `AUDITLOG_RABBITMQ_ENCRYPTED`   | True when rabbitmq logging is encrypted          | `false`                       |
| `AUDITLOG_RABBITMQ_LOG_PII`     | True when PII data should be logged as well      | `false`                       |
| `AUDITLOG_RABBITMQ_ROUTING_KEY` | The routing key to use for logging with rabbitmq | `auditlog`                    |
| `AUDITLOG_FILE_ENCRYPTED`       | True when the file logging should be encrypted   | `false`                       |
| `AUDITLOG_FILE_LOG_PII`         | True when PII data should be logged as well      | `false`                       |
| `AUDITLOG_FILE_PATH`            | File path to store the file audit logging        | `%kernel.logs_dir%/audit.log` |

### Miscellaneous settings

| Variable                                | Description                                                                                                            | Default value                        |
|-----------------------------------------|------------------------------------------------------------------------------------------------------------------------|--------------------------------------|
| `<TENANT>_RABBITMQ_STATS_URL`           | Url to the RabbitMQ management interface for statistics. This needs to have the management plugin enabled on rabbitmq. | `http://guest:guest@127.0.0.1:15672` |
| `<TENANT>_RABBITMQ_URL`                 | Default rabbitMQ entrypoint. This is used for the audit logger functionality.                                          | `amqp://guest:guest@localhost:5672`  |
| `TIKA_HOST`                             | Url on which Tika is running. Used for the workers that extract content through tika                                   | `http://127.0.0.1:9998`              |
| `CLAM_AV_ADDRESS`                       | This is used for validating uploaded files.                                                                            | `tcp://clamav:3310`                  |
| `CLAM_AV_MAX_FILESIZE`                  | Max filesize to be scanned in bytes                                                                                    | `1073741824` (1GiB)                  |
| `DOCUMENT_PAGE_LIMIT`                   | Maximum number of pages to process per document. `0` means no limit.                                                   | `0`                                  |
| `LIMIT_TOTAL_DOCUMENT_FILE_SIZE_IN_GIB` | Maximum combined size of the document files in one dossier, in GiB.                                                    | `50`                                 |
| `LOG_LEVEL`                             | Minimum Monolog level to record.                                                                                       | `debug`                              |
| `MAILER_DSN`                            | Symfony Mailer DSN, used for two-factor codes sent by email.                                                           | `null`                               |

### Development settings

These only apply when `APP_ENV=dev` and are set through `.env.dev.local`.

| Variable            | Description                                                                                      | Default value |
|---------------------|--------------------------------------------------------------------------------------------------|---------------|
| `ENABLE_TOOLBAR`    | Whether the Symfony debug toolbar is rendered. The E2E tasks set this to `false`.                | `true`        |
| `VAR_DUMPER_SERVER` | Address of a `server:dump` listener, so `dump()` output goes there instead of into the response. | `null`        |

Host ports for the docker setup are also configurable, so a second checkout can run alongside the first. They are
defined with defaults in `compose.yml`: `DOCKER_MINVWS_PUBLIC_PORT`, `DOCKER_MINVWS_ADMIN_PORT`,
`DOCKER_MINVWS_PUBLICATION_API_HTTPS_PORT` and their `MINFIN` counterparts, plus `DOCKER_ELASTICSEARCH_PORT`,
`DOCKER_REDIS_PORT`, `DOCKER_RABBITMQ_PORT`, `DOCKER_RABIITMQ_MANAGEMENT_PORT` and `DOCKER_POSTGRES_PORT`. See
[development_install.md](development_install.md) for the defaults.
