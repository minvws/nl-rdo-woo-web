# Woo Publication Platform Authorization matrix

<!-- TOC -->
- [Woo Publication Platform Authorization matrix](#woo-publication-platform-authorization-matrix)
  - [Super admin](#super-admin)
  - [User](#user)
  - [Department](#department)
  - [Department landing page](#department-landing-page)
  - [Content page](#content-page)
  - [Subject](#subject)
  - [Dossier](#dossier)
  - [Document](#document)
  - [Organisation](#organisation)
  - [Inquiry](#inquiry)
  - [Misc](#misc)
<!-- TOC -->

[auth_matrix.yaml](../../config/packages/auth_matrix.yaml) is the source of truth. The tables below are a rendering of
it; where the two disagree, the YAML wins and this document needs fixing.

Permissions that are not listed default to `false`. Filters are supplementary checks on the entities a role may reach:
`organisation_only` restricts access to entities in the user's own organisation, and `published_dossiers` /
`unpublished_dossiers` restrict it by dossier state.

## Super admin

| **Prefix**  | **Roles**        | **Permissions** |
|-------------|------------------|-----------------|
| super_admin | ROLE_SUPER_ADMIN | update: true    |

## User

| **Prefix** | **Roles**               | **Permissions**                                      | **Filters**              |
|------------|-------------------------|------------------------------------------------------|--------------------------|
| user       | ROLE_ORGANISATION_ADMIN | create: true, read: true, update: true, delete: true | organisation_only: true  |
| user       | ROLE_SUPER_ADMIN        | create: true, read: true, update: true               | organisation_only: false |

## Department

| **Prefix** | **Roles**               | **Permissions**                        | **Filters**             |
|------------|-------------------------|----------------------------------------|-------------------------|
| department | ROLE_SUPER_ADMIN        | create: true, read: true, update: true |                         |
| department | ROLE_ORGANISATION_ADMIN | read: true                             | organisation_only: true |

## Department landing page

| **Prefix**              | **Roles**               | **Permissions** | **Filters**             |
|-------------------------|-------------------------|-----------------|-------------------------|
| department_landing_page | ROLE_SUPER_ADMIN        | update: true    |                         |
| department_landing_page | ROLE_ORGANISATION_ADMIN | update: true    | organisation_only: true |

## Content page

| **Prefix**   | **Roles**        | **Permissions**          |
|--------------|------------------|--------------------------|
| content_page | ROLE_SUPER_ADMIN | read: true, update: true |

## Subject

| **Prefix** | **Roles**                                 | **Permissions**                        |
|------------|-------------------------------------------|----------------------------------------|
| subject    | ROLE_SUPER_ADMIN, ROLE_ORGANISATION_ADMIN | create: true, read: true, update: true |

## Dossier

| **Prefix** | **Roles**          | **Permissions**                                                                           | **Filters**                                           |
|------------|--------------------|-------------------------------------------------------------------------------------------|-------------------------------------------------------|
| dossier    | ROLE_DOSSIER_ADMIN | create: true, read: true, update: true, delete: false                                     | published_dossiers: true                              |
| dossier    | ROLE_DOSSIER_ADMIN | create: true, read: true, update: true, delete: true                                      | published_dossiers: false, unpublished_dossiers: true |
| dossier    | ROLE_VIEW_ACCESS   | create: false, read: true, update: false, delete: false                                   | published_dossiers: true, unpublished_dossiers: true  |
| dossier    | ROLE_SUPER_ADMIN   | create: true, read: true, update: true, delete: true, execute: true, administration: true | published_dossiers: true, unpublished_dossiers: true  |
| dossier    | ROLE_API_CLIENT    | create: true, read: true, update: true, delete: false                                     | published_dossiers: false, unpublished_dossiers: true |

`ROLE_API_CLIENT` is the role granted to Publication API clients; see [dossier-types.md](dossier-types.md).

## Document

| **Prefix** | **Roles**          | **Permissions**                                         | **Filters**                                           |
|------------|--------------------|---------------------------------------------------------|-------------------------------------------------------|
| document   | ROLE_SUPER_ADMIN   | create: true, read: true, update: true, delete: true    | published_dossiers: true, unpublished_dossiers: true  |
| document   | ROLE_DOSSIER_ADMIN | create: true, read: true, update: true, delete: true    | published_dossiers: false, unpublished_dossiers: true |
| document   | ROLE_DOSSIER_ADMIN | create: true, read: true, update: true, delete: false   | published_dossiers: true, unpublished_dossiers: false |
| document   | ROLE_VIEW_ACCESS   | create: false, read: true, update: false, delete: false | published_dossiers: true, unpublished_dossiers: false |

## Organisation

| **Prefix**   | **Roles**        | **Permissions**                        |
|--------------|------------------|----------------------------------------|
| organisation | ROLE_SUPER_ADMIN | create: true, read: true, update: true |

## Inquiry

The `(un)published_dossiers` filters on the super-admin entry are needed to filter the dossier selection during linking.

| **Prefix** | **Roles**                                   | **Permissions**                                              | **Filters**                                          |
|------------|---------------------------------------------|--------------------------------------------------------------|------------------------------------------------------|
| inquiry    | ROLE_SUPER_ADMIN                            | create: true, read: true, update: true, administration: true | published_dossiers: true, unpublished_dossiers: true |
| inquiry    | ROLE_ORGANISATION_ADMIN, ROLE_DOSSIER_ADMIN | create: true, read: true, update: true                       |                                                      |
| inquiry    | ROLE_VIEW_ACCESS                            | create: false, read: true, update: false                     |                                                      |

## Misc

| **Prefix** | **Roles**                                                     | **Permissions**                                      |
|------------|---------------------------------------------------------------|------------------------------------------------------|
| stat       | ROLE_ORGANISATION_ADMIN, ROLE_SUPER_ADMIN                     | read: true                                           |
| elastic    | ROLE_SUPER_ADMIN                                              | create: true, read: true, update: true, delete: true |
| upload     | ROLE_SUPER_ADMIN, ROLE_ORGANISATION_ADMIN, ROLE_DOSSIER_ADMIN | create: true                                         |
