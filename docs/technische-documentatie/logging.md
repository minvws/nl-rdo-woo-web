# Logging

<!-- TOC -->
- [Logging](#logging)
  - [Audit logging is minvws-only](#audit-logging-is-minvws-only)
  - [Events](#events)
  - [Payload shape](#payload-shape)
  - [Where audit logs go](#where-audit-logs-go)
  - [Event details](#event-details)
    - [Sign in (091111)](#sign-in-091111)
    - [Failed sign in (091111)](#failed-sign-in-091111)
    - [Sign out (092222)](#sign-out-092222)
    - [Two-factor authentication failed (093333)](#two-factor-authentication-failed-093333)
    - [Create user event (090002)](#create-user-event-090002)
    - [Reset credentials (090003)](#reset-credentials-090003)
    - [Change account data and roles (900101)](#change-account-data-and-roles-900101)
    - [(De)activate user (900104)](#deactivate-user-900104)
    - [Organisation created (090012)](#organisation-created-090012)
    - [Organisation changed (090013)](#organisation-changed-090013)
    - [File upload (080001)](#file-upload-080001)
    - [Dossier deleted (woo\_1)](#dossier-deleted-woo_1)
    - [Publication API sign in](#publication-api-sign-in)
  - [Application logging](#application-logging)
<!-- TOC -->

Audit logging uses the `minvws/audit-logger` package. An event class carries an `EVENT_CODE` and an `EVENT_KEY`;
listeners build an event, attach an actor, a target and a data payload, and hand it to `AuditLogger::log()`.

## Audit logging is minvws-only

Almost all audit logging is implemented in tenant-specific listeners under `tenants/minvws/src/AuditLog/`:

| Listener                  | Covers                                                          |
|---------------------------|-----------------------------------------------------------------|
| `LoginAuditLogger`        | sign out, failed sign in                                        |
| `TwoFactorAuditLogger`    | successful sign in, failed two-factor attempt                   |
| `UserAdminAuditLogger`    | user created, credentials reset, account changed, (de)activated |
| `OrganisationAuditLogger` | organisation created, organisation changed                      |
| `FileScanAuditLogger`     | file upload / virus scan result                                 |

`minfin` and `minbuza` have no `tenants/<tenant>/src/` directory at all, so **they emit none of these events**. Only two
audit events are tenant-agnostic, because they live outside `tenants/`: `dossier_deleted` (in `src/`) and the
Publication API's sign-in events (in `apps/publication_api/`).

## Events

| Event                            | `event_code` | `EVENT_KEY`                    | `action_code` | All tenants |
|----------------------------------|--------------|--------------------------------|---------------|-------------|
| Sign in                          | `091111`     | `user_login`                   | `E`           | no          |
| Failed sign in                   | `091111`     | `user_login`                   | `E`           | no          |
| Sign out                         | `092222`     | `user_logout`                  | `E`           | no          |
| Two-factor authentication failed | `093333`     | `user_login_two_factor_failed` | `E`           | no          |
| Create user                      | `090002`     | `user_created`                 | `C`           | no          |
| Reset credentials                | `090003`     | `reset_credentials`            | `U`           | no          |
| Change account data and roles    | `900101`     | `account_change`               | `U`           | no          |
| (De)activate user                | `900104`     | `account_change`               | `U`           | no          |
| Organisation created             | `090012`     | `organisation_created`         | `C`           | no          |
| Organisation changed             | `090013`     | `organisation_changed`         | `U`           | no          |
| File upload                      | `080001`     | `file_upload`                  | `E`           | no          |
| Dossier deleted                  | `woo_1`      | `dossier_deleted`              | `D`           | yes         |
| Publication API sign in          | `091111`     | `user_login`                   | `E`           | yes         |
| Publication API sign-in failed   | `0000000`    | `log`                          | `E`           | yes         |

`account_change` covers several codes. `AccountChangeLogEvent` declares `EVENT_CODE = '090001'`, but both call sites
override it with `withEventCode()`, so only `900101` and `900104` are ever emitted.

> **Note:** `AccountChangeLogEvent::EVENTCODE_ROLES` (`900102`) exists in the package but is never used. Role changes are
> logged as part of the `900101` event, not separately — see
> [Change account data and roles](#change-account-data-and-roles-900101).

`EVENT_KEY` is the event's key in the audit-logger package. It is **not** a RabbitMQ routing key:
`config/packages/audit_logger.yaml` configures one routing key for every event, from
`AUDITLOG_RABBITMQ_ROUTING_KEY` (default `auditlog`).

## Payload shape

Every event serialises to the same envelope (`GeneralLogEvent::getLogData()`):

| Field                | Description                                                             |
|----------------------|-------------------------------------------------------------------------|
| `user_id`            | Audit ID of the actor                                                   |
| `request`            | Whatever the listener passed to `withData()`                            |
| `created_at`         | Timestamp                                                               |
| `event_code`         | See the table above                                                     |
| `action_code`        | `C`reate, `R`ead, `U`pdate, `D`elete or `E`xecute                       |
| `source`             | Always `woo` — every listener in this project calls `withSource('woo')` |
| `allowed_admin_view` | Whether an administrator may view this record                           |
| `failed`             | Whether the action failed                                               |
| `failed_reason`      | Why it failed, when applicable                                          |

Data passed to `withPiiData()` is kept in a **separate** payload (`getPiiLogData()`), alongside the actor's email
address. It is only written when the relevant `AUDITLOG_*_LOG_PII` setting is enabled, so the personal fields shown in
the examples below can be absent.

## Where audit logs go

Configured in `config/packages/audit_logger.yaml`, per environment:

| Sink              | `prod` | `dev` | `test` |
|-------------------|--------|-------|--------|
| `psr_logger`      | on     | on    | on     |
| `doctrine_logger` | on     | on    | on     |
| `rabbitmq_logger` | on     | off   | off    |
| `file_logger`     | off    | on    | off    |

In development the file sink writes to `AUDITLOG_FILE_PATH`, which defaults to `%kernel.logs_dir%/audit.log`. Because
the log directory is per tenant and per application, that resolves to something like `var/log/minvws/admin/audit.log`.
See [environment-settings.md](environment-settings.md) for the encryption and PII settings.

## Event details

### Sign in (091111)

Triggered when a user signs in with the correct email/password combination and valid OTP code. Emitted from
`TwoFactorAuditLogger::onSuccess`, so it fires on two-factor success rather than on password success.

```json
{
    "user_id": "1edf31fb-35cd-63ec-a120-551869429a24",
    "request": {
        "user_id": "email@example.org",
        "user_name": "John Admin",
        "user_roles": [
            "ROLE_SUPER_ADMIN"
        ]
    },
    "created_at": "2023-09-19T10:05:49.615233Z",
    "event_code": "091111",
    "action_code": "E",
    "source": "woo",
    "allowed_admin_view": false,
    "failed": false,
    "failed_reason": null
}
```

### Failed sign in (091111)

Triggered when either the email address or password is incorrect. The `failed_reason` field will be set to
`invalid_email` or `invalid_password` respectively. Emitted from `LoginAuditLogger::onAuthenticationFailure`.

```json
{
    "user_id": null,
    "request": {
        "user_id": "notexisting@example.org",
        "partial_password_hash": "8c6976e5b5410415",
        "exception_message_key": "Invalid credentials.",
        "exception_message_data": []
    },
    "created_at": "2023-09-19T10:03:39.750387Z",
    "event_code": "091111",
    "action_code": "E",
    "source": "woo",
    "allowed_admin_view": false,
    "failed": true,
    "failed_reason": "invalid_email"
}
```

The same event with `"failed_reason": "invalid_password"` is emitted when the email address exists but the password is
wrong.

`partial_password_hash` are the first 16 characters of the SHA256 of the actual password used to sign in. This way we can
see if a user is trying the same, or different passwords. `exception_message_key` and `exception_message_data` carry the
Symfony authentication exception behind the failure.

### Sign out (092222)

Triggered when a user signs out. Emitted from `LoginAuditLogger::onLogout`.

```json
{
    "user_id": "1edf31fb-35cd-63ec-a120-551869429a24",
    "request": [],
    "created_at": "2023-09-19T10:06:38.047759Z",
    "event_code": "092222",
    "action_code": "E",
    "source": "woo",
    "allowed_admin_view": false,
    "failed": false,
    "failed_reason": null
}
```

### Two-factor authentication failed (093333)

Triggered when the two-factor authentication code is incorrect. Note that when this event is triggered, the user has supplied correct
email/password combination, but the sign-in (091111) event is ONLY triggered when the OTP code is also correct.
Emitted from `TwoFactorAuditLogger::onFailure`.

```json
{
    "user_id": "1edf31fb-35cd-63ec-a120-551869429a24",
    "request": [],
    "created_at": "2023-09-19T10:07:13.069192Z",
    "event_code": "093333",
    "action_code": "E",
    "source": "woo",
    "allowed_admin_view": false,
    "failed": false,
    "failed_reason": null
}
```

### Create user event (090002)

Triggered when a new user has been created, either through the admin or with `woopie:user:create`. When there is no
signed-in actor, as on the command line, the actor is recorded as a synthetic `cli user` / `system@localhost`.

```json
{
    "user_id": "1edf31fb-35cd-63ec-a120-551869429a24",
    "request": {
        "user_id": "1ee56c3c-3c0d-6848-8176-598618a7882c",
        "roles": [
            "ROLE_ADMIN"
        ]
    },
    "created_at": "2023-09-19T10:08:49.794783Z",
    "event_code": "090002",
    "action_code": "C",
    "source": "woo",
    "allowed_admin_view": false,
    "failed": false,
    "failed_reason": null
}
```

### Reset credentials (090003)

Triggered when a user has requested a password or 2fa token reset. As with user creation, a command-line reset records
the synthetic `cli user` actor.

```json
{
    "user_id": "1edf31fb-35cd-63ec-a120-551869429a24",
    "request": {
        "user_id": "1edf3c24-b0cd-6226-aaa2-b3733b07034b",
        "password_reset": true,
        "2fa_reset": true
    },
    "created_at": "2023-09-19T10:09:41.700622Z",
    "event_code": "090003",
    "action_code": "U",
    "source": "woo",
    "allowed_admin_view": false,
    "failed": false,
    "failed_reason": null
}
```

`password_reset` marks a password reset and `2fa_reset` a two-factor reset. Exactly one of them is ever true: the two
resets are separate operations in `UserService`, each dispatching its own event. `woopie:user:reset` does both, so it
emits **two** `090003` events, one per reset.

### Change account data and roles (900101)

Triggered when a user's name, email address or roles change. `UserAdminAuditLogger::onUpdate` emits **one** event for
all of these, with `EVENTCODE_USERDATA` (`900101`).

Role changes are part of the regular data payload. Name and email are passed through `withPiiData()`, so they appear in
the separate PII payload alongside the actor's email address, and only when PII logging is enabled.

```json
{
    "user_id": "1edf31fb-35cd-63ec-a120-551869429a24",
    "request": {
        "user_id": "1edf3c24-b0cd-6226-aaa2-b3733b07034b",
        "old": {
            "roles": [
                "ROLE_ADMIN"
            ]
        },
        "new": {
            "roles": [
                "ROLE_ADMIN",
                "ROLE_ADMIN_USERS"
            ]
        }
    },
    "created_at": "2023-09-19T10:24:37.497512Z",
    "event_code": "900101",
    "action_code": "U",
    "source": "woo",
    "allowed_admin_view": false,
    "failed": false,
    "failed_reason": null
}
```

The accompanying PII payload:

```json
{
    "request": {
        "old": {
            "name": "old name",
            "email": "email@example.org"
        },
        "new": {
            "name": "new name",
            "email": "email@example.org"
        }
    },
    "email": "admin@example.org"
}
```

> There is no separate `900102` event for role changes, even though
> `AccountChangeLogEvent::EVENTCODE_ROLES` (`900102`) is defined in the audit-logger package. Anything consuming these
> logs must read role changes from the `900101` event.

### (De)activate user (900104)

Triggered when a user is either activated or deactivated. Same event class as above, with `EVENTCODE_ACTIVE`.

```json
{
    "user_id": "1edf31fb-35cd-63ec-a120-551869429a24",
    "request": {
        "user_id": "1ee1b0c4-3fcc-6060-9e69-e10598284f03",
        "enabled": false
    },
    "created_at": "2023-09-19T10:25:43.055089Z",
    "event_code": "900104",
    "action_code": "U",
    "source": "woo",
    "allowed_admin_view": false,
    "failed": false,
    "failed_reason": null
}
```

- `enabled` is either `true` or `false`, depending on the new state of the user.

### Organisation created (090012)

Triggered when an organisation is created. Emitted from `OrganisationAuditLogger::onCreated`.

```json
{
    "user_id": "1edf31fb-35cd-63ec-a120-551869429a24",
    "request": {
        "organisation_id": "1ee1b0c4-3fcc-6060-9e69-e10598284f03",
        "name": "Ministerie van Volksgezondheid, Welzijn en Sport",
        "departments": [
            "VWS"
        ]
    },
    "created_at": "2026-08-03T10:25:43.055089Z",
    "event_code": "090012",
    "action_code": "C",
    "source": "woo",
    "allowed_admin_view": false,
    "failed": false,
    "failed_reason": null
}
```

### Organisation changed (090013)

Triggered when an organisation's name or departments change. Only the organisation ID is in the regular payload; the
old and new values are PII data. Fields that did not change are recorded as the literal string `[unchanged]`.

```json
{
    "user_id": "1edf31fb-35cd-63ec-a120-551869429a24",
    "request": {
        "organisation_id": "1ee1b0c4-3fcc-6060-9e69-e10598284f03"
    },
    "created_at": "2026-08-03T10:26:11.101922Z",
    "event_code": "090013",
    "action_code": "U",
    "source": "woo",
    "allowed_admin_view": false,
    "failed": false,
    "failed_reason": null
}
```

The accompanying PII payload:

```json
{
    "request": {
        "old": {
            "name": "old name",
            "departments": "[unchanged]"
        },
        "new": {
            "name": "new name",
            "departments": "[unchanged]"
        }
    },
    "email": "admin@example.org"
}
```

### File upload (080001)

Triggered after an uploaded file has been scanned for viruses, whether the scan passed or failed. Emitted from
`FileScanAuditLogger` in response to `FileScannedEvent`. The actor is only set when there is a signed-in user.

```json
{
    "user_id": "1edf31fb-35cd-63ec-a120-551869429a24",
    "request": {
        "filename": "1729902-208789-PG Nota voor brief aan RIVM.pdf"
    },
    "created_at": "2026-08-03T10:27:02.336104Z",
    "event_code": "080001",
    "action_code": "E",
    "source": "woo",
    "allowed_admin_view": false,
    "failed": true,
    "failed_reason": "Win.Test.EICAR_HDB-1 FOUND"
}
```

`failed` reflects the scan result and `failed_reason` carries the reason reported by ClamAV.

### Dossier deleted (woo_1)

Triggered when a dossier is deleted, from `DeleteDossierHandler`. Unlike the events above this one lives in `src/`, so it
is emitted **for every tenant**. It is a `GeneralLogEvent` subclass with its own event code and key.

The log call sits in a `finally` block, so a failed deletion is recorded too, with `failed` set and `failed_reason`
holding the exception message.

```json
{
    "user_id": "1edf31fb-35cd-63ec-a120-551869429a24",
    "request": {
        "id": "1ee069e4-70b6-6a54-b3a9-95eaef3bc6c6",
        "prefix": "VWS",
        "dossier_number": "534-3444",
        "title": "Nota's over de inkoop van mondkapjes",
        "status": "published"
    },
    "created_at": "2026-08-03T10:28:44.902113Z",
    "event_code": "woo_1",
    "action_code": "D",
    "source": "woo",
    "allowed_admin_view": false,
    "failed": false,
    "failed_reason": null
}
```

### Publication API sign in

The Publication API authenticates clients with mTLS and logs its own events from `PublicationApiAuthenticator`. These
also live outside `tenants/`, so they apply to every tenant.

- A successful authentication emits a `user_login` event (`091111`) whose payload holds the certificate's
  `common_name`.
- A rejected authentication emits a `LoginFailedAuditLogEvent`, a plain `GeneralLogEvent` with `failed` set and a
  `failed_reason` prefixed with `publication_api_`. The four reasons are:

  | `failed_reason`                                   | Cause                                                                |
  |---------------------------------------------------|----------------------------------------------------------------------|
  | `publication_api_invalid_certificate`             | The client certificate is missing or unusable                        |
  | `publication_api_invalid_organization_identifier` | The certificate's organisation identifier does not resolve           |
  | `publication_api_invalid_common_name`             | The certificate has no usable common name                            |
  | `publication_api_common_name_not_whitelisted`     | The common name is not in `*_PUBLICATION_API_SSL_USERNAME_WHITELIST` |

## Application logging

Separately from the audit log, `LoginLogger` (in `apps/admin/`) and `TwoFactorLogger` (in `src/`) write sign-in,
sign-out and two-factor moments to the regular PSR/Monolog logger. Those run for **every** tenant, so seeing a login
line in the application log says nothing about whether an audit record was written. `TwoFactorLogger` also persists a
`LoginActivity` record on success.
