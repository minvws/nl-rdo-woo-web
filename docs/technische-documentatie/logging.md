# Logging

<!-- TOC -->
- [Logging](#logging)
  - [Event details](#event-details)
    - [Sign in (091111)](#sign-in-091111)
    - [Failed sign in (091111)](#failed-sign-in-091111)
    - [Sign out (092222)](#sign-out-092222)
    - [Two-factor authentication failed (093333)](#two-factor-authentication-failed-093333)
    - [Create user event (090002)](#create-user-event-090002)
    - [Reset credentials (090003)](#reset-credentials-090003)
    - [Change account data (900101)](#change-account-data-900101)
    - [Change roles (900102)](#change-roles-900102)
    - [(De)activate user (900104)](#deactivate-user-900104)
<!-- TOC -->

We use the following events in Woopie:

| Event                             | `event_code` (string)   | Routing key (prefixed with `[app].[env].`[^1])   |
|-----------------------------------|-------------------------|--------------------------------------------------|
| Sign in                           | `091111`                | `user_login`                                     |
| Failed sign in                    | `091111`                | `user_login`                                     |
| Sign out                          | `092222`                | `user_logout`                                    |
| TwoFactor authentication failed   | `093333`                | `user_login_two_factor_failed`                   |
| --------------------------------- | ----------------------- | ------------------------------------------------ |
| Create user event                 | `090002`                | `user_created`                                   |
| Reset credentials                 | `090003`                | `reset_credentials`                              |
| Change account data               | `900101`                | `account_change`                                 |
| Change roles                      | `900102`                | `account_change`                                 |
| (De)activate user                 | `900104`                | `activate_account`                               |
| --------------------------------- | ----------------------- | ------------------------------------------------ |

[^1]: The routing key is the same as the `event_code` but with dots instead of underscores.

## Event details

### Sign in (091111)

Triggered when a user signs in with the correct email/password combination and valid OTP code.

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
    "created_at": {
        "date": "2023-09-19 10:05:49.615233",
        "timezone_type": 3,
        "timezone": "Europe\/Berlin"
    },
    "event_code": "091111",
    "action_code": "E",
    "allowed_admin_view": false,
    "failed": false,
    "failed_reason": null
}
```

### Failed sign in (091111)

Triggered when either the email address or password is incorrect. The `failed_reason` field will be set to `invalid_email` or `invalid_password`
respectively.

```json
{
    "user_id": null,
    "request": {
        "user_id": "notexisting@example.org",
        "partial_password_hash": "8c6976e5b5410415"
    },
    "created_at": {
        "date": "2023-09-19 10:03:39.750387",
        "timezone_type": 3,
        "timezone": "Europe\/Berlin"
    },
    "event_code": "091111",
    "action_code": "E",
    "allowed_admin_view": false,
    "failed": true,
    "failed_reason": "invalid_email"
}
```

```json
{
    "user_id": null,
    "request": {
        "user_id": "existing@example.org",
        "partial_password_hash": "5e0ece63e5003380"
    },
    "created_at": {
        "date": "2023-09-19 10:05:06.726454",
        "timezone_type": 3,
        "timezone": "Europe\/Berlin"
    },
    "event_code": "091111",
    "action_code": "E",
    "allowed_admin_view": false,
    "failed": true,
    "failed_reason": "invalid_password"
}
```

`partial_password_hash` are the first 16 bytes of the SHA256 of the actual password used to sign in. This way we can see if a user is trying
the same, or different passwords.

### Sign out (092222)

Triggered when a user signs out.

```json
{
    "user_id": "1edf31fb-35cd-63ec-a120-551869429a24",
    "request": [],
    "created_at": {
        "date": "2023-09-19 10:06:38.047759",
        "timezone_type": 3,
        "timezone": "Europe\/Berlin"
    },
    "event_code": "092222",
    "action_code": "E",
    "allowed_admin_view": false,
    "failed": false,
    "failed_reason": null
}
```

### Two-factor authentication failed (093333)

Triggered when the two-factor authentication code is incorrect. Note that when this event is triggered, the user has supplied correct
email/password combination, but the sign-in (091111) event is ONLY triggered when the OTP code is also correct.

```json
{
    "user_id": "1edf31fb-35cd-63ec-a120-551869429a24",
    "request": [],
    "created_at": {
        "date": "2023-09-19 10:07:13.069192",
        "timezone_type": 3,
        "timezone": "Europe\/Berlin"
    },
    "event_code": "093333",
    "action_code": "E",
    "allowed_admin_view": false,
    "failed": false,
    "failed_reason": null
}
```

### Create user event (090002)

Triggered when a new user has been created.

```json
{
    "user_id": "1edf31fb-35cd-63ec-a120-551869429a24",
    "request": {
        "user_id": "1ee56c3c-3c0d-6848-8176-598618a7882c",
        "roles": [
            "ROLE_ADMIN"
        ]
    },
    "created_at": {
        "date": "2023-09-19 10:08:49.794783",
        "timezone_type": 3,
        "timezone": "Europe\/Berlin"
    },
    "event_code": "090002",
    "action_code": "C",
    "allowed_admin_view": false,
    "failed": false,
    "failed_reason": null
}
```

### Reset credentials (090003)

Triggered when a user has requested a password or 2fa token reset.

```json
{
    "user_id": "1edf31fb-35cd-63ec-a120-551869429a24",
    "request": {
        "user_id": "1edf3c24-b0cd-6226-aaa2-b3733b07034b",
        "password_reset": true,
        "2fa_reset": true
    },
    "created_at": {
        "date": "2023-09-19 10:09:41.700622",
        "timezone_type": 3,
        "timezone": "Europe\/Berlin"
    },
    "event_code": "090003",
    "action_code": "U",
    "allowed_admin_view": false,
    "failed": false,
    "failed_reason": null
}
```

When `password_reset` is true, the password is reset. If `2fa_reset` is true, the 2fa token is reset. Both can be set to true.

### Change account data (900101)

Triggered when account data has been changed.

```json
{
    "user_id": "1edf31fb-35cd-63ec-a120-551869429a24",
    "request": {
        "user_id": "1ee1b0c4-3fcc-6060-9e69-e10598284f03",
        "old": {
            "name": "old name",
            "email": "email@example.org"
        },
        "new": {
            "name": "new name",
            "email": "email@example.org"
        }
    },
    "created_at": {
        "date": "2023-09-19 10:23:25.391582",
        "timezone_type": 3,
        "timezone": "Europe\/Berlin"
    },
    "event_code": "900101",
    "action_code": "U",
    "allowed_admin_view": false,
    "failed": false,
    "failed_reason": null,
    "email": "admin@example.org"
}
```

### Change roles (900102)

Triggered when account roles have changed for a user.

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
    "created_at": {
        "date": "2023-09-19 10:24:37.497512",
        "timezone_type": 3,
        "timezone": "Europe\/Berlin"
    },
    "event_code": "900101",
    "action_code": "U",
    "allowed_admin_view": false,
    "failed": false,
    "failed_reason": null,
    "email": "admin@example.org"
}
```

### (De)activate user (900104)

Triggered when a user is either activated or deactivated.

```json
{
    "user_id": "1edf31fb-35cd-63ec-a120-551869429a24",
    "request": {
        "user_id": "1ee1b0c4-3fcc-6060-9e69-e10598284f03",
        "enabled": false
    },
    "created_at": {
        "date": "2023-09-19 10:25:43.055089",
        "timezone_type": 3,
        "timezone": "Europe\/Berlin"
    },
    "event_code": "900104",
    "action_code": "U",
    "allowed_admin_view": false,
    "failed": false,
    "failed_reason": null,
    "email": "admin@example.org"
}
```

- `enabled` is either `true` or `false`, depending on the new state of the user.
