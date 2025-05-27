# Environment settings

<!-- TOC -->
- [Environment settings](#environment-settings)
  - [Environment variables](#environment-variables)
    - [Global settings](#global-settings)
    - [Database settings](#database-settings)
    - [Messenger settings](#messenger-settings)
    - [Elastic settings](#elastic-settings)
    - [Redis settings](#redis-settings)
    - [Storage settings](#storage-settings)
    - [Audit logger settings settings](#audit-logger-settings-settings)
    - [Miscellaneous settings](#miscellaneous-settings)
<!-- TOC -->

This document describes the environment settings that are used by the application.

## Environment variables

The following environment variables are used by the application:

### Global settings

| Variable             | Description                                                   | Default value           |
| -------------------- | ------------------------------------------------------------- | ----------------------- |
| `APP_ENV`            | The application environment.                                  | `prod`                  |
| `APP_DEBUG`          | Whether the application is in debug mode.                     | `false`                 |
| `APP_SECRET`         | Unique secret for creating signatures (rememberme, CSRF etc). | `null`                  |
| `SITE_NAME`          | The name of the site. Used only for displaying purposes.      | het publicatieplatform  |
| `COOKIE_NAME`        | The name of session cookie to use.                            | `WOOPID`                |
| `TOTP_ISSUER`        | Issuer of the TOTP tokens, used in 2fa for the totp URI       | `localhost`             |
| `APP_MODE`           | Application mode (see below)                                  | `BOTH`                  |
| `PUBLIC_BASE_URL`    | The url of the FRONTEND site                                  | `http://localhost:8000` |
| `PIWIK_ANALYTICS_ID` | Identification number for Piwik analytics                     | `0`                     |

Cookie names should be prefixed with `__Host-` when running on HTTPS. However, this will break the application when running on
HTTP, for instance, during development.

The `APP_MODE` defines how the given instance behaves. It can be `BOTH`, `FRONTEND` or `BACKEND`. When `BOTH` is used, the instance
can run both as a frontend and a backend. If `FRONTEND` is used, the instance will only run as a frontend and the admin panel will not be available.
When `BACKEND` is used, the instance will only run as a backend and the frontend will not be available.

### Database settings

| Variable                  | Description                                                                              | Default value |
| ------------------------- | ---------------------------------------------------------------------------------------- | ------------- |
| `DATABASE_URL`            | The DSN of the database connection.                                                      | `null`        |
| `DATABASE_ENCRYPTION_KEY` | Database at-rest encryption key (generated with "php bin/console generate:database-key") | `null`        |

### Messenger settings

This is a list of DSNs for the different queues that are used by the application. The default values are for a local RabbitMQ instance, but messenger
can also be configured to use the database, redis or any other
storage system.

| Variable                  | Description                            | Default value                                      |
| ------------------------- | -------------------------------------- | -------------------------------------------------- |
| `HIGH_TRANSPORT_DSN`      | DSN for high priority work             | `amqp://guest:guest@localhost:5672/%2f/high`       |
| `INGESTOR_TRANSPORT_DSN`  | DSN for ingesting documents            | `amqp://guest:guest@localhost:5672/%2f/ingestor`   |
| `ESUPDATER_TRANSPORT_DSN` | DSN for updates on the elastic search  | `amqp://guest:guest@localhost:5672/%2f/es_updates` |
| `GLOBAL_TRANSPORT_DSN`    | DSN for global household functionality | `amqp://guest:guest@localhost:5672/%2f/global`     |

These settings are in order of priority. So if there is a message in the high priority queue, it will be processed before any other messages.

### Elastic settings

Settings to connect to the elastic search cluster. If user/pass/mtls settings are empty, no authentication will be used.

| Variable                       | Description                              | Default value           |
| ------------------------------ | ---------------------------------------- | ----------------------- |
| `ELASTICSEARCH_HOST`           | Url to cluster                           | `http://127.0.0.1:9200` |
| `ELASTICSEARCH_USER`           | Username for authentication (if any)     | `null`                  |
| `ELASTICSEARCH_PASS`           | Password for authentication (if any)     | `null`                  |
| `ELASTICSEARCH_MTLS_CERT_PATH` | Certificate path for mTLS authentication | `null`                  |
| `ELASTICSEARCH_MTLS_KEY_PATH`  | Key path for mTLS authentication         | `null`                  |
| `ELASTICSEARCH_MTLS_CA_PATH`   | CA path for mTLS authentication          | `null`                  |

### Redis settings

Redis is used for storing cached information about documents. This is used for example for the document content, so that it doesn't have to be
extracted when ingesting the same document multiple times. It will also store the sessions of the users.

| Variable               | Description                              | Default value            |
| ---------------------- | ---------------------------------------- | ------------------------ |
| `REDIS_URL`            | URL to redis                             | `redis://localhost:6379` |
| `REDIS_TLS_CAFILE`     | CA path for mTLS authentication          | `null`                   |
| `REDIS_TLS_LOCAL_CERT` | Certificate path for mTLS authentication | `null`                   |
| `REDIS_TLS_LOCAL_PK`   | Key path for mTLS authentication         | `null`                   |

### Storage settings

Storage settings defines how documents, pages and thumbnails are stored and retrieved. There are two options: local and aws, but it is also possible
to create your own storage adapter since internally it will be using flysystem.

| Variable                         | Description                                                           | Default value      |
|----------------------------------|-----------------------------------------------------------------------|--------------------|
| `STORAGE_DOCUMENT_ADAPTER`       | Which adapter to use for document storage (`aws` or `local`)          | `local`            |
| `STORAGE_THUMBNAIL_ADAPTER`      | Which adapter to use for thumbnail storage (`aws` or `local`)         | `local`            |
| `STORAGE_BATCH_ADAPTER`          | Which adapter to use for archive storage (`aws` or `local`)           | `local`            |
| `STORAGE_WOO_INDEX_ADAPTER`      | Which adapter to use for WooIndex sitemaps storage (`aws` or `local`) | `local`            |
| `STORAGE_UPLOAD_ADAPTER`         | Which adapter to use for upload storage (`aws` or `local`)            | `local`            |
| `STORAGE_ASSETS_ADAPTER`         | Which adapter to use for assets storage (`aws` or `local`)            | `local`            |
| `STORAGE_MINIO_REGION`           | The AWS/Minio region to use                                           | `eu-west-1`        |
| `STORAGE_MINIO_ENDPOINT`         | The AWS/Minio endpoint                                                | ``                 |
| `STORAGE_MINIO_ACCESS_KEY`       | The AWS/Minio access key                                              | ``                 |
| `STORAGE_MINIO_SECRET_KEY`       | The AWS/Minio secret key                                              | ``                 |
| `STORAGE_MINIO_DOCUMENT_BUCKET`  | Bucket for document storage                                           | `doc-bucket`       |
| `STORAGE_MINIO_THUMBNAIL_BUCKET` | Bucket for thumbnail storage                                          | `thumb-bucket`     |
| `STORAGE_MINIO_BATCH_BUCKET`     | Bucket for archive storage                                            | `batch-bucket`     |
| `STORAGE_MINIO_WOO_INDEX_BUCKET` | Bucket for WooIndex sitemap storage                                   | `woo-index-bucket` |
| `STORAGE_MINIO_UPLOAD_BUCKET`    | Bucket for temporary upload storage                                   | `upload-bucket`    |
| `STORAGE_MINIO_ASSETS_BUCKET`    | Bucket for assets storage (like the department logo)                  | `assets-bucket`    |

Note that we are using Minio as a S3 compatible storage system. This means that you can also use AWS S3 as a storage system.

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
  php bin/console woopie:auditlog:generate-keys
```

and copy the output to the env vars.

> Note: in order to log to rabbitMQ, you need to have the rabbitMQ configured through the RABBITMQ_URL env var (see miscellanious settings).

The following settings are available:

| Variable                        | Description                                      | Default value                 |
| ------------------------------- | ------------------------------------------------ | ----------------------------- |
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

| Variable               | Description                                                                                                            | Default value                        |
| ---------------------- | ---------------------------------------------------------------------------------------------------------------------- | ------------------------------------ |
| `RABBITMQ_STATS_URL`   | Url to the RabbitMQ management interface for statistics. This needs to have the management plugin enabled on rabbitmq. | `http://guest:guest@127.0.0.1:15672` |
| `TIKA_HOST`            | Url on which Tika is running. Used for the workers that extract content through tika                                   | `http://127.0.0.1:9998`              |
| `RABBITMQ_URL`         | Default rabbitMQ entrypoint. This is used for the audit logger functionality.                                          | `amqp://guest:guest@localhost:5672`  |
| `CLAM_AV_ADDRESS`      | This is used for validating uploaded files.                                                                            | `tcp://clamav:3310`                  |
| `CLAM_AV_MAX_FILESIZE` | Max filesize to be scanned in bytes                                                                                    | `1073741824` (1GiB)                  |
